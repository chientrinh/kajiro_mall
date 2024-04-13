<?php

namespace common\models\webdb20;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/FukuyoTime.php $
 * $Id: FukuyoTime.php 2664 2016-07-06 08:36:09Z mori $
 *
 * This is the model class for table "tmfukuyo_time".
 *
 * @property integer $fukuyo_timeid
 * @property string $fukuyo_time
 */
class FukuyoTime extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmfukuyo_time';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb20');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fukuyo_time'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fukuyo_timeid' => 'Fukuyo Timeid',
            'fukuyo_time' => 'Fukuyo Time',
        ];
    }

    public function getName()
    {
        return $this->fukuyo_time;
    }

}
