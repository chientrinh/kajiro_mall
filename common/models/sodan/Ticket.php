<?php

namespace common\models\sodan;

use Yii;
use backend\models\Staff;

/**
 * Ticket
 * 予約票のテンプレート管理 Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/Ticket.php $
 * $Id: Ticket.php 1876 2018-06-25 09:42:35 sado $
 */
class Ticket extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_ticket';
    }
    
    public static function find()
    {
        return new TicketQuery(get_called_class());
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
            [['ticket_id', 'customer_id', 'product_id', 'created_by', 'updated_by'], 'integer'],
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
            'ticket_id'   => 'ID',
            'customer_id' => "顧客ID",
            'product_id'  => 'チケット名',
            'create_date' => '購入日',
            'expire_date' => '有効期限',
            'created_by'  => '作成者',
            'update_date' => '更新日時',
            'status'      => 'ステータス'
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
    public function getClient()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(\common\models\Product::className(), ['product_id' => 'product_id']);
    }
    
    public function getExpiredate()
    {
        return date('Y/m/d H:i:s', strtotime($this->create_date . '+1 year'));
    }
}

class TicketQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function active()
    {
        return $this->andWhere('NOW() <= create_date + INTERVAL 1 YEAR');
    }
}
