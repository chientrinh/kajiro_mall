<?php

namespace common\models;

use Yii;
use \yii\db\ActiveRecord;

/**
 * This is the model class for table "wtb_purchase" which is used as session storage
 * @see frontend/modules/cart/Module.php
 * @see backend/modules/dispatch/Module.php
 *
 * @property integer $session
 * @property string $json
 * @property string $expire_date
 */
class WtbPurchase extends ActiveRecord
{
    const WAIT_LIMIT = 86400; // 24 Hours == 60 * 60 * 24

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wtb_purchase';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'expire',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'expire',
                ],
                'value' => function ($event) {
                    return time() + self::WAIT_LIMIT;
                },
            ],
        ];
    }

    /* @return bool */
    public static function updateSession($prev_id)
    {
        if(($model = self::findOne($prev_id)) === null)
            return false;

        if(($now_id = Yii::$app->session->id) == false)
            return false;

        $model->session = $now_id;

        return $model->save();
    }
    
    /**
     *  セッションデータのPurchaseにあるPayment_idを変更し、ログイン直後の支払い方法指定に対応する
     *  @return bool */
    public static function updatePaymentSession($prev_id, $payment_id)
    {
        if(($model = self::findOne($prev_id)) === null)
            return false;

        if(($now_id = Yii::$app->session->id) == false)
            return false;

        $json = json_decode($model->data, true);

        // 0:一括発送カート、1:豊受カート　のみ変更をかける
        $json['0']['purchase']['payment_id'] = $payment_id;
        $json['1']['purchase']['payment_id'] = $payment_id;
        
        $model->data = json_encode($json);
        $model->session = $now_id;

        return $model->save();
    }
}
