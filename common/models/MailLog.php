<?php

namespace common\models;
use Yii;

/**
 * This is the model class for table "ltb_mailer".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/MailLog.php $
 * $Id: MailLog.php 2826 2016-08-10 02:54:38Z mori $
 *
 * @property integer $mailer_id
 * @property string $date
 * @property string $to
 * @property string $subject
 * @property string $body
 */
class MailLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ltb_mailer';
    }

    public function behaviors()
    {
        return [
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => null,
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
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
            [['to', 'subject', 'body'], 'required'],
            [['date'], 'safe'],
            [['body'], 'string'],
            [['to', 'sender', 'subject'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'mailer_id' => 'Mailer ID',
            'date'      => '日時',
            'to'        => '宛先',
            'sender'    => '差出人',
            'subject'   => '件名',
            'body'      => '本文',
            'tbl'       => '参照',
        ];
    }

    public function getNext()
    {
        return self::findOne($this->mailer_id + 1 );
    }

    public function getPrev()
    {
        return self::findOne($this->mailer_id - 1 );
    }
}
