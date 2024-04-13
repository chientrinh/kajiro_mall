<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/TransferItem.php $
 * $Id: TransferItem.php 1769 2015-11-05 09:55:16Z mori $
 *
 * This is the model class for table "dtb_transfer_item".
 *
 * @property integer $item_id
 * @property integer $purchase_id
 * @property string $ean13
 * @property string $name
 * @property integer $price
 * @property integer $qty_request
 * @property integer $qty_shipped
 *
 * @property DtbTransfer $transfer
 */
class TransferItem extends PurchaseItem
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_transfer_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['purchase_id', 'company_id', 'code', 'quantity', 'price'], 'required'],
            [['purchase_id', 'product_id', 'remedy_id'], 'integer'],
            [['price', 'quantity', 'discount_rate', 'discount_amount', 'point_rate', 'point_amount'], 'integer', 'min' => 0],
            ['purchase_id', 'exist', 'targetClass'=>Transfer::className()],
            ['product_id',  'exist', 'targetClass'=>Product::className()],
            ['remedy_id',   'exist', 'targetClass'=>Remedy::className()],
            ['company_id', 'default', 'value' => function($model, $attribute) {
                return \yii\helpers\ArrayHelper::getValue($model, 'model.company.company_id');
            }],
            ['remedy_id', 'default', 'value' => 0 ],
            [['discount_rate','discount_amount','point_rate','point_amount'], 'default', 'value' => 0 ],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'item_id'     => '品目ID',
            'purchase_id' => '移動ID',
            'code'        => '商品コード',
            'name'        => '品名',
            'price'       => '参考価格',
            'quantity'    => "数量",
            'qty_request' => '要望',
            'qty_shipped' => '実数',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'qty_request' => '発注を依頼する数量',
            'qty_shipped' => '実際に納品する・された数量',
        ];
    }

    public function beforeSave($insert)
    {
        if($insert)
            if(! isset($this->qty_shipped))
                $this->qty_shipped = $this->quantity;

        return parent::beforeSave($insert);
    }

    public function getModel()
    {
        if($this->product_id)
            return Product::findOne($this->product_id);

        elseif(in_array(strlen($this->code),[12,13]))
        {
            $finder = new \common\components\ean13\ModelFinder();
            return $finder->getOne($this->code);
        }

        return null;
    }
    public function getPurchase_id()
    {
        return (0 - $this->purchase_id);
    }

    public function getQuantity()
    {
        if(isset($this->qty_shipped))
            return $this->qty_shipped;

        return $this->qty_request;
    }

    public function setQuantity($value)
    {
        if($this->isNewRecord)
            $this->qty_request = $value;
        else
            $this->qty_shipped = $value;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer()
    {
        return $this->hasOne(Transfer::className(), ['purchase_id' => 'purchase_id']);
    }

}
