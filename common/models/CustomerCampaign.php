<?php

namespace common\models;

use Yii;

/**
 * CustomerCampaign
 * キャンペーンコードを入力した顧客の管理Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerCampaign.php $
 * $Id: BookTemplate.php 1876 2018-03-01 09:42:35 sado $
 */
class CustomerCampaign extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer_campaign';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
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
            [['customer_id'], 'integer'],
            [['create_date'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id' => '顧客ID',
            'name'        => '名前',
            'create_date' => '作成日時'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }
}
