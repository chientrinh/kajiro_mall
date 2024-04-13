<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_customer_membership".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerMembership.php $
 * $Id: CustomerMembership.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $customer_id
 * @property integer $membership_id
 *
 * @property DtbCustomer $customer
 * @property MtbMembership $membership
 */
class CustomerMembership extends \yii\db\ActiveRecord
{
    const DATETIME_MAX      = '3000-12-31 00:00:00';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer_membership';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['start_date'],
                ],
                'value' => function ($event) {
                    return $this->start_date ? $this->start_date : new \yii\db\Expression('NOW()');
                },
            ],
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['expire_date'],
                ],
                'value' => function ($event) {
                    return $this->expire_date ? $this->expire_date : self::DATETIME_MAX ;
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['customer_id', 'membership_id'], 'required'],
            [['customer_id', 'membership_id'], 'integer'],
            [['start_date','expire_date'], 'safe'],
            ['customer_id',  'exist', 'targetClass'=>Customer::className()],
            ['membership_id','exist', 'targetClass'=>Membership::className()],
            ['start_date','compare','compareAttribute'=>'expire_date','operator'=>'<='],
            ['start_date','unique','targetAttribute'=>['membership_id','customer_id','start_date'],'message'=>'同一顧客に同じ所属を同一の開始日で指定することはできません'],
            ['start_date','noDuplication','skipOnError'=>true, 'when'=>function($model){ return $model->isNewRecord; }],
        ];
    }

    public function noDuplication($attr, $params)
    {
        $query = static::find()->andWhere([
            'customer_id'   => $this->customer_id,
            'membership_id' => $this->membership_id,
        ])->andWhere('((start_date < :s) AND (:e < expire_date)) OR (start_date BETWEEN :s AND :e) OR (expire_date BETWEEN :s AND :e)',[
            ':s' => $this->start_date,
            ':e' => $this->expire_date,
        ]);

        if(($model = $query->one()) && ! $this->equals($model))
            $this->addError($attr, sprintf("有効期間が重なっています：既存レコード(%s: %s から %s まで)",
                                           $model->membership->name,
                                           Yii::$app->formatter->asDate($model->start_date),
                                           Yii::$app->formatter->asDate($model->expire_date)));

        return $this->hasErrors($attr);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'label'       => "名称",
            'start_date'  => "開始",
            'expire_date' => "終了",
        ];
    }

    public function expire()
    {
        if($this->isExpired())
            return true;

        $this->expire_date = date('Y-m-d H:i:s');
        
        return (0 < $this->update());
    }

    public function extend($expire_date)
    {
        $start = strtotime($this->expire_date) + 1;
        $start = date('Y-m-d H:i:s', $start);

        return $this->renew($start, $expire_date);
    }

    private function renew($start_date, $expire_date)
    {
        $model = new CustomerMembership(['customer_id'   => $this->customer_id,
                                         'membership_id' => $this->membership_id,
                                         'start_date'    => $start_date,
                                         'expire_date'   => $expire_date]);
        if(! $model->save())
            return null;

        return $model;
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new CustomerMembershipQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->membership->company;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMembership()
    {
        return $this->hasOne(Membership::className(), ['membership_id' => 'membership_id']);
    }

    public function getLabel()
    {
        return $this->membership->name;
    }

    public function getName()
    {
        return $this->membership->name;
    }

    public function isExpired()
    {
        return (strtotime($this->expire_date) < time());
    }

    public static function setAsToranokoGenericMember($customer)
    {
        return self::setMembership($customer, Membership::PKEY_TORANOKO_GENERIC);
    }

    public static function setAsToranokoFamilyMember($child, $parent)
    {
        if(! $parent->equals($child->parent)) // establish the family tie
            $parent->link('children',$child);

        if(! $mship = static::find() // find parent's membership property
            ->active()
            ->toranoko()
            ->andWhere(['customer_id' => $parent->customer_id])
            ->one())
        {
            Yii::warning(sprintf('parent(%d) is not valid toranoko member',$parent->customer_id),self::className().'::'.__FUNCTION__);
            return false;
        }

        $profile = new CustomerMembership([
            'customer_id'   => $child->customer_id,
            'membership_id' => \common\models\Membership::PKEY_TORANOKO_FAMILY,
            'start_date'    => $mship->start_date,
            'expire_date'   => $mship->expire_date,
        ]);
        if(! $profile->save())
            Yii::error($profile->errors,self::className().'::'.__FUNCTION__);

        return ! $profile->isNewRecord;
    }

    public static function setAsToranokoNetworkMember($customer)
    {
        return self::setMembership($customer, Membership::PKEY_TORANOKO_NETWORK);
    }

    private static function setMembership($customer, $target_id)
    {
        if($customer->isNewRecord)
            return false;

        if($customer->memberships)
            foreach($customer->memberships as $mship)
                if(($mship->membership_id == $target_id) && (time() < strtotime($mship->expire_date)))
                    return true;

        $model = new self();
        $model->customer_id   = $customer->customer_id;
        $model->membership_id = $target_id;

        return $model->save();
    }
}
