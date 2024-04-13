<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductDiscount.php $
 * $Id: $
 *
 */
class ProductDiscount extends \yii\db\ActiveRecord
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
        return 'dtb_product_discount';
    }

    public static function primaryKey()
    {
        return ['discount_id'];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'   => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => function ($event) { return new \yii\db\Expression('NOW()'); },
            ],
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
            [['product_id', 'd_product_id', 'use_count'], 'required'],
            [['product_id', 'd_product_id'], 'integer'],
            [['product_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'discount_id'  => 'ID',
            'product_id'   => '商品',
            'd_product_id' => '割引商品',
            'use_count'    => '使用可能回数',
            'create_by'    => '作成者',
            'create_date'  => '作成日時',
            'update_by'    => '更新者',
            'update_date'  => '更新日時',
        ];
    }

    public function attributeHints()
    {
        return [
            'product_id'   => '顧客に販売する商品のIDを入力してください',
            'd_product_id' => '上記商品使用時に割引される商品のIDを入力してください'
        ];
    }

    /* @inheritdoc */
    public static function find()
    {
        return new ProductDiscountQuery(get_called_class());
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductDiscount()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'd_product_id']);
    }
}

/**
 * ActiveQuery for ProductMaster
 */
class ProductDiscountQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }    
}
