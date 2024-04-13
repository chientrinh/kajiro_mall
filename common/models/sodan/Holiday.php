<?php

namespace common\models\sodan;

use Yii;
use \common\models\Customer;
use \backend\models\Staff;

/**
 * This is the model class for table "dtb_sodan_holiday".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/Holiday.php $
 * $Id: Holiday.php 4143 2019-03-28 08:43:17Z kawai $
 *
 * @property integer $id
 * @property string $date
 * @property integer $homoeopath_id
 * @property integer $active
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $created_by
 * @property integer $updated_by
 *
 * @property MtbStaff $updatedBy
 * @property MtbStaff $createdBy
 * @property DtbCustomer $homoeopath
 */
class Holiday extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_holiday';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'staff_id' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by','updated_by'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_by'],
                ],
                'value' => function ($event) {
                    if(! Yii::$app->get('user', false) || ! Yii::$app->user->identity instanceof \backend\models\Staff)
                        return null;

                    return Yii::$app->user->id;
                },
            ],
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_at','updated_at'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => function ($event) {
                    return time();
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
            [['date'], 'required'],
            [['date'], 'date', 'format'=>'php:Y-m-d'],
            [['date'], 'validateStatus'],
            ['homoeopath_id', 'default', 'value' => null],
            [['homoeopath_id', 'active', 'created_at', 'updated_at', 'created_by', 'updated_by', 'all_day', 'holiday_flg'], 'integer'],
            [['homoeopath_id'], 'exist', 'targetClass'=>Customer::className(), 'targetAttribute'=>'customer_id', 'when' => function($data) {return ($data->homoeopath_id);}],
            [['created_by','updated_by'], 'exist', 'targetClass'=>Customer::className(), 'targetAttribute'=>'customer_id'],
            [['active'],'in','range'=>[0,1] ],
            [['title', 'start_time', 'end_time', 'note'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'date'          => '年月日',
            'homoeopath_id' => 'ホメオパス',
            'active'        => '有効',
            'created_at'    => '作成日',
            'updated_at'    => '更新日',
            'created_by'    => '作成者',
            'updated_by'    => '更新者',
            'all_day'       => '終日',
            'holiday_flg'   => '休業日として扱う',
            'start_time'    => '休業時刻（開始）',
            'end_time'      => '休業時刻（終了）',
            'note'          => '備考',
            'title'         => '名前',
        ];
    }

    public function attributeHints() {
        return [
            'homoeopath_id' => '選択しない場合は会社全体の休業日になります'
        ];
    }

    public static function find()
    {
        return new HolidayQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(Staff::className(), ['created_by' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHomoeopath()
    {
        return $this->hasOne(Homoeopath::className(), ['homoeopath_id' => 'homoeopath_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['updated_by' => 'staff_id']);
    }

    public function validateStatus($attr, $params)
    {
        if(! $this->active)
            return true;

        $q = Holiday::find()->active()->andWhere(['date'=>$this->date]);
        if($this->homoeopath_id)
            $q->andWhere(['or',
                              ['homoeopath_id'=> null ],
                              ['homoeopath_id'=> $this->homoeopath_id ]]);
        else
            $q->andWhere(['homoeopath_id'=> null ]);

        if($q->exists())
            $this->addError($attr, "すでに休業日となっています");

        $q = Interview::find()->active()->andWhere(['itv_date'=>$this->date]);
        if($this->homoeopath_id)
            $q->andWhere(['homoeopath_id'=>$this->homoeopath_id]);

        if($q->exists())
            $this->addError($attr, "相談会が設定されている日は休業にできません");

        return $this->hasErrors($attr);
    }
}

class HolidayQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['active'=>1]);
    }
}
