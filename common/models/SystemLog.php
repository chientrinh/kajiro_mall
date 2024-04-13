<?php

namespace common\models;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SystemLog.php $
 * $Id: SystemLog.php 894 2015-04-17 00:34:10Z mori $
 *
 * This is the model class for table "ltb_system".
 *
 * @property integer $id
 * @property integer $level
 * @property string $category
 * @property double $log_time
 * @property string $prefix
 * @property string $message
 */
class SystemLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ltb_system';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['level'], 'integer'],
            [['log_time'], 'number'],
            [['prefix', 'message'], 'string'],
            [['category'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'       => 'ID',
            'level'    => 'Level',
            'category' => 'Category',
            'log_time' => 'Log Time',
            'prefix'   => 'Prefix',
            'message'  => 'Message',
        ];
    }
}