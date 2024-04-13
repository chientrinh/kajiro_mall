<?php

namespace common\models\sodan;

use Yii;

/**
 * This is the model class for table "mtb_sodan_status".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/InterviewStatus.php $
 * $Id: InterviewStatus.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $status_id
 * @property string $name
 *
 * @property Interview[] $interviews
 * @property Questionnaire[] $questionnaires
 */
class InterviewStatus extends \yii\db\ActiveRecord
{
    const PKEY_VACANT   = 0;  // 予約待ち
    const PKEY_READY    = 1;  // 予約済み
    const PKEY_ONGOING  = 2;  // 相談中
    const PKEY_DONE     = 3;  // 相談完了
    const PKEY_KARUTE_DONE = 4;  // カルテ完了
    const PKEY_CANCEL   = 8;  // 予約キャンセル
    const PKEY_VOID     = 9;  // 無効

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_sodan_status';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'status_id' => '状態ID',
            'name'      => '名称',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInterviews()
    {
        return $this->hasMany(Interview::className(), ['status_id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionnaires()
    {
        return $this->hasMany(Questionnaire::className(), ['status_id' => 'status_id']);
    }
}
