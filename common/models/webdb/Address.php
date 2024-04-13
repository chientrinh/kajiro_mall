<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tbladdress".
 *
 * @property integer $addressid
 * @property string $postnum
 * @property string $address1
 * @property string $address2
 * @property string $address3
 * @property string $tel
 * @property string $fax
 * @property string $mobile
 * @property integer $customerid
 * @property string $email
 * @property string $url
 *
 * @property Tblcustomer $customer
 */
class Address extends \yii\db\ActiveRecord
{
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbladdress';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb20');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['postnum', 'address1', 'address2', 'address3', 'tel', 'fax', 'mobile', 'email', 'url'], 'string'],
            [['customerid'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'addressid' => 'Addressid',
            'postnum' => 'Postnum',
            'address1' => 'Address1',
            'address2' => 'Address2',
            'address3' => 'Address3',
            'tel' => 'Tel',
            'fax' => 'Fax',
            'mobile' => 'Mobile',
            'customerid' => 'Customerid',
            'email' => 'Email',
            'url' => 'Url',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'customerid']);
    }
}
