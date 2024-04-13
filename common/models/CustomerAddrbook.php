<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_customer_addrbook".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerAddrbook.php $
 * $Id: CustomerAddrbook.php 4119 2019-02-21 08:09:05Z kawai $
 *
 * @property integer $id
 * @property integer $customer_id
 * @property string $name01
 * @property string $name02
 * @property string $kana01
 * @property string $kana02
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 * @property string $update_date
 *
 * @property DtbCustomer $customer
 * @property MtbPref $pref
 */
class CustomerAddrbook extends \yii\db\ActiveRecord
{
    const SCENARIO_ZIP2ADDR = 'zip2addr';
    const SCENARIO_CODE = 'code';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer_addrbook';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
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
            [['name01','name02','kana01','kana02','addr01','addr02','tel01','tel02','tel03'], 'trim'],
            [['customer_id', 'name01', 'name02', 'kana01', 'kana02', 'pref_id', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03'], 'required'],
            [['kana01', 'kana02'], 'filter', 'filter' => function($value) { return \common\components\Romaji2Kana::translate($value,'hiragana'); }, 'skipOnEmpty'=>true ],
            [['zip01','zip02','tel01','tel02','tel03'], 'integer'],
            [['name01', 'name02'], 'string', 'max' => 255],
            [['kana01', 'kana02'], 'string', 'max' => 255, 'skipOnEmpty'=>true],
            [['zip01'], 'string', 'max' => 3],
            [['addr01', 'addr02'], 'string', 'max' => 255],
            [['tel01','tel02','tel03'], 'string', 'max' => 5],
            [['code'], 'string', 'max' => 10],
            [['code'], 'unique'],
            ['code', 'exist', 'targetClass' => '\common\models\Membercode', 'targetAttribute' => 'code'],
            ['customer_id', 'exist', 'targetClass' => '\common\models\Customer', 'targetAttribute' => 'customer_id'],
            ['pref_id', 'exist', 'targetClass' => '\common\models\Pref', 'targetAttribute' => 'pref_id'],
        ];
    }

    public function scenarios()
    {
        return array_merge(parent::scenarios(),[
            self::SCENARIO_ZIP2ADDR    => ['zip01','zip02'],
            self::SCENARIO_CODE    => ['code'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id'=> "ご本人",
            'code'       => "会員証NO",
            'name'       => "お名前",
            'kana'       => "かな",
            'zip'        => "郵便番号",
            'pref'       => "都道府県",
            'addr'       => "住所",
            'tel'        => "電話",
            'name01'     => "姓",
            'name02'     => "名",
            'addr01'     => "住所1",
            'addr02'     => "住所2",
        ];
    }

    public function attributeHints()
    {
        return [
            'addr01' => "市区町村名（例：千代田区神田神保町）",
            'addr02' => "番地・ビル名（例：1-3-5）",
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    public function getCode()
    {
        return $this->hasOne(Membercode::className(), ['code' => 'code']);
    }

    public function getFullAddress()
    {
        return sprintf('〒%s %s', $this->zip, $this->addr);
    }

    /**
     * @return string
     */
    public function getKana()
    {
        return sprintf('%s %s', $this->kana01, $this->kana02);
    }

    /**
     * @return string
     */
    public function getName()
    {
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
     * @return string
     */
    public function getZip()
    {
        if(! $this->zip01 && ! $this->zip02)
            return null;

        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    /**
     * @return string
     */
    public function getAddr()
    {
        return sprintf('%s %s %s', ($p = $this->pref) ? $p->name : null, $this->addr01, $this->addr02);
    }

    /**
     * @return string
     */
    public function getTel()
    {
        if(! $this->tel01 && ! $this->tel02 && ! $this->tel03)
            return null;

        return sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);
    }

    public function zip2addr()
    {
        $candidate = \common\models\Zip::zip2addr($this->zip01, $this->zip02);
        if(! $candidate)
        {
            $this->addError('zip02', "郵便番号に一致する住所が検索できませんでした");
            return false;
        }

        // apply the first address to self
        $this->pref_id = $candidate->pref_id;
        $this->addr01  = $candidate->addr01[0];

        return array_combine($candidate->addr01, $candidate->addr01);
    }

    public function code2addr()
    {
        $customer = \common\models\Customer::findByBarcode($this->code);
        if(! $customer)
        {
            $this->addError('code', "入力された会員証NOに一致する会員情報が検索できませんでした");
//            $old = CustomerAddrbook::findOne(['id' => $this->id]);
//            if($old)
//                return $old;
            return false;
        }

        // apply the first address to self
        $this->name01 = $customer->name01;
        $this->name02 = $customer->name02;
        $this->kana01 = $customer->kana01;
        $this->kana02 = $customer->kana02;
        $this->zip01 = $customer->zip01;
        $this->zip02 = $customer->zip02;
        $this->pref_id = $customer->pref_id;
        $this->addr01  = $customer->addr01;
        $this->addr02  = $customer->addr02;
        $this->tel01 = $customer->tel01;
        $this->tel02 = $customer->tel02;
        $this->tel03 = $customer->tel03;

        return $customer;
    }
}
