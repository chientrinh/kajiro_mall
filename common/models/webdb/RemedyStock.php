<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "mtb_remedy_stock".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/RemedyStock.php $
 * $Id: RemedyStock.php 1184 2015-07-23 12:19:09Z mori $
 *
 * @property integer $remedy_id
 * @property integer $potency_id
 * @property integer $vial_id
 * @property integer $prange_id
 * @property integer $in_stock
 *
 * @property remedyPotency $potency
 * @property remedyVial $vial
 * @property remedyPriceRange $prange
 * @property remedy $remedy
 */
class RemedyStock extends \common\models\RemedyStock
{
    public $name;
    public $price;

    public function attributes()
    {
        return array_merge(parent::attributes(),[
            'name','price',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrange()
    {
        return null;
    }

    /* @return bool */
    public function beforeSave($insert)
    {
        return false; // do not save
    }
}
