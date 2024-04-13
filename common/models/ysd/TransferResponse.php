<?php

namespace common\models\ysd;

use Yii;

/**
 * This is the model class for table "transfer_response".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/TransferResponse.php $
 * $Id: TransferResponse.php 3843 2018-03-14 09:14:15Z mori $
 *
 * @property integer $trs_id
 * @property integer $custno - customer id
 * @property integer $bankcd - bank code
 * @property integer $brcd   - branch code
 * @property integer $acitem - 当座・普通
 * @property integer $acno   - account number
 * @property string  $acname - account name
 * @property integer $charge - amount billed
 * @property integer $pre    - premier
 * @property string  $cdate  - closing date
 * @property string  $rdate  - request date
 * @property integer $stt    - status
 * @property integer $created_at
 */
class TransferResponse extends \yii\db\ActiveRecord
{
    const CHARSET_FEED = 'SJIS-WIN';

        
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer_response';
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
            [['custno', 'bankcd', 'brcd', 'acitem', 'acno', 'acname', 'charge', 'pre', 'stt', 'cdate', 'rdate', 'created_at'], 'required'],
            [['custno', 'bankcd', 'brcd', 'acitem', 'acno', 'charge', 'pre', 'stt', 'created_at'], 'integer'],
            [['cdate', 'rdate'], 'safe'],
            [['acname'], 'string', 'max' => 255],
            ['custno', 'unique', 'targetAttribute' => ['custno','rdate']],
            [['created_by'], 'exist','targetClass'=>\backend\models\Staff::className(), 'targetAttribute'=>'staff_id'],
            [['stt'], 'exist', 'targetClass'=>TransferStatus::className(), 'targetAttribute'=>'stt_id'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'trs_id'     => 'Trs ID',
            'custno'     => '顧客ID',
            'bankcd'     => '銀行コード',
            'brcd'       => '支店コード',
            'acitem'     => '預金種目',
            'acno'       => '口座番号',
            'acname'     => '口座名義人名',
            'charge'     => '振替金額',
            'pre'        => '初回引き渡し',
            'stt'        => 'ステータス',
            'cdate'      => '請求締め日',
            'rdate'      => '振替依頼日',
            'created_at' => '入力日時',
            'created_by' => '入力者',
        ];
    }

    public function feed($line)
    {
//        $bytes = [1, 8, 6, 15, 2, 20, 4, 5, 1, 8, 30, 10, 8, 6, 15, 1, 2, 1, 207];
//        $buf   = [];
//
//        if(strlen($line) !== array_sum($bytes))
//            return false;
//
//        $pos = 0;
//        foreach($bytes as $byte)
//        {
//            $buf[] = substr($line, $pos, $byte);
//            $pos  += $byte;
//        }
//
//        if('2' !== $buf[0]) { return false; }
//
//        $this->bdate   = $buf[1];
//        $this->btime   = $buf[2];
//        $this->bsvcno  = $buf[3];
//        $this->chl     = $buf[4];
//        $this->custno  = $buf[5];
//        $this->bankcd  = $buf[6];
//        $this->brcd    = $buf[7];
//        $this->acitem  = $buf[8];
//        $this->acno    = $buf[9];
//        $this->acname  = mb_convert_encoding($buf[10], $this->db->charset, self::CHARSET_FEED);
//        $this->secno   = $buf[11];
//        $this->cdate   = $buf[12];
//        $this->ctime   = $buf[13];
//        $this->csvcno  = $buf[14];
//        $this->stt     = $buf[15];
//        $this->rscd    = $buf[16];
//        $this->acnmchk = $buf[17];
//
//        if('' !== trim($buf[18])) { return false; }

        $csv = explode(',', $line);
        if('2' !== $csv[0]) { return false; }

        $this->acname  = mb_convert_encoding($csv[23], $this->db->charset, self::CHARSET_FEED);
        $this->cdate   = $csv[29];
        $this->stt     = $csv[17];
        $this->custno = $csv[5];
        $this->pre = $csv[16];
        $this->charge = $csv[15];
        $this->created_at = time();

// これらのレコードを保持することは無い。口座情報はあくまでYSD側が持つ        
//                            'bankcd'     => '銀行コード',
//            'brcd'       => '支店コード',
//            'acitem'     => '預金種目',
//            'acno'       => '口座番号',
        $this->acitem = 0;
        $this->bankcd = 0;
        $this->brcd = 0;
        $this->acno = 0;

        return $this->validate();
    }

    public function getBank()
    {
        return 0;
    }

    public function getAc()
    {
        return 0;
    }

    public function getCustomer()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id'=>'custno']);
    }

    public function getRequest()
    {
        return $this->hasOne(TransferRequest::className(), [
            'custno' => 'custno',
            'pre'    => 'pre',
            'cdate'  => 'cdate',
            'charge' => 'charge',
        ]);
    }

    public function getStatus()
    {
        return $this->hasOne(TransferStatus::className(), ['stt_id'=>'stt']);
    }

    public function isPaid()
    {
        return $this->stt == TransferStatus::PKEY_PAID;
    }
    
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(),['staff_id'=>'created_by']);
    }


}
