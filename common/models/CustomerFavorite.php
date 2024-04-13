<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_customer_favorite".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerFavorite.php $
 * $Id: CustomerFavorite.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $customer_id
 * @property integer $product_id
 * @property string $update_date
 *
 * @property DtbCustomer $customer
 * @property DtbProduct $product
 */
class CustomerFavorite extends \yii\db\ActiveRecord
{
    const SCENARIO_PRODUCT = 'product';
    const SCENARIO_REMEDY  = 'remedy';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer_favorite';
    }

    /* @inheritdoc */
    public static function primaryKey()
    {
        return ['customer_id','product_id','remedy_id'];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'update_date',
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
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
            [['customer_id', 'product_id'], 'required', 'on'=>self::SCENARIO_PRODUCT],
            [['customer_id', 'remedy_id' ], 'required', 'on'=>self::SCENARIO_REMEDY ],
            [['customer_id', 'product_id', 'remedy_id'], 'integer'],
            ['customer_id',  'exist', 'targetClass'=>'\common\models\Customer', 'targetAttribute'=>'customer_id'],
            ['product_id',   'exist', 'targetClass'=>'\common\models\Product',  'targetAttribute'=>'product_id', 'skipOnEmpty' => true],
            ['remedy_id',   'exist', 'targetClass'=>'\common\models\Remedy',  'targetAttribute'=>'remedy_id', 'skipOnEmpty' => true],
            [['update_date'], 'safe'],
        ];
    }

    /* @inheritdoc */
    public function scenarios()
    {
        return [
            self::SCENARIO_PRODUCT => self::attributes(),
            self::SCENARIO_REMEDY  => self::attributes(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id' => 'Customer ID',
            'product_id'  => 'Product ID',
            'remedy_id'   => 'Remedy ID',
            'update_date' => 'Update Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        if($this->isProduct())
            return $this->hasOne(Product::className(), ['product_id' => 'product_id']);

        return $this->hasOne(Remedy::className(), ['remedy_id' => 'remedy_id']);
    }

    /* @return bool */
    public function isRemedy()
    {
        return (Category::REMEDY == $this->category_id);
    }

    /* @return bool */
    public function isProduct()
    {
        return (0 < $this->product_id);
    }

}
