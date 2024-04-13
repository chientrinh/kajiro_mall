<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/BinaryStorage.php $
 * $Id: BinaryStorage.php 1777 2015-11-08 17:36:20Z mori $
 *
 * This is the model class for table "dtb_binary_storage".
 *
 * @property integer $file_id
 * @property string $tbl_name
 * @property integer $pkey
 * @property string $property
 * @property string $basename
 * @property string $type
 * @property integer $size
 * @property resource $data
 */
class BinaryStorage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_binary_storage';
    }

    public function behaviors()
    {
        return [
            'staff_id' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
            ],
            'update'   => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => function ($event) { return date('Y-m-d H:i:s'); },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tbl_name', 'pkey', 'property', 'basename', 'type', 'size', 'data'], 'required'],
            [['pkey', 'size'], 'integer'],
            [['data'], 'string'],
            [['tbl_name', 'property', 'basename', 'type'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'file_id'  => 'File ID',
            'tbl_name' => 'テーブル',
            'pkey'     => '主キー',
            'property' => '属性',
            'basename' => 'ファイル名',
            'type'     => 'MIME type',
            'size'     => 'サイズ',
            'data'     => 'Data',
        ];
    }

    public function attributeHints()
    {
        return [
            'property' => 'そのファイルにどんな情報が入っているのか、255字以内で説明してください',
        ];
    }

    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id'=>'created_by']);
    }

    public function getUpdator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id'=>'updated_by']);
    }

}
