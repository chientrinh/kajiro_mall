<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_product_restriction".
 *
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductRestriction.php $
 * @version $Id: ProductRestriction.php 2722 2016-07-15 08:38:22Z mori $
 *
 *
 * @property integer $restrict_id
 * @property string $name
 *
 * @property MvtbProductMaster[] $mvtbProductMasters
 */
class ProductRestriction extends \yii\db\ActiveRecord
{
    const PKEY_INSTORE_ONLY = 99;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_product_restriction';
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
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'restrict_id' => 'Restrict ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(ProductMaster::className(), ['restrict_id' => 'restrict_id']);
    }
}
