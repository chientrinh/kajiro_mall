<?php

namespace common\models\sodan;

use Yii;
use backend\models\Staff;

/**
 * BookTemplate
 * 予約票のテンプレート管理 Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/BookTemplate.php $
 * $Id: BookTemplate.php 1876 2018-03-01 09:42:35 sado $
 */
class BookTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_book_template';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            'staff_id' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['template_id', 'created_by', 'updated_by'], 'integer'],
            [['body'], 'string', 'length'=>[0, 1024]],
            [['create_date', 'update_date'], 'safe'],
            [['created_by','updated_by'], 'exist', 'targetClass' => '\backend\models\Staff', 'targetAttribute' => 'staff_id'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'template_id' => 'ID',
            'body'        => "本文",
            'create_date' => '作成日時',
            'update_date' => '更新日時'
        ];
    }

    public function attributeHints()
    {
        return [
            'body'   => "予約票に表示する文言を入力してください。（改行は６行以内で入力してください）",
        ];
    }

    public function beforeSave($insert)
    {
        if(defined('YII_ENV') && YII_ENV == 'test')
            return;

        if(! Yii::$app instanceof \yii\web\Application ||
             Yii::$app->user->isGuest ||
           ! Yii::$app->user->identity instanceof \backend\models\Staff)
        {
            $this->detachBehavior('staff_id');
            $this->created_by = 0; // system@toyouke.com
            $this->updated_by = 0; // system@toyouke.com
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'created_by']);
    }
}
