<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_purchase".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/PurchaseHeader.php $
 * $Id: PurchaseHeader.php 2254 2016-03-17 04:22:28Z mori $
 *
 */
class PurchaseHeader extends \yii\base\Model
{
    public $company;
    public $date;
    public $customer_id;
    public $jan;
    public $name;
    public $qty;
    public $price;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company','date','customer_id','jan','name','qty','price'], 'required'],
            [['jan','name'], 'string'],
            [['qty'], 'integer', 'min' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company'  => '会社',
            'date' => '日付',
            'customer_id'  => '顧客ID',
            'jan'  => 'JANコード',
            'name'  => '商品名',
            'qty'  => '数量',
            'price'  => '金額',
        ];
    }

    public function feed($line)
    {
        $buf = explode(',', rtrim($line));
        if(count($buf) !== count($this->attributeLabels()))
            return false;

        $this->company  = $buf[0];
        $this->date = $buf[1];
        $this->customer_id  = $buf[2];
        $this->jan  = $buf[3];
        $this->name  = $buf[4];
        $this->qty  = $buf[5];
        $this->price  = $buf[6];
        return true;
    }

}
