<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_event_attendee".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/EventAttendee.php $
 * $Id: EventAttendee.php 2595 2016-06-19 08:03:03Z mori $
 *
 * @property integer $product_id
 * @property integer $venue_id
 * @property integer $customer_id
 * @property integer $purchase_id
 * @property integer $adult
 * @property integer $child
 * @property string $email
 * @property string $note
 * @property integer $show_up
 * @property string $create_date
 * @property string $update_date
 * @property integer $created_by
 * @property integer $updated_by
 *
 * @property MtbStaff $updatedBy
 * @property DtbCustomer $customer
 * @property MtbStaff $createdBy
 * @property DtbProduct $product
 * @property DtbPurchase $purchase
 * @property MtbEventVenue $venue
 */
class EventAttendee extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_event_attendee';
    }

    public static function primaryKey()
{
    return ['venue_id','customer_id'];
}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_id', 'venue_id', 'adult', 'child', 'show_up', 'create_date', 'update_date'], 'required'],
            [['product_id', 'venue_id', 'customer_id', 'purchase_id', 'adult', 'child', 'show_up', 'created_by', 'updated_by'], 'integer'],
            [['create_date', 'update_date'], 'safe'],
            [['email', 'note'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'venue_id' => 'Venue ID',
            'customer_id' => 'Customer ID',
            'purchase_id' => 'Purchase ID',
            'adult' => '大人',
            'child' => '小人',
            'email' => 'Email',
            'note' => 'Note',
            'show_up' => 'Show Up',
            'create_date' => 'Create Date',
            'update_date' => 'Update Date',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(MtbStaff::className(), ['staff_id' => 'updated_by']);
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
    public function getCreatedBy()
    {
        return $this->hasOne(MtbStaff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(DtbProduct::className(), ['product_id' => 'product_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchase()
    {
        return $this->hasOne(DtbPurchase::className(), ['purchase_id' => 'purchase_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVenue()
    {
        return $this->hasOne(MtbEventVenue::className(), ['venue_id' => 'venue_id']);
    }
}
