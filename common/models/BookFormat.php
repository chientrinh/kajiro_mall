<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/BookFormat.php $
 * $Id: BookFormat.php 2722 2016-07-15 08:38:22Z mori $
 */

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_book_format".
 *
 * @property integer $format_id
 * @property string $name
 */
class BookFormat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_book_format';
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
            'format_id' => "判型ID",
            'name'      => "判型",
        ];
    }
}
