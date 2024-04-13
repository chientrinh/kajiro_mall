<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_agency_office".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/AgencyOffice.php $
 * $Id: AgencyOffice.php 3728 2017-11-03 07:41:13Z naito $
 *
 * @property integer $office_id
 * @property integer $customer_id
 * @property string $company_name
 * @property string $person_name
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
 * @property DtbCustomer $customer
 */
class AgencyOffice extends \yii\db\ActiveRecord
{
    const PAYMENT_DAY_5          = 5;
    const PAYMENT_DAY_10         = 10;
    const PAYMENT_DAY_15         = 15;
    const PAYMENT_DAY_20         = 20;
    const PAYMENT_DAY_25         = 25;
    const PAYMENT_DAY_ENDOFMONTH = 99;

    public function init()
    {
        if ($this->payment_date == null)
            $this->payment_date = AgencyOffice::PAYMENT_DAY_15;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_agency_office';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
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
            [['company_name','person_name'],'default','value'=>''],
            [['customer_id', 'company_name', 'person_name', 'zip01', 'zip02', 'pref_id', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03'], 'required'],
            [['customer_id', 'pref_id', 'payment_date'], 'integer'],
            ['customer_id','exist','targetClass'=>Customer::className()],
            ['pref_id','exist','targetClass'=>Pref::className()],
            [['company_name', 'person_name', 'zip01', 'zip02', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03'], 'string', 'max' => 255],
            [['zip01'],          'string', 'max' => 3],
            [['zip02'],          'string', 'max' => 4],
            [['tel01','fax01'],  'string', 'max' => 5],
            [['tel02','tel03','fax02','fax03'], 'string', 'min' => 1, 'max' => 6],
            ['payment_date', 'in', 'range'=> array_keys(AgencyOffice::getPaymentDays())],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id' => '顧客',
            'company_name'=> '法人名・屋号',
            'person_name' => '担当者',
            'zip'         => "郵便番号",
            'pref_id'     => "都道府県",
            'addr'        => "住所",
            'fulladdress' => "住所",
            'addr01'      => "市区町村名",
            'addr02'      => "番地・ビル名",
            'tel01'       => "市外局番",
            'tel02'       => "市内局番",
            'tel03'       => "枝番",
            'fax01'       => "市外局番",
            'fax02'       => "市内局番",
            'fax03'       => "枝番",
            'payment_date'=> "お支払日",
        ];
    }

    public function attributeHints()
    {
        return [
            'company_name'  => '',
            'person_name'   => '',
        ];
    }

    public static function getPaymentDays($day=null)
    {
        $days = [
            AgencyOffice::PAYMENT_DAY_5 => '5日',
            AgencyOffice::PAYMENT_DAY_10 => '10日',
            AgencyOffice::PAYMENT_DAY_15 => '15日',
            AgencyOffice::PAYMENT_DAY_20 => '20日',
            AgencyOffice::PAYMENT_DAY_25 => '25日',
            AgencyOffice::PAYMENT_DAY_ENDOFMONTH => '月末',
        ];

        if ($day && array_key_exists($day, $days))
            return $days[$day];

        return $days;
    }

    /**
     * @return string
     */
    public function getAddr()
    {
        return sprintf('%s %s %s', 
                       ($this->pref ? $this->pref->name : ''),
                       $this->addr01,
                       $this->addr02);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    public function getFax()
    {
        return sprintf('%s-%s-%s', $this->fax01, $this->fax02, $this->fax03);
    }

    public function getFullAddress()
    {
        return sprintf('〒%s %s', $this->zip, $this->addr);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        return $this->hasOne(Pref::className(), ['pref_id' => 'pref_id']);
    }

    public function getTel()
    {
        return sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    public function zip2addr()
    {
        if(! $this->validate(['zip01','zip02']))
            return false;

        if(! $ret = Zip::zip2addr($this->zip01, $this->zip02))
            return false;

        $this->pref_id = $ret->pref_id;
        $this->addr01  = (1 < count($ret->addr01)) ? $ret->addr01 : array_shift($ret->addr01);
    }
}
