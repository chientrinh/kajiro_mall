<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * とらのこ年会費が発送済みまたは入金済みになったら会員資格を追加する
 */
class FixMembership extends \yii\base\Behavior
{
    const EXPIRE_MONTH = 5;
    const EXPIRE_DAY   = 4;

    private $action    = 'extend';
    private $mship_id  = 0;

    public function events()
    {
        return [
            Purchase::EVENT_AFTER_INSERT  => 'updateMembership',
            Purchase::EVENT_BEFORE_UPDATE => 'updateMembership',
        ];
    }

    /* @return void */
    public function updateMembership($event)
    {
        if(false == $this->configure($event))
            return;

        if('upgrade' == $this->action)
            $this->upgrade();
        else
            $this->extend();
    }

    /* @return bool */
    private function configure($event)
    {
        $owner = $this->owner;

        if($owner->isExpired())
            return false; // nothing to do

        if(0 == $owner->customer_id)
            return false; // nothing to do

        if($owner instanceof Purchase)
        {
            $purchase = $owner;

            if(! $purchase->shipped)
                return false; // nothing to do

            if((ActiveRecord::EVENT_AFTER_UPDATE == $event->name) &&
               ($purchase->getOldAttribute('shipped') ||
                ($purchase->shipped == $purchase->getOldAttribute('shipped')))
            ) // `shipped` unchanged, skip update
                return false;
        }

        $column = ArrayHelper::getColumn($owner->items, 'product_id');
        if(in_array(Product::PKEY_TORANOKO_G_ADMISSION, $column))
            $this->mship_id = Membership::PKEY_TORANOKO_GENERIC;

        elseif(in_array(Product::PKEY_TORANOKO_N_ADMISSION, $column))
            $this->mship_id = Membership::PKEY_TORANOKO_NETWORK;

        elseif(in_array(Product::PKEY_TORANOKO_N_UPGRADE, $column))
        {
            $this->mship_id = Membership::PKEY_TORANOKO_GENERIC;
            $this->action   = 'upgrade';
        }

        if(! $this->mship_id)
            return false; // nothing to do

        return true;
    }

    /* @return void */
    private function extend()
    {
        $purchase = $this->owner;
        $customer = $purchase->customer;
        $start    = $purchase->create_date;

        if($customer->isToranoko())
        {
            $exp = $customer->getMemberships()
                            ->andWhere(['not', ['start_date' => $purchase->create_date]])
                            ->andWhere(['membership_id'=>[Membership::PKEY_TORANOKO_GENERIC,
                                                          Membership::PKEY_TORANOKO_GENERIC_UK,
                                                          Membership::PKEY_TORANOKO_NETWORK,
                                                          Membership::PKEY_TORANOKO_NETWORK_UK,
                                                          Membership::PKEY_TORANOKO_FAMILY]])
                            ->max('expire_date');

            if($exp)
                $start = date('Y-m-d H:i:s', strtotime($exp) + 1);
        }

        if($customer->getMemberships()->andWhere(['start_date'=>$start])->exists())
            return; // already inserted

        $query = CustomerMembership::find()
                                   ->andWhere(['customer_id'   => $customer->customer_id])
                                   ->andWhere(['membership_id' => $this->mship_id])
                                   ->andWhere(['like','start_date',date('Y-m-d', strtotime($start))]);

        $model = $query->one(); // ToranokoApplicationForm::saveMembership()でINSERTしたレコードを取得

        if(! $model)
            $model = new CustomerMembership(['customer_id'   => $customer->customer_id,
                                             'membership_id' => $this->mship_id]);

        $exp    = strtotime($start);
        $expire = sprintf('%04d-%02d-%02d 23:59:59',
                          ((5 <= date('m',$exp)) ? date('Y',$exp)+1 : date('Y',$exp)),
                          self::EXPIRE_MONTH,
                          self::EXPIRE_DAY); // 次の５月４日２３時まで有効

        $model->start_date  = $start;
        $model->expire_date = $expire;

        if(! $model->save())
            Yii::error(sprintf('%s->save() failed: %s, %s at %s', $model->className(),
                               implode(';', $model->attributes),
                               implode(';', $model->firstErrors),
                               self::className()
            ));
    }

    private function upgrade()
    {
        $purchase = $this->owner;
        $customer = $purchase->customer;
        $query    = $customer->getMemberships()
                             ->andWhere(['membership_id'=>[Membership::PKEY_TORANOKO_NETWORK,
                                                           Membership::PKEY_TORANOKO_NETWORK_UK]])
                             ->orderBy(['expire_date' => SORT_ASC]);
        if(! $m1 = $query->one())
        {
            Yii::error(["とらのこ正会員へアップグレードが指定された伝票ですが肝心の顧客はネットワーク会員ではありませんでした。または処理済みの可能性があります",
                        'customer_id' => $customer->id,
                        'purchase_id' => $purchase->purchase_id,
            ]);
            return;
        }

        // 正会員資格を用意する
        $m2 = new CustomerMembership(['customer_id'  => $customer->id,
                                      'membership_id'=> $this->mship_id]);

        // 有効期限を計算する
        $exp = $purchase->create_date;
        if($exp instanceof \yii\db\Expression) // $purchase is newly inserted
            $exp = Purchase::find()->where(['purchase_id'=>$purchase->purchase_id])
                                   ->select('create_date')
                                   ->scalar();

        $stamp = strtotime($exp);
        $eoy   = sprintf('%04d-%02d-%02d 23:59:59', // 年度末を計算する（翌年５月４日23時59分59秒）
                       ((5 <= date('m',$stamp)) ? date('Y',$stamp)+1 : date('Y',$stamp)),
                       self::EXPIRE_MONTH,
                       self::EXPIRE_DAY);

        if(strtotime($eoy) < strtotime($m1->expire_date)) // 元の会員資格の期限は今年度末を超えている
        {
            $m3 = new CustomerMembership(['customer_id'  => $customer->id,
                                          'start_date'   => date('Y-m-d', strtotime($eoy) + 1),
                                          'expire_date'  => $m1->expire_date,
                                          'membership_id'=> $m1->membership_id,
            ]);//今年度末を超えている部分をコピーする
        }
        $m1->expire_date = date('Y-m-d H:i:s', $stamp - 1);
        $m2->start_date  = $exp;
        $m2->expire_date = $eoy;

        if(! $m1->save())
            Yii::error([$m1->attributes, $m1->firstErrors]);

        if(! $m2->save())
            Yii::error([$m2->attributes, $m2->firstErrors]);

        if(isset($m3) && ! $m3->save())
            Yii::error([$m3->attributes, $m3->firstErrors]);
    }

}
