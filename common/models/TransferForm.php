<?php

namespace common\models;

use Yii;
use \backend\models\Staff;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/TransferForm.php $
 * $Id: TransferForm.php 2755 2016-07-20 06:24:49Z mori $
 *
 * This is the model class for table "dtb_transfer".
 *
 * @property integer $purchase_id
 * @property integer $src_id
 * @property integer $dst_id
 * @property string $create_date
 * @property string $update_date
 * @property string $asked_at
 * @property string $posted_at
 * @property string $got_at
 * @property integer $create_by
 * @property integer $update_by
 * @property integer $status_id
 * @property string $note
 *
 * @property MtbPurchaseStatus $status
 * @property MtbBranch $dst
 * @property MtbBranch $src
 * @property DtbTransferItem[] $dtbTransferItems
 */
class TransferForm extends Transfer
{
    public $items;

    public function init()
    {
        parent::init();

        if(! $this->items){ $this->items = []; }
    }

    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['items','required','message'=>'商品がありません'];

        return $rules;
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if($runValidation && $this->hasErrors())
            return false;

        if($attributeNames && ! in_array('items', $attributeNames))
             return parent::save($runValidation, $attributeNames);
 
        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            $this->deleteItems();
            if(! parent::save($runValidation, $attributeNames))
                throw new \yii\db\Exception('save() failed');

            $this->saveItems();
        }
        catch (\yii\db\Exception $e)
        {
            Yii::error($e->__toString(), $this->className().'::'.__FUNCTION__);
            $transaction->rollBack();
            return false;
        }

        $transaction->commit();
        return true;
    }

    private function deleteItems()
    {
        if($this->isNewRecord)
            return;

        TransferItem::deleteAll('purchase_id = :id', [
            ':id' => $this->purchase_id,
        ]);

        return;
    }

    private function saveItems()
    {
        $items = [];
        foreach($this->items as $item)
        {
            $chunk = $this->convertToPurchaseItem($item,count($items));

            if(is_array($chunk))
                foreach($chunk as $model)
                    $items[] = $model;
            else
                $items[] = $chunk;
        }

        $this->items = $this->convertToTransferItems($items);

        foreach($this->items as $item)
            if(! $item->save())
                throw new \yii\db\Exception("{$item->className()}->save() failed");

        return;
    }

    private function convertToPurchaseItem(\yii\base\Model $item, $seq)
    {
        $pkey = $this->purchase_id;

        if(method_exists($item, 'convertToPurchaseItem'))
            $model = $item->convertToPurchaseItem($seq, [
                'purchase_id' => $pkey,
            ]);
        else
            $model = $item;

        return $model;
    }

    private function convertToTransferItems($items)
    {
        $seq  = 0;

        foreach($items as $k => $item)
        {
            $model = new TransferItem(['purchase_id' => $this->purchase_id]);

            foreach($model->attributes() as $attr)
            {
                if($item->hasAttribute($attr))
                    $model->$attr = $item->$attr;
                elseif(in_array($attr,['item_id','qty_shipped']))
                    ; // pass
                elseif('qty_request' == $attr)
                    $model->$attr = $item->quantity;
                else
                    throw new \yii\db\Exception("item {$item->className()} does not have failed: $attr");
            }

            $model->purchase_id = $this->purchase_id;
            $model->seq = $seq++;
            $items[$k] = $model;
        }

        return $items;
    }

}
