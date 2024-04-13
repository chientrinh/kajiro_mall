<?php 

namespace common\models;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductImageSyncronizer.php $
 * $Id: ProductImageSyncronizer.php 2305 2016-03-26 07:23:25Z mori $
 */

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class ProductImageSyncronizer extends \yii\base\Behavior
{
    public function init()
    {
        parent::init();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'updateImage',
            ActiveRecord::EVENT_BEFORE_DELETE => 'deleteImage',
        ];
    }

    /* @return void */
    public function updateImage($event)
    {
        if(! $query = $this->getQuery())
            return;

        $new = $this->owner;
        if($new->images)
            return;

        foreach($query->all() as $img)
        {
            $img->ean13 = $new->barcode;
            $img->save();
        }
    }

    /* @return void */
    public function deleteImage($event)
    {
        if(! $query = $this->getQuery())
            return;

        foreach($query->all() as $img)
        {
            $img->delete();
        }
    }

    private function getQuery()
    {
        if($this->owner instanceof RemedyStock)
            return ProductImage::find()->where(['ean13' => $this->owner->barcode]);

        if(! $this->owner instanceof Product)
            return null; // don't know how to deal with

        $new  = $this->owner;
        $jan  = ArrayHelper::getValue($new,'productJan.jan');
        $isbn = ArrayHelper::getValue($new,'bookinfo.isbn');
        $old  = Product::findOne($new->product_id);

        $query = ProductImage::find()->orFilterWhere(['ean13'=> $jan  ])
                                     ->orFilterWhere(['ean13'=> $isbn ])
                                     ->orWhere(['ean13'=> $old->getBarcode(false)]);
        return $query;
    }

}

