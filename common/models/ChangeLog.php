<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;
use \yii\helpers\Json;
use \backend\models\Staff;

/**
 * This is the model class for table "wtb_change_log".
 *
 * @property datetime $create_date
 * @property integer  $user_id
 * @property string   $tbl
 * @property string   $route
 * @property string   $action
 * @property resource $before
 * @property resource $after
 * @property integer  $expire
 */
class ChangeLog extends \yii\db\ActiveRecord
{
    const DEFAULT_LIFETIME = 7776000 ; // 90 days

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wtb_change_log';
    }

    public function behaviors()
    {
        return [
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => [],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW(5)');
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['action', 'tbl'], 'required'],
            [['action'], 'in', 'range' => ['insert','update','delete']],
            [['user_id'], 'integer'],
            [['expire'],'default', 'value' => (time() + self::DEFAULT_LIFETIME) ],
            [['pkey', 'before', 'after'], 'filter', 'filter' => function($value){ return is_string($value) ? $value : Json::encode($value); } ],
            [['tbl', 'pkey', 'route'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'create_date' => 'Create Date',
            'user_id'     => 'User ID',
            'tbl'         => 'テーブル名',
            'route'       => 'Route',
            'action'      => '操作',
            'before'      => 'Before',
            'after'       => 'After',
            'expire'      => 'Expire',
        ];
    }

    /* @return array */
    public function getDiff()
    {
        $before = Json::decode($this->before);
        $after  = Json::decode($this->after);

        if(! $before){ $before = []; }
        if(! $after ){ $after  = []; }

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $matrix = [];
        foreach($keys as $key)
        {
            $b = ArrayHelper::getValue($before, $key, '');
            $a = ArrayHelper::getValue($after , $key, '');

            $matrix[] = ['key'    => $key,
                         'before' => is_string($b) ? $b : Json::encode($b),
                         'after'  => is_string($a) ? $a : Json::encode($a),
            ];
        }

        return $matrix;
    }

    public function getUser()
    {
        if(preg_match('/^app-frontend/', $this->route))
            return Customer::findOne($this->user_id);

        else
            return Staff::findOne($this->user_id);
    }
}
