<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_product_jan".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductJan.php $
 * $Id: ProductJan.php 3065 2016-11-02 05:04:52Z mori $
 *
 * @property integer $product_id
 * @property string $jan
 *
 * @property DtbProduct $product
 */
class ProductJan extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_product_jan';
    }

    public static function primaryKey()
    {
        return ['jan'];
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_id', 'jan'], 'required'],
            [['product_id'], 'integer'],
            [['product_id'], 'unique', 'message'=>'この商品のJANコードは登録済みです'],
            [['product_id'], 'exist',  'targetClass'=>Product::className()],
            [['jan'], 'number'],
            [['jan'], 'unique','message' => 'このJANコードは別の商品で登録済みです'],
            [['jan'], 'string', 'length' => 13],
            [['jan'], 'checkDigit'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'jan' => 'Jan',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($product = $this->product)
            if(! $product->isNewRecord)
                 $product->update(); /* update ProductMaster::ean13 */

        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * inline validator
     * @return bool
     */
    public function checkDigit($attr, $param)
    {
     if(! \common\components\ean13\CheckDigit::verify($this->$attr))
            $this->addError($attr, sprintf("チェックディジット(最終桁)は %d であるべきです。",
                                           \common\components\ean13\CheckDigit::generate(substr($this->$attr,0,12))));

        return $this->hasErrors($attr);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

}
