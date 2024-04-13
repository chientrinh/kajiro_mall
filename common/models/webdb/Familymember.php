<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tblfamilymember".
 *
 * @property integer $familymemberid
 * @property integer $customerid
 * @property integer $parentid
 *
 * @property Tblcustomer $customer
 */
class Familymember extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblfamilymember';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb18');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['customerid', 'parentid'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'familymemberid' => 'Familymemberid',
            'customerid' => 'Customerid',
            'parentid' => 'Parentid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'customerid']);
    }

    public function getParent()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'parentid']);
    }

    public function getBrothers()
    {
        return $this->hasMany(self::className(), ['parentid' => 'parentid']);
    }
}