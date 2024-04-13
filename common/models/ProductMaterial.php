<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_product_material".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductMaterial.php $
 * $Id: ProductMaterial.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $product_id
 * @property integer $material_id
 * @property integer $unit_id
 * @property integer $quantity
 * @property string $start_date
 * @property string $expire_date
 *
 * @property DtbProduct $product
 * @property MtbMaterial $material
 * @property MtbUnit $unit
 */
class ProductMaterial extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_product_material';
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
            [['product_id', 'material_id', 'unit_id', 'quantity', 'start_date'], 'required'],
            [['product_id', 'material_id', 'unit_id', 'quantity'], 'integer'],
            [['start_date', 'expire_date'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'material_id' => 'Material ID',
            'unit_id' => 'Unit ID',
            'quantity' => 'Quantity',
            'start_date' => 'Start Date',
            'expire_date' => 'Expire Date',
        ];
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
    public function getMaterial()
    {
        return $this->hasOne(MtbMaterial::className(), ['material_id' => 'material_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(MtbUnit::className(), ['unit_id' => 'unit_id']);
    }
}
