<?php

namespace common\models;

/**
 * OfferFinder: translate EAN13 to any models under /common/models
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/OfferFinder.php $
 * $Id: OfferFinder.php 1687 2015-10-18 15:44:27Z mori $
 */

use Yii;

class OfferFinder extends \yii\base\Model
{
    public $customer;
    public $timestamp;

    public function init()
    {
        parent::init();

        if(! $this->timestamp)
             $this->timestamp = time();
    }

    public function getDiscountRateByEan13($ean13)
    {
        $cmd = 'SELECT MAX(discount_rate) FROM (
        SELECT MAX(discount_rate) AS discount_rate
        FROM mtb_offer
        WHERE grade_id = :gid
        UNION
        SELECT MAX(discount_rate) AS discount_rate
        FROM mtb_offer_seasonal
        WHERE grade_id = :gid AND start_date >= :now AND end_date <= :now
       ) t1';

        return Yii::$app->createCommand($cmd)
                        ->bindParams([
                            ':gid'=> $this->customer->grade_id,
                            ':now'=> $this->timestamp,
                        ])
                        ->queryScalar();
    }

    public function getPointRateByEan13($ean13)
    {
        $cmd = 'SELECT MAX(point_rate) FROM (
        SELECT MAX(point_rate) AS point_rate
        FROM mtb_offer
        WHERE grade_id = :gid
        UNION
        SELECT MAX(point_rate) AS point_rate
        FROM mtb_offer_seasonal
        WHERE grade_id = :gid AND start_date >= :now AND end_date <= :now
       ) t1';

        return Yii::$app->createCommand($cmd)
                        ->bindParams([
                            ':gid'=> $this->customer->grade_id,
                            ':now'=> $this->timestamp,
                        ])
                        ->queryScalar();
    }

    public function getDiscountRateByModel($model)
    {
        if($model->className == \common\models\Product::className())
            return $this->getDiscountRateByEan13($model->barcode);

        if($model->className == \common\models\RemedyStock::className())
            return $this->getDiscountRateByEan13($model->barcode);

    }

}
