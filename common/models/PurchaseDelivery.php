<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_purchase_deliv".
 *
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/PurchaseDelivery.php $
 * @version $Id: PurchaseDelivery.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $purchase_id
 * @property string $expect_date
 * @property integer $expect_time
 * @property string $update_date
 * @property integer $gift
 * @property string $code
 * @property string $name01
 * @property string $name02
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 *
 * @property MtbPref $pref
 * @property DtbPurchase $purchase
 */
class PurchaseDelivery extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_purchase_deliv';
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'update_date',
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['purchase_id', 'name01', 'name02', 'zip01', 'zip02', 'pref_id', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03'], 'required'],
            [['gift'], 'default', 'value' => 0],
            [['purchase_id', 'expect_time', 'gift', 'pref_id'], 'integer'],
            ['purchase_id', 'exist', 'targetClass' => Purchase::className() ],
            ['expect_time', 'exist', 'targetClass' => DeliveryTime::className(), 'targetAttribute' => 'time_id', 'skipOnEmpty' => true],
            ['pref_id',     'exist', 'targetClass' => Pref::className() ],
            [['expect_date', 'update_date'], 'safe'],
            [['name01', 'name02'], 'string', 'max' => 128],
            [['code'], 'string', 'max' => 10],
            [['zip01'], 'string', 'length'=> 3],
            [['zip02'], 'string', 'length'=> 4],
            [['tel01', 'tel02', 'tel03'], 'string', 'min'=> 2, 'max' => 5],
            [['addr01', 'addr02'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name'        => "お名前",
            'kana'        => "かな",
            'zip'         => "郵便番号",
            'addr'        => "住所",
            'tel'         => "電話番号",
            'expect_date' => "配達希望日",
            'expect_time' => "時間帯",
            'gift'        => "納品書金額表示",
            'code'        => '会員証NO',
        ];
    }

    /* @return string */
    public function getAddr()
    {
        if(! $this->pref && ! $this->addr01 && ! $this->addr02)
            return null;

        return sprintf('%s %s %s', $this->pref ? $this->pref->name : null, $this->addr01, $this->addr02);
    }

    public function getDateString()
    {
        if(! $this->expect_date)
            return "(日付指定なし)";

        return Yii::$app->formatter->asDate($this->expect_date, 'php:Y年m月d日(D)');
    }

    public function getDateTimeString()
    {
        return sprintf('%s %s', $this->dateString, $this->timeString);
    }

    /* @return string */
    public function getKana()
    {
        if(! $this->kana01 && ! $this->kana02)
            return null;

        return sprintf('%s %s', $this->kana01, $this->kana02);
    }

    /* @return string */
    public function getName()
    {
        if(! $this->name01 && ! $this->name02)
            return null;

        return sprintf('%s %s', $this->name01, $this->name02);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        return $this->hasOne(Pref::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchase()
    {
        return $this->hasOne(Purchase::className(), ['purchase_id' => 'purchase_id']);
    }

    /* @return string */
    public function getTel()
    {
        if(! $this->tel01 && ! $this->tel02 && ! $this->tel03)
            return null;
        return sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);
    }

    /* @return string */
    public function getMembercode()
    {
        if(! $this->code)
            return null;
        return $this->hasOne(Membercode::className(), ['ean13' => 'code']);
    }

    protected function getTime()
    {
        return $this->hasOne(DeliveryTime::className(), ['time_id' => 'expect_time']);
    }

    public function getTimeString()
    {
        if(! $this->expect_time)
            return "(時間指定なし)";
        return $this->time->name;
    }

    /* @return string */
    public function getZip()
    {
        if(! $this->zip01 && ! $this->zip02)
            return null;

        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    public function getGiftName()
    {
        return ($this->gift == 1) ? '非表示' : '表示';
    }

    public function beforeValidate()
    {
        if(! parent::beforeValidate())
            return false;

        if(! $this->expect_time)
             $this->expect_time = null;

        if(! $this->expect_date)
             $this->expect_date = null;

        return true;
    }
}
