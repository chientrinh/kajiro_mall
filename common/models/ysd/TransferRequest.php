<?php

namespace common\models\ysd;

use Yii;
use \common\models\Customer;
use \common\models\Invoice;

/**
 * This is the model class for table "transfer_request".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/TransferRequest.php $
 * $Id: TransferRequest.php 3823 2018-02-02 00:51:16Z kawai $
 *
 * @property integer $trq_id
 * @property string  $cdate  - closing date
 * @property integer $custno - customer id
 * @property integer $charge - amount billed
 * @property integer $pre    - premier
 * @property integer $created_at
 */
class TransferRequest extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transfer_request';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ysd');
    }

    public function afterFind()
    {
        // cast to int(10) zerofill
        $this->custno = sprintf('%010d',$this->custno);
        // @see http://stackoverflow.com/questions/34480872/yii2-active-record-casts-zerofill-column-to-int-losing-zeros

        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pre'], 'validatePremier', 'skipOnEmpty'=>false ],
            [['cdate', 'custno', 'charge', 'pre', 'created_at'], 'required'],
            [['custno', 'charge', 'pre', 'created_at'], 'integer'],
            [['custno'], 'exist', 'targetClass' => Customer::className(), 'targetAttribute' => 'customer_id'],
            [['charge'], 'integer', 'min' => 1 ],
//            [['charge'], 'exist', 'targetClass' => Invoice::className(), 'targetAttribute' => ['charge'=>'due_total','cdate'=>'target_date','custno'=>'customer_id'],'message'=>'{attribute}が当月の請求書と一致しません'], // 未落ちデータを取り込む関係でこれは不要となった
            [['pre'], 'in', 'range'=> [0, 1], 'skipOnEmpty'=>false ],
            [['custno'],'unique','targetAttribute'=>['custno','cdate']],
            [['cdate'], 'date'],
//            [['created_at'], 'safe'],
        ];
    }

    /**
     * set default value to this->pre
     */
    public function validatePremier($attr, $param)
    {
        if(! $this->custno)
            $this->addError($attr,"custno must be set to define $attr");

        $query = self::find()->andWhere(['custno' => $this->custno])
                             ->andWhere(['<', 'cdate', $this->cdate]);

        if(! $query->exists())
            $this->pre = 1; // YSD口座を登録後、初めて請求依頼を発行する
        else
            $this->pre = 0;

        return $this->hasErrors($attr);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'trq_id'  => 'Trq ID',
            'cdate'   => '請求締め日',
            'custno'  => '顧客ID',
            'charge'  => '振替金額',
            'pre'     => '初回引き渡し',
            'created_at' => '依頼日時',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::className(),['customer_id' => 'custno']);
    }

    public function getResponse()
    {
        return $this->hasOne(TransferResponse::className(),[
            'custno' => 'custno',
            'pre'    => 'pre',
            'cdate'  => 'cdate',
            'charge' => 'charge',
        ]);
    }
}
