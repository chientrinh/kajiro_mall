<?php

namespace common\models;

use Yii;
use backend\models\Staff;

/**
 * Ticket
 * 予約票のテンプレート管理 Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/DiscountProductLog.php $
 * $Id: Ticket.php 1876 2018-06-25 09:42:35 sado $
 */
class DiscountProductLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_discount_product_log';
    }
    
    public static function find()
    {
        return new DiscountProductLogQuery(get_called_class());
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            'staff_id' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
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
            [['ticket_id', 'customer_id', 'created_by', 'updated_by', 'subcategory_id', 'discount_id'], 'integer'],
            [['create_date', 'update_date'], 'safe'],
            [['created_by','updated_by'], 'exist', 'targetClass' => '\backend\models\Staff', 'targetAttribute' => 'staff_id'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ticket_id'   => 'チケット番号',
            'customer_id' => "顧客ID",
            'use_count'  => '残り使用回数',
            'create_date' => '購入日',
            'expire_date' => '有効期限',
            'created_by'  => '作成者',
            'update_date' => '更新日時',
            'status'      => 'ステータス',
            'product_id'  => '商品ID'
        ];
    }

    public function attributeHints()
    {
        return [];
    }

    public function beforeSave($insert)
    {
        if(defined('YII_ENV') && YII_ENV == 'test')
            return;

        if(! Yii::$app instanceof \yii\web\Application ||
             Yii::$app->user->isGuest ||
           ! Yii::$app->user->identity instanceof \backend\models\Staff)
        {
            $this->detachBehavior('staff_id');
            $this->created_by = 0; // system@toyouke.com
            $this->updated_by = 0; // system@toyouke.com
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDiscountProduct()
    {
        return $this->hasOne(\common\models\ProductDiscount::className(), ['discount_id' => 'discount_id']);
    }
    
    public function getExpiredate()
    {
        return date('Y/m/d H:i:s', strtotime($this->create_date . '+1 year'));
    }

    public function isUsed()
    {
        if($this->isNewRecord)
            return false;

        return (!$this->use_count && $this->used_flg);
    }
}

class DiscountProductLogQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function active()
    {
        return $this->andWhere('NOW() <= create_date + INTERVAL 1 YEAR');
    }
    
    public function ticket()
    {
        return $this->andWhere(['subcategory_id' => Subcategory::PKEY_SODAN_TICKET]);
    }
}
