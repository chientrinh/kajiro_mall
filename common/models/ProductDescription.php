<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductDescription.php $
 * $Id: ProductDescription.php 2722 2016-07-15 08:38:22Z mori $
 *
 * This is the model class for table "mtb_product_description".
 *
 * @property integer $desc_id
 * @property integer $product_id
 * @property string $title
 * @property string $body
 *
 * @property DtbProduct $product
 */
class ProductDescription extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_product_description';
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
            [['product_id','title','body'], 'required'],
            [['product_id'], 'integer'],
            [['category_id'], 'safe'],
            [['title'], 'string', 'max' =>   255],
            [['body'],  'string', 'max' => 10240],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'desc_id'    => 'Desc ID',
            'product_id' => 'Product ID',
            'title'      => '見出し',
            'body'       => '本文',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    public function getCategory_id()
    {
        if($this->product)
            return $this->product->category_id;

       return null;
    }

    public function setCategory_id($id)
    {
        if($this->product)
            $this->product->category_id = $id;
    }
}
