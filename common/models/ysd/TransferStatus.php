<?php

namespace common\models\ysd;

use Yii;

/**
 * This is the model class for table "transefer_status".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/TransferStatus.php $
 * $Id: TransferStatus.php 1964 2016-01-11 07:05:04Z mori $
 *
 */
class TransferStatus extends \yii\db\ActiveRecord
{
    const PKEY_PAID      = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer_status';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ysd');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['stt_id','name'], 'required'],
            [['stt_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'stt_id' => '状態ID',
            'name'   => '名称',
        ];
    }

}
