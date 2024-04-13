<?php

namespace common\models\sodan;

use Yii;

/**
 * This is the model class for table "dtb_sodan_qnr".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/Questionnaire.php $
 * $Id: Questionnaire.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $qnr_id
 * @property integer $client_id
 * @property string $create_date
 * @property string $update_date
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $status_id
 * @property string $complaint
 * @property resource $data
 *
 * @property MtbStaff $updatedBy
 * @property MtbStaff $createdBy
 * @property MtbSodanStatus $status
 */
class Questionnaire extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_qnr';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'client' => [
                'class' => InitClient::className(),
                'owner' => $this,
            ],
            'update' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'staff_id' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by','updated_by'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_by'],
                ],
                'value' => function ($event) {
                    if(! Yii::$app->get('user',false) || ! Yii::$app->user->identity instanceof \backend\models\Staff)
                        return null;

                    return Yii::$app->user->id;
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
            [['client_id', 'create_date', 'update_date', 'created_by', 'updated_by', 'status_id'], 'required'],
            [['client_id', 'created_by', 'updated_by', 'status_id'], 'integer'],
            [['create_date', 'update_date'], 'safe'],
            [['client_id'], 'exist', 'targetClass' => \common\models\Customer::className(), 'targetAttribute'=>'customer_id'],
            [['complaint', 'data'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'qnr_id' => '質問票ID',
            'client_id' => 'クライアント',
            'create_date' => '起票日',
            'update_date' => '更新日',
            'created_by' => '起票者',
            'updated_by' => '更新者',
            'status_id' => '状態',
            'complaint' => '主訴',
            'data' => 'クライアント回答',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'updated_by']);
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
    public function getStatus()
    {
        return $this->hasOne(SodanStatus::className(), ['status_id' => 'status_id']);
    }

    /**
     * @inheritdoc
     * @return QuestionnaireQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new QuestionnaireQuery(get_called_class());
    }
}

/**
 * This is the ActiveQuery class for [[Questionnaire]].
 *
 * @see Questionnaire
 */
class QuestionnaireQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return Questionnaire[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Questionnaire|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
