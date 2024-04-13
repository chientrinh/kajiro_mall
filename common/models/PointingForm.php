<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/PointingForm.php $
 * $Id: PointingForm.php 3622 2017-09-29 12:33:29Z kawai $
 *
 */
class PointingForm extends Pointing
{
    public $items;

    private $_customer;

    public function init()
    {
        parent::init();

        if($this->isNewRecord && ! $this->items)
            $this->items = [];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['items', 'required','message'=>"商品がありません"];

        return $rules;
    }

    public function validateItems($attr, $params)
    {
        foreach($this->items as $item)
            if(! $item->validate())
            {
                $this->addError($attr, "商品の入力に不整合があります");
                return false;
            }
        
        return true;
    }

    public function dump()
    {
        $dump = $this->attributes;

        $dump['items'] = [];
        foreach($this->items as $item)
        {
            $dump['items'][] = $item->attributes;
        }

        return $dump;
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

        if(! isset($dump['items']))
            return;

        $this->items = [];

        foreach($dump['items'] as $k => $attributes)
        {
            $item = new PointingItem();

            foreach($attributes as $name => $value)
                if($item->hasAttribute($name)){
                   $item->$name = $value;
                }

            $this->items[$k] = $item;
        }

        return;
    }

    public function getCustomer()
    {
        if(isset($this->_customer))
            return $this->_customer;

        if($this->customer_id)
            $model = parent::getCustomer()->one();

        if(! isset($model))
             $model = false;

        if($model && ! $this->isNewRecord)
            $model->point += ($this->point_consume - $this->point_given);

        return $this->_customer = $model;
    }

    public function addItem(\common\models\Product $product)
    {
        $item = new PointingItem(['quantity' => 1]);

        foreach($item->attributes as $name => $value)
        {
            if($product->hasAttribute($name))
                if($name == 'code') {
                    $item->$name = $product->barcode;
                } else {
                    $item->$name = $product->$name;
                }
        }

        $this->items[] = $item;
    }

    public function mergeItems()
    {
        $newItems = [];
        foreach($this->items as $k => $item)
        {
            if($item->quantity <= 0)
                continue;

            foreach($newItems as $i => $newItem)
            {
                $diff = array_diff($item->attributes, $newItem->attributes);
                ArrayHelper::remove($diff, 'seq');
                ArrayHelper::remove($diff, 'quantity');
                
                if(0 == count($diff))
                {
                    $newItems[$i]->quantity += $this->items[$k]->quantity;
                    unset($this->items[$k]);
                    break;
                }
            }
            if(isset($this->items[$k]))
                $newItems[] = $this->items[$k];
        }

        foreach($newItems as $k => $v)
            $newItems[$k]->seq = $k;

        $this->items = $newItems;
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            if(! $this->isNewRecord)
            {
                PointingItem::deleteAll('pointing_id = :id', [
                    ':id' => $this->pointing_id
                ]);
            }

            if(! parent::save($runValidation, $attributeNames) )
                throw new \yii\db\Exception('failed to save dtb_pointing');

            $pkey = $this->pointing_id;
            $seq = 0;
            foreach($this->items as $item)
            {
                $item->pointing_id = $pkey;
                $item->seq = $seq++;
                if(! $item->save())
                {
                    Yii::error($item->errors);
                    throw new \yii\db\Exception('failed to save dtb_pointing_item');
                }
                Yii::warning($item->attributes);
            }

            if($this->customer)
            {
                $this->customer->point = $this->customer->currentPoint();
                $this->customer->save(false, ['point']);
            }
            
        }
        catch (\yii\db\Exception $e)
        {
            Yii::warning($e->__toString(), $this->className().'::'.__FUNCTION__);

            $transaction->rollBack();
            return false;
        }
        $transaction->commit();
        Yii::warning('commit done');
        return true;
    }
}
