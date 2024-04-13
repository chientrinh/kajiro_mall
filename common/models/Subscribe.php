<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_subscribe".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Subscribe.php $
 * $Id: Subscribe.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $subscribe_id
 * @property string $name
 */
class Subscribe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_subscribe';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'subscribe_id' => 'Subscribe ID',
            'name' => 'Name',
        ];
    }
}
