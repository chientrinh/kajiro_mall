<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tblfriendship".
 *
 * @property integer $friendshipid
 * @property string $dateofcontinuation
 * @property string $dateofadmission
 * @property integer $admissionid
 * @property integer $customerid
 * @property integer $demarcationid
 * @property integer $sentoasysno
 * @property string $oasyssendway
 * @property string $oasyssenddate
 * @property string $oasyssenddenpyono
 * @property string $toranokowithdrawdate
 * @property string $toranokowithdrawreason
 * @property string $toranokolastupdate
 * @property string $nyukin_date
 * @property string $login_name
 * @property integer $account_typeid
 *
 * @property Tblcustomer $customer
 * @property Tmadmission $admission
 * @property Tmdemarcation $demarcation
 */
class Friendship extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblfriendship';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb18');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['dateofcontinuation', 'dateofadmission', 'oasyssendway', 'oasyssenddate', 'oasyssenddenpyono', 'toranokowithdrawdate', 'toranokowithdrawreason', 'toranokolastupdate', 'nyukin_date', 'login_name'], 'string'],
            [['admissionid', 'customerid', 'demarcationid', 'sentoasysno', 'account_typeid'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'friendshipid' => 'Friendshipid',
            'dateofcontinuation' => 'Dateofcontinuation',
            'dateofadmission' => 'Dateofadmission',
            'admissionid' => 'Admissionid',
            'customerid' => 'Customerid',
            'demarcationid' => 'Demarcationid',
            'sentoasysno' => 'Sentoasysno',
            'oasyssendway' => 'Oasyssendway',
            'oasyssenddate' => 'Oasyssenddate',
            'oasyssenddenpyono' => 'Oasyssenddenpyono',
            'toranokowithdrawdate' => 'Toranokowithdrawdate',
            'toranokowithdrawreason' => 'Toranokowithdrawreason',
            'toranokolastupdate' => 'Toranokolastupdate',
            'nyukin_date' => 'Nyukin Date',
            'login_name' => 'Login Name',
            'account_typeid' => 'Account Typeid',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'customerid']);
    }

}
