<?php

namespace common\models\ysd;

use Yii;
use \yii\helpers\ArrayHelper;
use \yii\helpers\Json;
use \common\models\Customer;

/**
 * This is the model class for table "register_request".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/RegisterRequest.php $
 * $Id: RegisterRequest.php 2233 2016-03-12 00:35:40Z mori $
 *
 * @property integer $rrq_id
 * @property integer $userno
 * @property string  $seikanji
 * @property string  $meikanji
 * @property string  $seikana
 * @property string  $meikana
 * @property integer $birthday
 * @property string  $mailaddr
 * @property integer $ip
 * @property integer $created_at
 * @property string  $feedback
 * @property string  $emsg
 */
class RegisterRequest extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'register_request';
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
            'timestamp'=>[
                'class' => \yii\behaviors\TimestampBehavior::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userno', 'seikanji', 'meikanji', 'seikana', 'meikana', 'birthday', 'mailaddr'], 'required'],
            [['birthday'], 'filter', 'filter'=>function($value){ return preg_replace('/( .*)|[-]/', '', $value); }],
            [['userno', 'birthday'], 'integer'],

            ['mailaddr', 'email'],
            ['mailaddr', 'string', 'max' => 200],
            [['seikanji', 'meikanji', 'seikana', 'meikana'], 'string', 'max' => 33],
            [['seikanji', 'meikanji'], 'sjisOnly'],
            [['seikana', 'meikana'],   'kanaOnly'],
            [['feedback'],'string','max'=> 2 ],
            [['emsg'],'string'],
            ['userno','exist','targetClass'=>Customer::className(),'targetAttribute'=>'customer_id'],
            ['userno','validateCustomer'],
            [['ip'],'string','min'=>'3','max'=>'39'/* ipv6 ready */],
            [['created_at'],'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rrq_id'     => 'Rrq ID',
            'userno'     => '顧客ID',
            'seikanji'   => '顧客姓(漢)',
            'meikanji'   => '顧客名(漢)',
            'seikana'    => '顧客姓(カナ)',
            'meikana'    => '顧客名(カナ)',
            'birthday'   => '生年月日',
            'mailaddr'   => 'メールアドレス',
            'ip'         => '要求元IP',
            'created_at' => '受付日時',
            'updated_at' => '更新日時',
            'feedback'   => 'YSD応答',
            'emsg'       => 'YSDエラー文字列',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::className(),['customer_id'=>'userno']);
    }

    /*
     * @return array of UTF-8 string
     */
    public function getPostData()
    {
        $this->validate();

        return [
            'CORPCD'   => null,
            'PASSWORD' => null,
            'SVCNO'    => null,
            'USERNO'   => sprintf('%010d', $this->userno),
            'SEIKANJI' => $this->seikanji,
            'SEIKANA'  => $this->seikana,
            'MEIKANJI' => $this->meikanji,
            'MEIKANA'  => $this->meikana,
            'ZIPCD'    => '', // 実際のところ送信しても一切参照されることがないので省略してよい @ 2016.01.15 YSD打合せ
            'TELNO'    => '', // 〃
            'ADDR'     => '', // 〃
            'BIRTHDAY' => $this->birthday,
            'MAILADDR' => $this->mailaddr,
        ];
    }

    public function kanaOnly($attr, $param)
    {
        $value = $this->$attr;

        $value = mb_convert_kana($value, 'CKV');
        $value = preg_replace('/[^ァ-ヶ]/u', '', $value); // カナ以外を消去する
        
        $this->$attr = $value;
    }

    public function sjisOnly($attr, $param)
    {
        $value = $this->$attr;

        // いちどShiftJISに変換して、元に戻す
        $value = mb_convert_encoding($value, 'SJIS', Yii::$app->charset);
        $value = mb_convert_encoding($value, Yii::$app->charset, 'SJIS');

        $this->$attr = $value;
    }

    /* @return $model */
    public static function startup(Customer $customer)
    {
        $model = new RegisterRequest(['userno'   => $customer->id,
                                      'seikanji' => $customer->name01,
                                      'seikana'  => $customer->kana01,
                                      'meikanji' => $customer->name02,
                                      'meikana'  => $customer->kana02,
                                      'birthday' => $customer->birth,
                                      'mailaddr' => $customer->email,
        ]);

        if(Yii::$app instanceof \yii\web\Application)
            $model->ip = Yii::$app->request->userIP;

        return $model;
    }

    /* @return bool */
    public function parseResponse($httpBody)
    {
        $this->feedback = 'er'; // which means 'error'

        try
        {
            $param = Json::decode($httpBody);
        }
        catch(\yii\base\InvalidParamException $e)
        {
            $this->emsg = $httpBody;
            return false;
        }

        if(! is_array($param))
        {
            $model->emsg = $httpBody;
            return false;
        }

        $this->feedback = ArrayHelper::getValue($param,'p_result', $this->feedback);
        $this->emsg     = ArrayHelper::getValue($param,'p_error_message', $httpBody);
        $userno         = ArrayHelper::getValue($param,'p_user_no');

        if($userno != $this->userno)
        {
            $emsg = sprintf('Register Request Server responded in different userno: (%s != %s)',
                            $this->userno, $userno);

            Yii::error($emsg, 'FATAL');
            $this->emsg .= $emsg;
        }

        return in_array($this->feedback, ['ok','ng']);
    }

    public function validateCustomer($attr, $params)
    {
        if(! $c = $this->getCustomer()->one())
            return false;

        if($c->isExpired())
            $this->addError($attr, '当該の顧客は無効です');

        return $this->hasErrors($attr);
    }

}
