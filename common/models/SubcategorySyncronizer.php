<?php

namespace common\models;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SubcategorySyncronizer.php $
 * $Id: SubcategorySyncronizer.php 3026 2016-10-27 05:21:23Z mori $
 */

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class SubcategorySyncronizer extends \yii\base\Behavior
{
    public function init()
    {
        parent::init();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'updateRows',
        ];
    }

    /* @return void */
    public function updateRows($event)
    {
        if(! $this->owner instanceof ProductMaster)
            return; // don't know how to deal with

        $prev = $this->owner->getOldAttribute('ean13');
        $now  = $this->owner->ean13;

        if($prev == $now)
            return; // nothing to do

        $query = ProductSubcategory::find()->where(['ean13' => $prev]);
        foreach($query->each() as $item)
        {
            $item->ean13 = $now;
            $item->save();
        }
    }

}

