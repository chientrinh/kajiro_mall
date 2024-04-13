<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_invoice_status".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/InvoiceStatus.php $
 * $Id: InvoiceStatus.php 2140 2016-02-24 06:09:33Z mori $
 *
 * @property integer $istatus_id
 * @property string $name
 */
class InvoiceStatus extends \yii\db\ActiveRecord
{
    const PKEY_VOID      =  0;
    const PKEY_ACTIVE    =  1;
    const PKEY_PAID      =  9;
    const PKEY_FORWARDED = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_invoice_status';
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
