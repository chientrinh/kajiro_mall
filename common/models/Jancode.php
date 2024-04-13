<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "vtb_jancode".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Jancode.php $
 * $Id: Jancode.php 2315 2016-03-27 06:15:44Z mori $
 *
 * @property string $jan
 * @property integer $product_id
 * @property string $sku_id
 */
class Jancode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'vtb_jancode';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
           [['product_id', 'remedy_id', 'potency_id', 'vial_id'], 'integer'],
           [['jan'],    'string', 'length' => 13],
           [['sku_id'], 'string', 'length' => 13],
        ];
    }

    public function beforeSave()
    {
        return false; // $this->tableName() is a view, no way to INSERT
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'jan'        => 'JAN',
            'product_id' => '商品 ID',
            'sku_id'     => 'SKU ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    /* @return ActiveQuery */
    public function getStock()
    {
        return $this->hasOne(RemedyStock::className(),[
            'remedy_id' => 'remedy_id',
            'potency_id'=> 'potency_id',
            'vial_id'   => 'vial_id',
        ]);
    }

    /* @return ActiveRecord */
    public function getStock__()
    {
        $model = RemedyStock::findOne([
            'remedy_id' => substr($this->sku_id, 2,6),
            'potency_id'=> substr($this->sku_id, 8,2),
            'vial_id'   => substr($this->sku_id,10,2),
        ]);

        if(! $model)
            $model = new RemedyStock([
                'remedy_id' => substr($this->sku_id, 2,6),
                'potency_id'=> substr($this->sku_id, 8,2),
                'vial_id'   => substr($this->sku_id,10,2),
                'in_stock'  => 0,
            ]);

        return $model;
    }

    
}
