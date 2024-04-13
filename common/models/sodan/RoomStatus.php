<?php

namespace common\models\sodan;

use Yii;

/**
 * This is the model class for table "mtb_sodan_room_status".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/RoomStatus.php $
 * $Id: RoomStatus.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $status_id
 * @property string $name
 */
class RoomStatus extends \yii\db\ActiveRecord
{
    const PKEY_WAITING  =-1;
    const PKEY_VACANT   = 0;
    const PKEY_OCCUPIED = 1;
    const PKEY_CANCEL   = 8;
    const PKEY_VOID     = 9;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_sodan_room_status';
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
            'name'      => '状態',
        ];
    }
}
