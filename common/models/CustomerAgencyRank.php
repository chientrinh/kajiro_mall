<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_customer_agency_rank".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerAgencyRank.php $
 * $Id: CustomerAgencyRank.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $customer_id
 * @property integer $membership_id
 *
 * @property DtbCustomer $customer
 * @property MtbMembership $membership
 */
class CustomerAgencyRank extends \yii\db\ActiveRecord
{
    const DATETIME_MAX      = '3000-12-31 23:59:59';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer_agency_rank';
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
            [['customer_id', 'rank_id', 'start_date', 'expire_date'], 'required'],
            [['customer_id', 'rank_id'], 'integer'],
            [['start_date','expire_date'], 'safe'],
            ['customer_id',  'exist', 'targetClass' => Customer::className()],
            ['rank_id', 'exist', 'targetClass' => AgencyRank::className()],
            ['start_date', 'compare', 'compareAttribute' => 'expire_date', 'operator' => '<='],
            ['start_date', 'unique', 'targetAttribute' => ['rank_id', 'customer_id', 'start_date'], 'message' => '同一顧客に同じ所属を同一の開始日で指定することはできません'],
            ['start_date', 'noDuplication'],
        ];
    }

    public function noDuplication($attr)
    {
        $query = static::find()->andWhere([
            'customer_id'   => $this->customer_id,
        ])->andWhere('((start_date < :s) AND (:e < expire_date)) OR (start_date BETWEEN :s AND :e) OR (expire_date BETWEEN :s AND :e)',[
            ':s' => $this->start_date,
            ':e' => $this->expire_date,
        ]);
        if ($this->id) {
            $query->andWhere("id <> {$this->id}");
        }

        if($model = $query->one())
            $this->addError($attr, sprintf("有効期間が重なっています：既存レコード(%s: %s から %s まで)",
                                           $model->rank->name,
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
            'rank_id'     => "ランク名",
            'liquor_rate' => '酒類割引率',
            'goods_rate'  => '雑貨割引率',
            'remedy_rate' => 'レメディー割引率',
            'other_rate'  => 'その他割引率',
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
        $model = new CustomerAgencyRank(['customer_id'   => $this->customer_id,
                                         'rank_id'       => $this->rank_id,
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
        return new CustomerAgencyRankQuery(get_called_class());
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
    public function getRank()
    {
        return $this->hasOne(AgencyRank::className(), ['rank_id' => 'rank_id']);
    }

    public function getName()
    {
        return $this->rank->name;
    }

    public function isExpired()
    {
        return (strtotime($this->expire_date) < time());
    }

    /**
     * @return boolean: whether allow save() or not
     */
    public function beforeSave($insert)
    {

        if (parent::beforeSave($insert))
        {
            $this->start_date  = date('Y-m-d 00:00:00', strtotime($this->start_date));
            $this->expire_date = date('Y-m-d 23:59:59', strtotime($this->expire_date));
            return true;
        }
        return false;
    }
}

class CustomerAgencyRankQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere('NOW() >= dtb_customer_agency_rank.start_date')
                        ->andWhere('NOW() <= dtb_customer_agency_rank.expire_date');
        else
            return $this->andWhere(['or', ['dtb_customer_agency_rank.expire_date < NOW()'],
                                          ['dtb_customer_agency_rank.start_date  > NOW()']]);
    }
}