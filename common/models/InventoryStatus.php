<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_inventory_status".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/InventoryStatus.php $
 * $Id: InventoryStatus.php 2046 2016-02-06 02:56:00Z mori $
 *
 * @property integer $istatus_id
 * @property string $name
 */
class InventoryStatus extends \yii\db\ActiveRecord
{
    const PKEY_INIT     = 1;
    const PKEY_SUBMIT   = 2;
    const PKEY_APPROVED = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_inventory_status';
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
            'istatus_id' => 'Istatus ID',
            'name' => 'Name',
        ];
    }
}
