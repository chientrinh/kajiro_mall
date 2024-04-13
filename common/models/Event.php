<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%dtb_product}}".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Event.php $
 * $Id: Event.php 2595 2016-06-19 08:03:03Z mori $
 *
 * @property integer $product_id
 * @property integer $category_id
 * @property string  $code
 * @property string  $name
 * @property integer $price
 * @property string  $start_date
 * @property string  $expire_date
 *
 * @property customerFavorite[] $customerFavorites
 * @property inventoryItem[] $inventoryItems
 * @property manufactureItem[] $manufactureItems
 * @property mMaterialInventoryItem[] $materialInventoryItems
 * @property category $category
 * @property productDiscount[] $productDiscounts
 * @property productJan[] $productJans
 * @property productPoint[] $productPoints
 * @property purchaseItem[] $purchaseItems
 * @property storageItem[] $ptorageItems
 * @property productMaterial[] $productMaterials
 */

class Event extends Product
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['category_id', 'mustBeEvent'];

        return $rules;
    }

    public function mustBeEvent($attr, $param)
    {
        $cat = $this->category;

        if(! $cat || ! $cat->isEvent())
            $this->addError($attr, "{$cat->name}はイベントではありません");

        return $this->hasErrors($attr);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        return array_merge($labels, [
            'price'     => "参加費",
            'occupancy' => "予約率",
            'adult'     => "大人",
            'child'     => "小人",
        ]);
    }

    public function getAttendees()
    {
        return $this->hasMany(EventAttendee::className(),['product_id'=>'product_id']);
    }

    public function getVenues()
    {
        return $this->hasMany(EventVenue::className(),['product_id'=>'product_id']);
    }

}
