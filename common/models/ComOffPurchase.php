<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the model class for table "dtb_com_off_purchase".
 *
 * $URL: http://tarax.toyouke.com/svn/MALL/common/models/ComOffPurchase.php $
 * $Id: Purchase.php 3407 2017-06-07 06:38:33Z naito $
 *
 * @property integer $purchase_id
 * @property integer $customer_id
 * @property integer $subtotal
 * @property integer $tax
 * @property integer $tax10_price
 * @property integer $tax8_price
 * @property integer $taxHP_price
 * @property integer $include_frozen
 * @property integer $frozen_items_count
 * @property integer $postage
 * @property integer $postage_frozen
 * @property integer $receive
 * @property integer $change
 * @property integer $payment_id
 * @property integer $paid
 * @property integer $shipped
 * @property integer $shipping_id
 * @property integer $shipping_frozen_id
 * @property integer $arrangement_date
 * @property integer $include_pre_order
 * @property integer $delivery_company_id
 * @property string $create_date
 * @property string $update_date
 * @property string $note
 *
 * @property DtbCustomer $customer
 * @property MtbPayment $payment
 * @property DtbPurchaseDeliv[] $dtbPurchaseDelivs
 * @property DtbPurchaseItem[] $dtbPurchaseItems
 */
class ComOffPurchase extends \common\models\Purchase
{
    /* @inheritdoc */
    public static function tableName()
    {
        return 'dtb_com_off_purchase';
    }

    /**
     * 親クラスのPurchaseでバリデーションは完了しているため、こちらでは実施しない 2021/07/14 kawai
     */
    public function rules()
    {
        return [];
    }

    public function feed($dump)
    {
        if(! $dump)
            return;

        foreach($dump as $name => $value)
        {
            if($this->hasAttribute($name))
            {
                $this->$name = $value;
            }
        }

        return;
    }
}
