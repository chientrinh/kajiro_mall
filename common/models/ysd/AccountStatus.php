<?php

namespace common\models\ysd;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/AccountStatus.php $
 * $Id: AccountStatus.php 2262 2016-03-18 04:27:37Z mori $
 *
 * This is the model class for table "mtb_ysd_account_status".
 *
 * @property integer $expire_id
 * @property string $name
 *
 * @property DtbYsdAccount[] $dtbYsdAccounts
 */
class AccountStatus extends \yii\db\ActiveRecord
{
    const PKEY_ENTRY = -1;// 手続き中
    const PKEY_VALID =  0;// 有効
    const PKEY_WARN  =  8;// 警告
    const PKEY_VOID  =  9;// 無効

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_ysd_account_status';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['expire_id','name'], 'required'],
            [['expire_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'expire_id' => '無効フラグ',
            'name'      => '説明',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccounts()
    {
        return $this->hasMany(Account::className(), ['expire_id' => 'expire_id']);
    }
}
