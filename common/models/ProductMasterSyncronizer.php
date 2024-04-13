<?php 

namespace common\models;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductMasterSyncronizer.php $
 * $Id: ProductMasterSyncronizer.php 4098 2019-01-16 07:09:13Z kawai $
 */

use Yii;
use yii\db\ActiveRecord;

class ProductMasterSyncronizer extends \yii\base\Behavior
{
    public function init()
    {
        parent::init();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'updateRow',
            ActiveRecord::EVENT_AFTER_UPDATE => 'updateRow',
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteRow',
        ];
    }

    /* @return void */
    public function updateRow($event)
    {
        if(! $this->owner instanceof RemedyStock && ! $this->owner instanceof Product)
            return;

        $model = $this->loadModel();

//        if($this->owner instanceof Product && $this->owner->isExpired())
//            return $model->delete();

        if(0 < $model->product_id) // dtb_productなら name を常に上書きする
            $model->name = $this->owner->name;
        else
        {
            if(! $model->name)
                 $model->name = $this->owner->name;
            // mtb_remedy_stockの場合、INSERT時にのみnameを代入する
            // 既存レコードの mvtb_product_master.name は上書きしない
        }

        if(! $kana = $this->owner->getAttribute('kana'))
             $kana = $this->owner->getKana();

        if(null === $price = $this->owner->getAttribute('price'))
             $price = $this->owner->getPrice();

        $model->load([
                'ean13'      => $this->owner->getBarcode(),
                'company_id' => $this->owner->category->seller_id,
                'category_id'=> $this->owner->category->category_id,
                'product_id' => $this->owner->getAttribute('product_id'),
                'remedy_id'  => $this->owner->getAttribute('remedy_id'),
                'potency_id' => $this->owner->getAttribute('potency_id'),
                'vial_id'    => $this->owner->getAttribute('vial_id'),
                'restrict_id'=> $this->owner->getAttribute('restrict_id'),
                'kana'       => $kana,
                'price'      => $price,
                'in_stock'   => $this->owner->getAttribute('in_stock'),
            ],'');

        if(! $model->save())
            Yii::error([$model->errors,$model->attributes],$this->className().'::'.__FUNCTION__);
    }

    public function deleteRow()
    {
        $model = $this->loadModel();

        if($model->isNewRecord)
            return;

        $model->delete();
    }

    private function loadModel()
    {
        $model = ProductMaster::find()->andFilterWhere([
            'product_id' => $this->owner->getAttribute('product_id'),
            'remedy_id'  => $this->owner->getAttribute('remedy_id'),
            'potency_id' => $this->owner->getAttribute('potency_id'),
            'vial_id'    => $this->owner->getAttribute('vial_id'),
        ])->one();

        if(! $model)
            $model = new ProductMaster();

        return $model;
    }

}

