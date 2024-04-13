<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the model class for tax (消費税)
 * 2019-10-01 の新消費税率、軽減税率の施行に対応するため改修 kawai
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Tax.php $
 * $Id: Tax.php 4185 2019-09-30 16:12:44Z mori $
 *
 */

class Tax extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_tax';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        $params = [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    if($event->name == 'beforeInsert' && $event->sender->create_date)
                        return $event->sender->create_date;
                    return date('Y-m-d H:i:s');
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];

        if('app-backend' == Yii::$app->id)
            $params[] = [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by','staff_id'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['staff_id'],
                ],
                'value' => function ($event) {
                        return Yii::$app->user->id;
                },
            ];

        return $params;
    }


    public static function primaryKey()
    {
        return ['tax_id'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tax_id', 'rate', 'start_date', 'end_date'], 'required'],
            [['rate'], 'integer', 'min' => 0],
            [['name'], 'string', 'max' => 128],
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tax_id'     => "税区分ID",
            'name'         => "区分名",
            'rate'      => "税率",
            'start_date'        => "適用開始日時",
            'end_date'            => "適用終了日時",
            'create_by'             => "作成者",
            'create_date'          => "作成日時",
            'update_by'   => "更新者",
            'update_date' => "更新日時",
        ];
    }



    public function compute($price)
    {
        if($price <= 0)
            return 0;

        return max(0, floor($price * ($this->getRate()/100)));
    }

    public function getRate()
    {
        return time() >= strtotime('2019-10-01 00:00:00') && time() >= strtotime($this->start_date) ? ($this->rate ? $this->rate : 10) : 8;
    }

    public static function newDate()
    {
        return strtotime('2019-10-01 00:00:00');
    }

/*
    public $rate;

    public function init()
    {
        parent::init();

        if(($this->rate < 0) || (1 <= $this->rate))
           throw new \yii\base\InvalidConfigException('Tax::rate is not properly set');
    }

    public function getRate()
    {
        return $this->rate;
    }

    public static function getRate()
    {
        return $this->rate;
    }

    public function compute($price)
    {
        $this->rate = 8;
        if($price <= 0)
            return 0;

        return max(0, floor($price * $this->rate));
    }
*/

}
