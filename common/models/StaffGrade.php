<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_staff_grade".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/StaffGrade.php $
 * $Id: StaffGrade.php 2722 2020-07-29 08:38:22Z kawai $
 *
 * @property integer $staff_grade_id
 * @property string $name
 *
 */
class StaffGrade extends \yii\db\ActiveRecord
{
    const PKEY_OF = 1; // officer 役員
    const PKEY_RE = 2; // Regular Employee 正社員
    const PKEY_CE = 3; // Contract Employee 契約社員
    const PKEY_PT = 4; // Part-time Job アルバイト

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_staff_grade';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->get('user') : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'staf_grade_id'   => '社員区分',
            'name'       => '社員区分名',
        ];
    }
}
