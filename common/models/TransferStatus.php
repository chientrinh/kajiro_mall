<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/TransferStatus.php $
 * $Id: TransferStatus.php 2046 2016-02-06 02:56:00Z mori $
 *
 * This is the model class for table "mtb_transfer_status".
 *
 * @property integer $status_id
 * @property string $name
 *
 * @property DtbTransfer[] $dtbTransfers
 */
class TransferStatus extends \yii\db\ActiveRecord
{
    const PKEY_INIT     = 0;
    const PKEY_ASKED    = 1;
    const PKEY_POSTED   = 2;
    const PKEY_RECEIVED = 3;
    const PKEY_DONE     = 7;
    const PKEY_CANCEL   = 8;
    const PKEY_VOID     = 9;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_transfer_status';
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
            'status_id' => 'Status ID',
            'name' => 'Name',
        ];
    }

}
