<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_event_venue".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/EventVenue.php $
 * $Id: EventVenue.php 2595 2016-06-19 08:03:03Z mori $
 *
 * @property integer $venue_id
 * @property string $name
 * @property integer $branch_id
 * @property string $event_date
 * @property string $start_time
 * @property string $end_time
 * @property string $pub_date
 * @property integer $capacity
 * @property integer $allow_child
 * @property integer $overbook
 *
 * @property DtbEventAttendee[] $dtbEventAttendees
 * @property MtbBranch $branch
 */
class EventVenue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_event_venue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'product_id','branch_id', 'event_date', 'start_time', 'end_time', 'pub_date', 'capacity'], 'required'],
            [['branch_id', 'capacity', 'allow_child', 'overbook'], 'integer'],
            [['event_date', 'start_time', 'end_time', 'pub_date'], 'safe'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'venue_id'   => 'Venue ID',
            'product_id' => 'イベント名',
            'name'       => '会場名',
            'branch_id'  => '拠点',
            'event_date' => '開催日',
            'start_time' => '開始時刻',
            'end_time'   => '終了時刻',
            'pub_date'   => '受付開始',
            'capacity'   => '定員',
            'allow_child'=> '子連れOK',
            'overbook'   => 'オーバーブック',
            'vacancy'    => "残席",
            'occupancy'  => "予約率",
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttendees()
    {
        return $this->hasMany(EventAttendee::className(), ['venue_id' => 'venue_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOccupancy()
    {
        return (int) $this->getAttendees()->sum('adult + child') / $this->capacity;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }
}
