<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_email_history".
 *
 * @property integer $email_id
 * @property integer $purchase_id
 * @property integer $customer_id
 * @property string $sender
 * @property resource $header
 * @property resource $body
 *
 * @property DtbCustomer $customer
 * @property DtbPurchase $purchase
 */
class EmailHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_email_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['purchase_id', 'customer_id'], 'integer'],
            [['sender'], 'required'],
            [['sender', 'header', 'body'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email_id' => 'Email ID',
            'purchase_id' => 'Purchase ID',
            'customer_id' => 'Customer ID',
            'sender' => 'Sender',
            'header' => 'Header',
            'body' => 'Body',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(DtbCustomer::className(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchase()
    {
        return $this->hasOne(DtbPurchase::className(), ['purchase_id' => 'purchase_id']);
    }
}
