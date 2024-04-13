<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductSubcategory.php $
 * $Id: ProductSubcategory.php 3522 2017-08-01 01:13:20Z kawai $
 *
 * This is the model class for table "dtb_product_subcategory".
 *
 * @property integer $subcategory_id
 * @property string $ean13
 *
 * @property MtbSubcategory $subcategory
 */
class ProductSubcategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_product_subcategory';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return date('Y-m-d H:i:s');
                },
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
            ['ean13', 'required'],
            ['ean13', 'string', 'length' => 13],
            ['subcategory_id', 'exist', 'targetClass' => Subcategory::className()  ],
    //        ['name', 'price', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'subcategory_id' => 'Subcategory ID',
            'ean13' => 'ばーこーど',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcategory()
    {
        return $this->hasOne(Subcategory::className(), ['subcategory_id' => 'subcategory_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13']);
    }
}
