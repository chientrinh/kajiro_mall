<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_pref".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Pref.php $
 * $Id: Pref.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $pref_id
 * @property string $name
 *
 * @property DtbCustomer[] $dtbCustomers
 * @property DtbCustomerAddrbook[] $dtbCustomerAddrbooks
 * @property DtbPurchaseDeliv[] $dtbPurchaseDelivs
 * @property MtbBranch[] $mtbBranches
 * @property MtbCompany[] $mtbCompanies
 * @property MtbMaterialMaker[] $mtbMaterialMakers
 * @property MtbZip[] $mtbZips
 */
class Pref extends \yii\db\ActiveRecord
{
    const PKEY_OKINAWA = 47;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_pref';
    }

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
            [['pref_id', 'name'], 'required'],
            [['pref_id'], 'integer'],
            [['name'], 'string', 'max' => 4]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pref_id' => 'Pref ID',
            'name' => 'Name',
        ];
    }

    public static function findAllDomestic()
    {
        return self::find()->where(['pref_id' => range(1,47)])->all();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDtbCustomers()
    {
        return $this->hasMany(DtbCustomer::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDtbCustomerAddrbooks()
    {
        return $this->hasMany(DtbCustomerAddrbook::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDtbPurchaseDelivs()
    {
        return $this->hasMany(DtbPurchaseDeliv::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbBranches()
    {
        return $this->hasMany(MtbBranch::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbCompanies()
    {
        return $this->hasMany(MtbCompany::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbMaterialMakers()
    {
        return $this->hasMany(MtbMaterialMaker::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbZips()
    {
        return $this->hasMany(MtbZip::className(), ['pref_id' => 'pref_id']);
    }
}
