<?php
namespace common\models;
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/DateForm.php $
 * $Id: DateForm.php 1774 2015-11-06 16:12:54Z mori $
 */
use Yii;

class DateForm extends \yii\base\Model
{
    public $year;
    public $month;
    public $day;
    public $wday;

    public function rules()
    {
        return [
            ['year', 'integer','min' => 1900, 'max'=> 3000],
            ['month','integer','min' =>    1, 'max'=>   12],
            ['day',  'integer','min' =>    1, 'max'=>   31],
            ['day',  'integer','min' =>    1, 'max'=>   30, 'when' => function($model){ return in_array($this->month,[2,4,6,9,11]); } ],
            ['wday', 'integer','min' =>    0, 'max'=>    6],
        ];
    }

    public function attributeLabels()
    {
        return [
            'year'  => '年',
            'month' => '月',
            'day'   => '日',
            'wday'  => '曜日',
        ];
    }
}
