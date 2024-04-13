<?php

namespace common\models;

use Yii;
use \yii\db\ActiveRecord;

/**
 * This is the model class for table "wtb_pointing".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/WtbPointing.php $
 * $Id: WtbPointing.php 1223 2015-08-02 01:35:03Z mori $
 *
 * @property string   $session
 * @property integer  $seller_id
 * @property resource $data
 * @property integer  $expire
 */
class WtbPointing extends \yii\db\ActiveRecord
{
    const WAIT_LIMIT = 7200; // 2 Hours == 60 * 60 * 2

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wtb_pointing';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['session'], 'required'],
            [['session'], 'unique'],
        ];
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
                    return time() + static::WAIT_LIMIT;
                },
            ],
        ];
    }

}
