<?php

namespace common\models\ecorange;

use Yii;

/**
 * This is the model class for table "{{%dtb_order_detail}}".
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/OrderDetail.php $
 * @version $Id: OrderDetail.php 3197 2017-02-26 05:22:57Z naito $
 *
 * @property integer $order_id
 * @property integer $product_id
 * @property integer $classcategory_id1
 * @property integer $classcategory_id2
 * @property string $product_name
 * @property string $product_code
 * @property string $classcategory_name1
 * @property string $classcategory_name2
 * @property string $price
 * @property string $discount_rate
 * @property string $discount_price
 * @property string $credit_discount_price
 * @property string $quantity
 * @property string $point_rate
 * @property integer $stock_num
 * @property integer $remedy_kbn
 * @property string $serial_number
 * @property string $remedy_create_date
 * @property integer $label_flg
 * @property integer $shop_id
 */
class OrderDetail extends \yii\db\ActiveRecord
{
    public $create_date;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_detail}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ecOrange');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'product_id', 'classcategory_id1', 'classcategory_id2', 'product_name', 'shop_id'], 'required'],
            [['order_id', 'product_id', 'classcategory_id1', 'classcategory_id2', 'stock_num', 'remedy_kbn', 'label_flg', 'shop_id'], 'integer'],
            [['product_name', 'product_code', 'classcategory_name1', 'classcategory_name2'], 'string'],
            [['price', 'discount_rate', 'discount_price', 'credit_discount_price', 'quantity', 'point_rate'], 'number'],
            [['create_date'], 'safe'],
            [['serial_number'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'product_id' => 'Product ID',
            'classcategory_id1' => 'Classcategory Id1',
            'classcategory_id2' => 'Classcategory Id2',
            'product_name' => 'Product Name',
            'product_code' => 'Product Code',
            'classcategory_name1' => 'Classcategory Name1',
            'classcategory_name2' => 'Classcategory Name2',
            'price' => 'Price',
            'discount_rate' => '割引率',
            'discount_price' => 'Discount Price',
            'credit_discount_price' => 'Credit Discount Price',
            'quantity' => 'Quantity',
            'point_rate' => 'Point Rate',
            'stock_num' => '在庫引当数',
            'remedy_kbn' => 'レメディー種別',
            'serial_number' => '製造番号',
            'remedy_create_date' => '製造日',
            'label_flg' => 'ラベル印刷',
            'shop_id'   => 'Shop ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreate_date()
    {
        return $this->order->create_date;
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
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['order_id' => 'order_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Baseinfo::className(), ['shop_id' => 'shop_id']);
    }


}
