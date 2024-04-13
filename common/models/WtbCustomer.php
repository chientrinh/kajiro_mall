<?php

namespace common\models;

use Yii;
use \yii\db\ActiveRecord;

/**
 * This is the model class for table "wtb_purchase".
 *
 * @property integer $session
 * @property string $json
 * @property string $expire_date
 */
class WtbCustomer extends ActiveRecord
{
    const WAIT_LIMIT = 3600; // 3600 == (60 * 60) == 1 Hour

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wtb_customer';
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
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'token',
                ],
                'value' => function ($event) {
                    return Yii::$app->security->generateRandomString() . '_' . time();
                },
            ],
        ];
    }
}
