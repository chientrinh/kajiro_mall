<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_streaming_buy".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/StreamingBuy.php $
 * $Id: StreamingBuy.php 2795 2020-04-20 11:55:11Z kawai $
 *
 * @property int  $streaming_buy_id
 * @property int  $customer_id
 * @property int  $streaming_id
 * @property datetime  $create_date
 * @property datetime  $expire_date
 * @property datetime  $update_date
 *
 * @property Streaming $streaming
 * @property Customer  $customer
 */

class StreamingBuy extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_streaming_buy';
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

    public static function primaryKey()
    {
        return ['streaming_buy_id'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['streaming_id', 'customer_id'], 'required'],
            [['streaming_buy_id', 'streaming_id', 'customer_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'streaming_buy_id'  => "配信購入ID",
            'customer_id'       => "顧客ID",
            'streaming_id'      => "配信ID",
            'create_date'       => "作成日時",
            'expire_date'       => "公開終了日",
            'update_date'       => "更新日時",
        ];
    }


    public function isExpired()
    {
        return (strtotime($this->expire_date) <= time());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStreaming()
    {
        return $this->hasOne(Streaming::className(), ['streaming_id' => 'streaming_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

}

/*
class SalesCategoryQuery extends \yii\db\ActiveQuery
{
    public function forCampaign()
    {
        return $this->andWhere(['or', 
                                    ['company_id' => 3], 
                                    ['branch_id' => [Branch::PKEY_FRONT, Branch::PKEY_ATAMI, Branch::PKEY_ROPPONMATSU, Branch::PKEY_HJ_TOKYO, Branch::PKEY_EVENT]]
                    ]);
    }

    public function wareHouse()
    {
        return $this->andWhere(['branch_id' => [Branch::PKEY_ATAMI, Branch::PKEY_ROPPONMATSU]]);
    }
}
*/
