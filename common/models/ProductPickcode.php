<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_product_pickcode".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductPickcode.php $
 * $Id: ProductPickcode.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property string $ean13
 * @property string $product_code
 * @property string $pickcode
 */
class ProductPickcode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_product_pickcode';
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
            [['pickcode','ean13','product_code'], 'trim'    ],
            [['pickcode','ean13'],                'required'],
            [['ean13'],        'string',  'length' => 13],
            [['pickcode','product_code'], 'string', 'max' => 32],
            ['ean13',          'unique'  ],
            ['product_code',   'unique'  ],
            ['pickcode',       'unique'  ],
            ['model',          'required', 'message'=>'対象商品が見つかりません'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ean13'        => 'EAN13',
            'product_code' => '商品 Code',
            'pickcode'     => 'Pickcode',
        ];
    }

    public function getModel()
    {
        if($stock = $this->remedyStock)
            return $stock;

        return $this->product;
    }

    public function getRemedyStock()
    {
        return RemedyStock::findByBarcode($this->ean13);
    }

    public function getProduct()
    {
        return Product::find()
            ->joinWith('productJan')
            ->andWhere(['or',
                       ['dtb_product_jan.jan' => $this->ean13],
                       ['code'                => [$this->ean13,
                                                  $this->product_code]],
            ]);
    }
}
