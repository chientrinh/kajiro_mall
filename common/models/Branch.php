<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_branch".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Branch.php $
 * $Id: Branch.php 4161 2019-06-07 05:51:23Z mori $
 *
 * @property integer $branch_id
 * @property integer $company_id
 * @property string  $name
 * @property string  $zip01
 * @property string  $zip02
 * @property integer $pref_id
 * @property string  $addr01
 * @property string  $addr02
 * @property string  $tel01
 * @property string  $tel02
 * @property string  $tel03
 *
 * @property Inventory[] $inventories
 * @property Manufacture[] $manufactures
 * @property MaterialInventory[] $materialInventories
 * @property ProductPoint[] $productPoints
 * @property Storages[] $storages
 * @property Company $company
 * @property Pref $pref
 * @property StaffRole[] $staffRoles
 * @property Staff[] $staff
 */

class Branch extends \yii\db\ActiveRecord
{
    const PKEY_FRONT       = 0; // フロント画面（仮想店舗）
    const PKEY_TROSE       = 1;
    const PKEY_ROPPONMATSU = 5;
    const PKEY_ATAMI       = 6;
    const PKEY_HE_TOKYO    = 8;
    const PKEY_HJ_TOKYO    = 13;
    const PKEY_HE_TORANOKO = 15;
    // 相談会機能
    const PKEY_HOMOEOPATHY_TOKYO   = 2;  // 日本ホメオパシーセンター東京総本部
    const PKEY_HOMOEOPATHY_SAPPORO = 3;  // 同札幌本部
    const PKEY_HOMOEOPATHY_NAGOYA  = 4;  // 同名古屋本部 
    const PKEY_HOMOEOPATHY_TOYOUKE_ORGANICS_SHOP = 8;  // 同札幌本部
    const PKEY_HOMOEOPATHY_SHOP_TOKYO  = 13;  // 同名古屋本部 
    const PKEY_HOMOEOPATHY_OSAKA   = 17; // 同大阪本部
    const PKEY_HOMOEOPATHY_FUKUOKA = 18; // 同福岡本部

    const PKEY_EVENT = 19; // 外部イベント
    const PKEY_CHHOM_TOKYO = 16; // CHhom東京校

    const PKEY_OTHER = 99;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_branch';
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
            [['company_id', 'name', 'zip01', 'zip02', 'pref_id', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03', 'email'], 'required'],
            [['company_id', 'pref_id'], 'integer'],
            [['name'], 'string', 'max' => 45],
            [['zip01'], 'string', 'max' => 3],
            [['zip02', 'tel02', 'tel03'], 'string', 'max' => 4],
            [['addr01', 'addr02'], 'string', 'max' => 255],
            [['tel01'], 'string', 'max' => 5],
            ['email', 'email'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'branch_id'  => "支店ID",
            'company_id' => '会社',
            'name'       => "名称",
            'company'    => "所属",
            'zip'        => "郵便番号",
            'pref'       => "都道府県",
            'addr'       => "所在地",
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new BranchQuery(get_called_class());
    }

    /* @return bool */
    public function isChhomShop()
    {
        return in_array($this->branch_id, [
            0, // 仮想店舗
            8, // HE Tokyo
            9, // HE Sapporo
            10, // HE Nagoya
            11, // HE Osaka
            12, // HE Fukuoka
            self::PKEY_ATAMI,
        ]);
    }

    /* @return bool */
    public function isRemedyShop()
    {
        if (in_array($this->branch_id, [
            0, // 仮想店舗
            13, // HJ Tokyo
            14, // HJ Osaka
            self::PKEY_ATAMI,
        ]))
            return true;

        return false;
    }

    public function isWarehouse()
    {
        return in_array($this->branch_id, [
            0, // 仮想店舗
            self::PKEY_ROPPONMATSU,
            self::PKEY_ATAMI,
            self::PKEY_TROSE,
        ]);
    }

    public function isDelivery()
    {
        return in_array($this->branch_id, [
            self::PKEY_ROPPONMATSU,
            //                 self::PKEY_ATAMI,
        ]);
    }

    public function isHJForCasher()
    {
        return in_array($this->branch_id, [
            self::PKEY_HJ_TOKYO, // id: 13(ホメオパシージャパンShop東京本店)
        ]);
    }

    public function isHEForCasher()
    {
        return in_array($this->branch_id, [
            8, // HE Tokyo
            9, // HE Sapporo
            10, // HE Nagoya
            11, // HE Osaka
            12, // HE Fukuoka
        ]);
    }

    public function isAtamiForCasher()
    {
        return in_array($this->branch_id, [
            self::PKEY_ATAMI,
        ]);
    }

    public function isRopponmatsuForCasher()
    {
        return in_array($this->branch_id, [
            self::PKEY_ROPPONMATSU,
        ]);
    }

    public function isTroseForCasher()
    {
        return in_array($this->branch_id, [
            self::PKEY_TROSE,
        ]);
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
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    public function getNickname()
    {
        return preg_replace('/日本ホメオパシーセンター|総?本部/u', '', $this->name);
    }

    /**
     * @return string
     */
    public function getTel()
    {
        if ($this->tel01 || $this->tel02 || $this->tel03)
            return sprintf("%s-%s-%s", $this->tel01, $this->tel02, $this->tel03);
        else
            return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInventories()
    {
        return $this->hasMany(Inventory::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManufactures()
    {
        return $this->hasMany(Manufacture::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterialInventories()
    {
        return $this->hasMany(MaterialInventory::className(), ['branch_id' => 'branch_id']);
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
    public function getProductPoints()
    {
        return $this->hasMany(ProductPoint::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasMany(Staff::className(), ['company_id' => 'staff_id'])->viaTable('mtb_staff_role', ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorages()
    {
        return $this->hasMany(Storage::className(), ['dst_id' => 'branch_id']);
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return sprintf("%s-%s", $this->zip01, $this->zip02);
    }
}

class BranchQuery extends \yii\db\ActiveQuery
{
    public function center($state = true)
    {
        if ($state)
            return $this->andWhere(['like', 'name', '日本ホメオパシーセンター']);
        else
            return $this->andWhere(['not like', 'name', '日本ホメオパシーセンター']);
    }

    public function forCampaign()
    {
        return $this->andWhere([
            'or',
            ['company_id' => 3],
            ['branch_id' => [Branch::PKEY_FRONT, Branch::PKEY_ATAMI, Branch::PKEY_ROPPONMATSU, Branch::PKEY_HJ_TOKYO, Branch::PKEY_EVENT]]
        ]);
    }

    public function wareHouse()
    {
        return $this->andWhere(['branch_id' => [Branch::PKEY_ATAMI, Branch::PKEY_ROPPONMATSU]]);
    }
}
