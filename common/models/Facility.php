<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Facility.php $
 * $Id: Facility.php 4219 2020-01-10 10:16:49Z mori $
 *
 * This is the model class for table "dtb_facility".
 *
 * @property integer $facility_id
 * @property integer $customer_id
 * @property string $name
 * @property string $title
 * @property string $summary
 * @property string $email
 * @property string $url
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 * @property string $fax01
 * @property string $fax02
 * @property string $fax03
 * @property string $pub_date
 * @property string $update_date
 *
 * @property DtbCustomer $customer
 * @property MtbPref $pref
 */
class Facility extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_facility';
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
            [['facility_id'], 'integer'],
            [['customer_id','name'], 'required'],
            [['customer_id'], 'exist', 'targetClass'=>Customer::className()],
            [['pref_id'    ], 'filter', 'filter'=>function($value){ return ! $value ? null : $value; } ],
            [['pref_id'    ], 'exist', 'targetClass'=>Pref::className()],
            [['private' ], 'default', 'value' => 1 ],
            [['private' ], 'in', 'range' => [0, 1] ],
            [['pub_date'], 'default', 'value' => date('Y-m-d')],
            [['pub_date', 'update_date'], 'safe'],
            [['url'     ], 'url'],
            [['summary' ], 'string', 'max' => 1024 * 10 /* 長すぎても無駄なので */ ],
            [['name', 'title', 'email', 'url', 'zip01', 'zip02', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03', 'fax01', 'fax02', 'fax03'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'facility_id' => '提携施設ID',
            'customer_id' => '顧客ID',
            'name'        => '施設名',
            'title'       => '肩書き',
            'summary'     => '紹介文',
            'email'       => 'メールアドレス',
            'url'         => 'URL',
            'zip'         => '郵便番号',
            'pref_id'     => '都道府県',
            'addr'        => '住所',
            'addr01'      => '市区町村名',
            'addr02'      => '番地・ビル名',
            'tel'         => '電話',
            'tel01'       => '市外局番',
            'tel02'       => '市内局番',
            'tel03'       => '枝番',
            'fax'         => 'FAX',
            'fax01'       => '市外局番',
            'fax02'       => '市内局番',
            'fax03'       => '枝番',
            'private'     => '公開しない',
            'pub_date'    => '公開日',
            'update_date' => '更新日',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name'        => '法人名または屋号を入力します',
            'title'       => 'この施設におけるご本人の肩書きを入力します（院長、所長、センター長など）',
            'email'       => '公開したいメールアドレスを入力します',
            'url'         => 'ホームページなどあれば入力します',
            'summary'     => 'テキスト形式で表示されます',
            'private'     => 'このページの情報をまだ公開したくない場合にはチェックを入れてください',
            'pub_date'    => '公開日になってから検索・閲覧が可能になります',
        ];
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

    /**
     * @return string
     */
    public function getFax()
    {
        $fax = sprintf('%s-%s-%s', $this->fax01, $this->fax02, $this->fax03);

        if(2 == strlen($fax))
            return '';

        return $fax;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMemberships()
    {
        return $this->hasMany(CustomerMembership::className(), ['customer_id' => 'customer_id'])
                    ->andWhere(['membership_id' => [
                        Membership::PKEY_HOMOEOPATH,
                        Membership::PKEY_JPHMA_ANIMAL,
                        Membership::PKEY_JPHMA_FH,
                        Membership::PKEY_JPHMA_IC,
                        Membership::PKEY_JPHMA_TECHNICAL,
                        Membership::PKEY_JPHMA_ZEN,
                        Membership::PKEY_AGENCY_HE,
//                        Membership::PKEY_AGENCY_HJ_A,
//                        Membership::PKEY_AGENCY_HJ_B,
                        Membership::PKEY_AGENCY_HP,
                        Membership::PKEY_JPHF_FARMER,
                        Membership::PKEY_HAS_QX_SCIO,
                        Membership::PKEY_HOMOEOPATHY_CENTER
                    ]])->active();
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
    public function getTel()
    {
        $tel = sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);

        if(2 == strlen($tel))
            return '';

        return $tel;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }
}
