<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_company".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Company.php $
 * $Id: Company.php 4014 2018-09-07 08:28:52Z mori $
 *
 * @property integer $company_id
 * @property string $name
 * @property string $manager
 * @property string $email
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 *
 * @property MtbBranch[] $mtbBranches
 * @property MtbCategory[] $mtbCategories
 * @property MtbPref $pref
 * @property MtbMembership[] $mtbMemberships
 * @property MtbStaff[] $mtbStaff
 */
class Company extends \yii\db\ActiveRecord
{
    const PKEY_TY = 1;    // | 日本豊受自然農株式会社
    const PKEY_HJ = 2;    // | ホメオパシージャパン株式会社
    const PKEY_HE = 3;    // | ホメオパシック・エデュケーション株式会社
    const PKEY_HP = 4;    // | ホメオパシー出版株式会社
    const PKEY_JPHMA = 5; // | 日本ホメオパシー医学協会
    const PKEY_TROSE = 6; // | トミー　ローズ
    const PKEY_GAIBUEVENT = 19; // | 外部イベント 
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_company';
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
            [['key', 'name', 'manager', 'email', 'zip01', 'zip02', 'pref_id', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03'], 'required'],
            [['pref_id'], 'integer'],
            [['name', 'manager'], 'string', 'max' => 45],
            [['email', 'addr01', 'addr02'], 'string', 'max' => 255],
            [['zip01'], 'string', 'max' => 3],
            [['zip02', 'tel02', 'tel03'], 'string', 'max' => 4],
            [['tel01'], 'string', 'max' => 5]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_id' => "会社ID",
            'name'       => "会社名",
            'manager'    => 'Manager',
            'email'      => 'Email',
            'zip'        => "郵便番号",
            'pref'       => "都道府県",
            'addr'       => "所在地",
            'branch'     => "支店",
        ];
    }

    public function isHE()
    {
        return ($this->company_id == Company::PKEY_HE);
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return sprintf("%s-%s", $this->zip01, $this->zip02);
    }

    /**
     * @return string
     */
    public function getAddr()
    {
        return sprintf("%s %s %s", $this->pref->name, $this->addr01, $this->addr02);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCommissions()
    {
        return $this->hasMany(Commision::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return string
     */
    public function getTel()
    {
        if($this->tel01 || $this->tel02 || $this->tel03)
            return sprintf("%s-%s-%s", $this->tel01, $this->tel02, $this->tel03);
        else
            return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranches()
    {
        return $this->hasMany(Branch::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['seller_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        return $this->hasOne(Pref::className(), ['pref_id' => 'pref_id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Product::className(),['category_id' => 'category_id'])
                    ->viaTable(Category::tableName(),['seller_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbMemberships()
    {
        return $this->hasMany(Membership::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbStaff()
    {
        return $this->hasMany(Staff::className(), ['company_id' => 'company_id']);
    }
}
