<?php

namespace common\models\shipping;

use Yii;

/**
 * This is the model class for table "register_response".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/shipping/Shipping.php $
 * $Id: ShippingHeader.php 2254 2016-03-17 04:22:28Z mori $
 *
 */
class Shipping extends \yii\base\Model
{
    /**
     * 伝票番号
     * お届け先コード
     * お届け先名
     * 荷物状況
     * 日付
     * 時刻
     * 出荷日
     * ｻｲｽﾞ品目
     * 運賃
     * お客様管理番号
     */
    public $shipping_id;
    public $code;
    public $name;
    public $status;
    public $date;
    public $time;
    public $arrangement_date;
    public $size;
    public $cost;
    public $purchase_id;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shipping_id','purchase_id'], 'required'],
            // [['shipping_id','purchase_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shipping_id'  => '伝票番号',
            'code' => 'お届け先コード',
            'name'  => 'お届け先名',
            'status'  => '荷物状況',
            'date'  => '日付',
            'time'  => '時刻',
            'arrangement_date'  => '出荷日',
            'size'  => 'ｻｲｽﾞ品目',
            'cost'  => '運賃',
            'purchase_id'  => 'お客様管理番号',
        ];
    }

    public function feed($line)
    {
        $buf = explode(',', rtrim($line));

        if(count($buf) !== count($this->attributeLabels()))
            return false;

        $this->shipping_id  = $buf[0];
        $this->code = $buf[1];
        $this->name  = $buf[2];
        $this->status  = $buf[3];
        $this->date  = $buf[4];
        $this->time  = $buf[5];
        $this->arrangement_date = $buf[6];
        $this->size  = $buf[7];
        $this->cost  = $buf[8];
        $this->purchase_id  = $buf[9];

        return $this->validate();
    }

}
