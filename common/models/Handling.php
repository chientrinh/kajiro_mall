<?php
namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;
use \common\models\Company;
use \common\models\Payment;


/**
  * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Handling.php $
 * $Id: Handling.php 3408 2017-06-07 06:45:01Z kawai $
 */

class Handling extends \yii\base\Model
{
    const DEFAULT_VALUE = 300;

    public $charge;
    public $payment_id;
    public $company_id;

    public function rules()
    {
        return [
            ['payment_id','required'],
            ['payment_id','exist','targetClass'=>Payment::className()],
            ['company_id','exist','targetClass'=>Company::className()],
            ['payment_id','in','range'=>[Payment::PKEY_POSTAL_COD], 'when'=>function($model){return (\common\models\Company::PKEY_TROSE != $this->company_id);}],
        ];
    }

    public function getPayment()
    {
        return Payment::find()->where(['payment_id'=>$this->payment_id])->one();
    }

    public function getValue()
    {
        $payment = $this->getPayment();
        if(! $payment || ! $payment->handling)
            return 0;

        if(in_array($this->payment_id, [Payment::PKEY_POSTAL_COD, Payment::PKEY_PARCEL_COD]))
            return $this->postalHandling();

        if(in_array($this->payment_id, [Payment::PKEY_YAMATO_COD,Payment::PKEY_DROP_SHIPPING]))
            return $this->yamatoHandling();

        return self::DEFAULT_VALUE; // 万一の場合はこの値を送料として返す
    }

    private function postalHandling()
    {
        $threshold = 10000;
//        if($this->company_id == \common\models\Company::PKEY_TROSE) {
            return 0;
//        } else {
//        return ($threshold <= $this->charge) ? 0 /*無料*/: 390 /*全国一律*/;
    }

    private function yamatoHandling()
    {
        $matrix = [
             300 =>  300 + Yii::$app->tax->compute( 300), //  1万円以下
             400 =>  400 + Yii::$app->tax->compute( 400), //  3万円以下
             600 =>  600 + Yii::$app->tax->compute( 600), // 10万円以下
            1000 => 1000 + Yii::$app->tax->compute(1000), // 10万円超
            // (商品合計 + 代引き手数料)を対象として、代引き手数料を計算します
        ];

        // set initial value
        $handling = $matrix[1000];

        if($this->charge < (10 * 10000))
            $handling = $matrix[600];

        if($this->charge < ( 3 * 10000))
            $handling = $matrix[400];

        if($this->charge < ( 1 * 10000))
            $handling = $matrix[300];

        // compute again with handling incl.
        if(( 1 * 10000) < ($this->charge + $handling))
            $handling = $matrix[400];

        if(( 3 * 10000) < ($this->charge + $handling))
            $handling = $matrix[600];

        if((10 * 10000) < ($this->charge + $handling))
            $handling = $matrix[1000];

        return $handling;
   }

}
