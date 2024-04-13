<?php
namespace common\models\sodan;

use Yii;

/**
 * This is the model class for table "dtb_sodan_room".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/Room.php $
 * $Id: Room.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $room_id
 * @property integer $homoeopath_id
 * @property integer $client_id
 * @property integer $branch_id
 * @property string $date
 * @property string $time
 * @property integer $duration
 * @property integer $create_date
 * @property integer $update_date
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $status_id
 *
 * @property MtbStaff $updatedBy
 * @property MtbBranch $branch
 * @property DtbCustomer $client
 * @property MtbStaff $createdBy
 * @property DtbCustomer $homoeopath
 */
class Room extends \yii\db\ActiveRecord
{
    const DURATION_50 = 50;
    const DURATION_60 = 60;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_room';
    }

    public function behaviors()
    {
        return [
            'update' => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => function ($event) { return date('Y-m-d H:i:s'); },
            ],
            'staff_id' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
            ],
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user',true) ? Yii::$app->user : null,
           ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['branch_id','itv_date','itv_time'],'required'],
            [['homoeopath_id', 'client_id', 'branch_id', 'duration', 'created_by', 'updated_by', 'status_id'], 'integer'],
            ['duration','in','range'=>[self::DURATION_50, self::DURATION_60]],
            [['homoeopath_id','client_id'],'exist','targetClass'=>\common\models\Customer::className(),'targetAttribute'=>'customer_id'],
            [['branch_id'],'exist','targetClass'=>\common\models\Branch::className()],
            [['created_by','updated_by'],'exist','targetClass'=>\backend\models\Staff::className(),'targetAttribute'=>'staff_id'],
            [['status_id'],'default','value'=>RoomStatus::PKEY_VACANT],
            [['status_id'],'exist','targetClass'=>RoomStatus::className()],
            ['itv_time','match','pattern'=>'/^[0-9]{1,2}:[0-9]{2}:?.*/'],
            ['itv_time','validateTime'],
            ['homoeopath_id','validateHomoeopath'],
            [['itv_date', 'note'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'room_id'       => 'Room ID',
            'homoeopath_id' => 'ホメオパス',
            'client_id'     => 'クライアント',
            'interview'     => 'カルテ',
            'branch_id'     => '拠点',
            'itv_date'      => '年月日',
            'itv_time'      => '開始時刻',
            'duration'      => '相談時間 (分)',
            'branch_id'     => '拠点',
            'create_date'   => '起票日',
            'update_date'   => '更新日',
            'created_by'    => '起票者',
            'updated_by'    => '更新者',
            'status_id'     => '状態',
            'note'          => "備考",
        ];
    }

    public function attributeHints()
    {
        return [
            'itv_time'      => '9:30 から 17:00 まで、10 分または 15 分刻みで入力してください',
            'homoeopath_id' => 'ホメオパス本人が登録する場合、自分が指定されます。事務局が登録する場合、省略できます（相談会当日までにホメオパスを指定してください）',
            'client_id'     => 'クライアントは相談会当日まで未指定でかまいません',
            'note'          => 'ホメオパスや事務局が、自分や相手のために何か書き残したいとき、ここに入力します(255文字まで)',
        ];
    }

    public function afterFind()
    {
        $this->status_id = $this->currentStatus();

        if( $this->getOldAttribute('status_id') != $this->status_id)
            $this->db->createCommand('UPDATE dtb_sodan_room set status_id = :sid WHERE room_id = :rid')
                 ->bindValues([':sid' => $this->status_id,
                               ':rid' => $this->room_id])
                 ->execute();

        parent::afterFind();
    }

    public function beforeValidate()
    {
        if($this->client && $this->homoeopath && (RoomStatus::PKEY_VACANT <= $this->status_id))
           $this->status_id = RoomStatus::PKEY_OCCUPIED;

        return parent::beforeValidate();
    }

    private function currentStatus()
    {
        if(((strtotime($this->itv_date) + 24 * 60 * 60) < time()) && ! $this->client)
            return RoomStatus::PKEY_VOID;

        if($this->client && (RoomStatus::PKEY_VACANT == $this->status_id))
            return RoomStatus::PKEY_OCCUPIED;

        return $this->status_id;
    }

    public function cancelate()
    {
        if($this->isExpired())
            return true;

        $this->status_id = RoomStatus::PKEY_VACANT;

        if($this->client)
        $this->note     .= "{$this->client->name}さんの予約がキャンセルされました。\n";

        $this->client_id = null;

        if($this->homoeopath)
            \common\components\sendmail\SodanMail::canceled($this);
 
        return($this->save());
    }

    public function expire()
    {
        if($this->isExpired())
            return true;

        $this->status_id = RoomStatus::PKEY_VOID;

        return($this->save());
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new RoomQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'client_id']);
    }

    public function getEndTime()
    {
        return strtotime($this->itv_time) + ($this->duration * 60);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHomoeopath()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'homoeopath_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInterview()
    {
        return $this->hasOne(Interview::className(), ['room_id' => 'room_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatus()
    {
        return $this->hasOne(RoomStatus::className(), ['status_id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'updated_by']);
    }

    public function isExpired()
    {
        return (RoomStatus::PKEY_CANCEL <= $this->status_id);
    }

    public function isOccupied()
    {
        return $this->client;
    }

    public function isVacant()
    {
        return ! $this->client && (RoomStatus::PKEY_VACANT == $this->status_id);
    }

    public function validateHomoeopath($attr, $params)
    {
        if($this->isExpired())
            return true;

        if(! $this->itv_time || ! $this->itv_date || ! $this->duration)
            return false;

        $query = Room::find()
               ->active()
               ->where(['homoeopath_id' => $this->homoeopath_id])
               ->andWhere(['itv_date'   => $this->itv_date])
               ->andWhere(['itv_time'   => $this->itv_time]);

        if(! $this->isNewRecord)
            $query->andWhere(['not',['room_id'=>$this->room_id]]);

        if($query->exists())
            $this->addError($attr, "同じホメオパスが別の部屋で同一時刻に指定済みです");

        return $this->hasErrors($attr);
    }

    public function validateTime($attr, $params)
    {
        preg_match('/^([0-9]{1,2}):([0-9]{2}):?.*/', $this->itv_time, $match);
        $hour = $match[1];
        $min  = $match[2];

        if(! in_array($hour, range(9, 17)))
            $this->addError($attr, "'{$hour}' は 9 時から 17 時までではありません");

        if(0 < ($min % 5))
            $this->addError($attr, "'{$min}' は 5 分刻みではありません");

        if((9 == $hour) && ($min < 30))
            $this->addError($attr, "開始時刻が早すぎます");

        if((17 == $hour) && (0 < $min))
            $this->addError($attr, "開始時刻が遅すぎます");

        return $this->hasErrors($attr);
    }
}

class RoomQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere(['<','status_id', RoomStatus::PKEY_CANCEL]);
        else
            return $this->andWhere(['>=','status_id',RoomStatus::PKEY_CANCEL]);
    }

    public function vacant($state = true)
    {
        if($state)
            return $this->andWhere(['status_id' => RoomStatus::PKEY_VACANT]);
        else
            return $this->andWhere(['status_id' => RoomStatus::PKEY_OCCUPIED]);
    }

    public function year($y)
    {
        return $this->andWhere(['EXTRACT(YEAR FROM itv_date)' => $y]);
    }

    public function month($m)
    {
        return $this->andWhere(['EXTRACT(MONTH FROM itv_date)' => $m]);
    }

    /*
     * @param integer $d [0: Mon, ... 6: Sun]
    */
    public function wday($d)
    {
        return $this->andWhere(['WEEKDAY(itv_date)' => $d]);
    }

    /*
     * @param integer $d range(1, 31)
    */
    public function day($d)
    {
        return $this->andWhere(['EXTRACT(DAY FROM itv_date)' => $d]);
    }

    public function today()
    {
        return $this->andWhere(['itv_date' => date('Y-m-d')]);
    }

    public function afternoon($state = true)
    {
        if($state)
            return $this->andWhere(['>=','itv_time','12:00']);
        else
            return $this->andWhere(['<','itv_time','12:00']);
    }
}
