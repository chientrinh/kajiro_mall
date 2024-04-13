<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the model class for table "dtb_com_off_purchase_item".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ComOffPurchaseItem.php $
 * $Id: ComOffPurchaseItem.php 2386 2016-04-07 06:14:03Z mori $
 *
 * @property integer $purchase_id
 * @property integer $product_id
 * @property integer $company_id
 * @property integer $quantity
 * @property integer $price
 * @property integer $discount_rate
 * @property integer $discount_amount
 * @property integer $point_rate
 * @property integer $point_amount
 * @property integer $point_consume
 * @property integer $point_consume_rate
 * @property integer $is_wholesale
 *
 * @property DtbComOffPurchase $purchase
 * @property DtbProduct $product
 */
class ComOffPurchaseItem extends \common\models\PurchaseItem
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_com_off_purchase_item';
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