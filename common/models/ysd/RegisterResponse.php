<?php

namespace common\models\ysd;

use Yii;

/**
 * This is the model class for table "register_response".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/RegisterResponse.php $
 * $Id: RegisterResponse.php 2262 2016-03-18 04:27:37Z mori $
 *
 */
class RegisterResponse extends \yii\db\ActiveRecord
{
    const CHARSET_FEED = 'SJIS-WIN';

    public $corp = '801255';
    public $div1 = '2';
    public $div2 =  0 ;

    public $bankcd   = null;
    public $bankname = null;
    public $brcd     = null;
    public $brname   = null;
    public $acitem   = null;
    public $acno     = null;
    public $ackana   = null;
    public $acname   = null;
    public $custzip  = null;
    public $custtel  = null;
    public $custaddr = null;
    public $rsv1     = null;
    public $rsv2     = null;
    public $rsv3     = null;
    public $rsv4     = null;
    public $rsv5     = null;
    public $rsv6     = null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'register_response';
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
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
            ],
            'staff' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => null,
            ],
            'account' => [
                'class' => FixAccount::className(),
                'owner' => $this,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cdate','custno','custkana','custname','errcd'], 'required'],
            [['cdate','custno','errcd'], 'integer'],

            [['custno'],     'exist','targetClass'=>RegisterRequest::className(), 'targetAttribute'=>'userno'],
            [['created_by'], 'exist','targetClass'=>\backend\models\Staff::className(), 'targetAttribute'=>'staff_id'],
            [['custno'], 'exist','targetClass'=>RegisterRequest::className(), 'targetAttribute'=>'userno'],
            [['custno'], 'unique', 'targetAttribute' => ['cdate','custno']],
            [['custkana','custname'], 'string','min'=>1, 'max'=>40],

            [['corp'  ], 'in', 'range' => ['801255']],
            [['errcd' ], 'in', 'range' => ['00'    ]],
            [['div1'  ], 'in', 'range' => ['2'     ]],
            [['div2'  ], 'integer', 'min' => 0, 'max' => 99],

            [['bankcd','brcd','acitem','acno','custzip','custtel'], 'mustBeEmpty'],
            [['bankname','brname','ackana','acname'],               'mustBeEmpty'],
            [['rsv1','rsv2','rsv3','rsv4','rsv5','rsv6'],           'mustBeEmpty'],
        ];
    }

    public function mustBeEmpty($attr, $params)
    {
        if(! $this->$attr)
            return true;

        $this->addError($attr, "{$attr} is not empty");

        return false;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rrs_id'   => 'Rrs ID',
            'cdate'    => '収納機関 取扱日',
            'div1'     => 'レコード区分',
            'corp'     => '委託者コード',
            'div2'     => '区分',
            'custno'   => '顧客番号',
            'bankcd'   => '振替銀行番号',
            'bankname' => '振替銀行名',
            'brcd'     => '振替銀行支店番号',
            'brname'   => '振替銀行支店名',
            'acitem'   => '預金種別',
            'acno'     => '口座番号',
            'ackana'   => '口座名義カナ',
            'acname'   => '口座名義氏名',
            'custkana' => '顧客カナ',
            'custname' => '顧客氏名',
            'custzip'  => '顧客〒',
            'custaddr' => '顧客住所',
            'custtel'  => '顧客TEL',
            'errcd'    => '不備フラグ',
            'rsv1'     => '予備1',
            'rsv2'     => '予備2',
            'rsv3'     => '予備3',
            'rsv4'     => '予備4',
            'rsv5'     => '予備5',
            'rsv6'     => '予備6',

            'created_at' => '入力日時',
            'created_by' => '入力者',
        ];
    }

    public function feed($line)
    {
        $line = mb_convert_encoding($line, $this->db->charset, self::CHARSET_FEED);
        $buf  = explode(',', rtrim($line));
        
        $this->div1     = array_shift($buf);
        $this->corp     = array_shift($buf);
        $this->div2     = array_shift($buf);
        $this->custno   = array_shift($buf);
        $this->bankcd   = array_shift($buf);
        $this->bankname = array_shift($buf);
        $this->brcd     = array_shift($buf);
        $this->brname   = array_shift($buf);
        $this->acitem   = array_shift($buf);
        $this->acno     = array_shift($buf);
        $this->ackana   = array_shift($buf);
        $this->acname   = array_shift($buf);
        $this->custkana = array_shift($buf);
        $this->custname = array_shift($buf);
        $this->custzip  = array_shift($buf);
        $this->custaddr = array_shift($buf);
        $this->custtel  = array_shift($buf);
        $this->errcd    = array_shift($buf);
        $this->rsv1     = array_shift($buf);
        $this->rsv2     = array_shift($buf);
        $this->rsv3     = array_shift($buf);
        $this->rsv4     = array_shift($buf);
        $this->rsv5     = array_shift($buf);
        $this->rsv6     = array_shift($buf);

        return $this->validate();
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new RegisterResponseQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(),['staff_id'=>'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(\common\models\Customer::className(),['customer_id'=>'custno']);
    }

}

class RegisterResponseQuery extends \yii\db\ActiveQuery
{
    public function newest()
    {
        return $this->orderBy(['created_at'=> SORT_DESC,
                               'cdate'     => SORT_DESC]);
    }
}

class FixAccount extends \yii\base\Behavior
{
    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
        ];
    }

    /* @return void */
    public function afterInsert($event)
    {
        if($this->owner instanceof RegisterResponse)
            $cid = $this->owner->custno;

        if(! isset($cid))
            // i don't know how to handle without customer_id
            return;

        if(! $this->owner->validate())
            // do nothing for invalid model
            return;

        $this->updateAccount($cid);
    }

    /* @return void */
    private function updateAccount($customer_id)
    {
        if(!$account = Account::findOne(['customer_id' => $customer_id]))
            $account = new Account(['customer_id' => $customer_id]);

        $account->expire_id = AccountStatus::PKEY_VALID;

        if(! $account->save())
            Yii::error([
                sprintf('saving Account failed for customer_id(%d)', $customer_id),
                $account->errors,
            ], self::className().'::'.__FUNCTION__);
    }

}
