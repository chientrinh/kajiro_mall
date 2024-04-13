<?php

namespace common\models\eccube;

use Yii;

/**
 * This is the model class for table "dtb_customer".
 *
 * @property integer $customer_id
 * @property integer $status
 * @property string $name01
 * @property string $name02
 * @property string $kana01
 * @property string $kana02
 * @property integer $sex
 * @property string $birth
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref
 * @property string $addr01
 * @property string $addr02
 * @property string $email
 * @property string $email_mobile
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 * @property string $mobile01
 * @property string $mobile02
 * @property string $mobile03
 * @property string $note
 * @property string $point
 * @property integer $mailmaga_flg
 * @property string $create_date
 * @property string $update_date
 * @property string $password
 * @property string $reminder_answer
 * @property integer $reminder
 * @property string $salt
 * @property string $secret_key
 * @property string $first_buy_date
 * @property string $last_buy_date
 * @property string $buy_times
 * @property string $buy_total
 * @property integer $del_flg
 * @property integer $country_id
 * @property string $zipcode
 * @property string $company_name
 * @property integer $job
 * @property string $fax01
 * @property string $fax02
 * @property string $fax03
 * @property string $mobile_phone_id
 */
class Customer extends \yii\db\ActiveRecord
{
    const AUTH_MAGIC = 'niacoliawiophouseasloutruraslujiasinedro';
    const ALGORITHM  = 'sha256';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ecCube');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['customer_id', 'secret_key'], 'required'],
            [['customer_id', 'status', 'sex', 'pref', 'mailmaga_flg', 'reminder', 'del_flg', 'country_id', 'job'], 'integer'],
            [['birth', 'create_date', 'update_date', 'first_buy_date', 'last_buy_date'], 'safe'],
            [['email_mobile', 'note', 'secret_key', 'zipcode', 'company_name', 'fax01', 'fax02', 'fax03', 'mobile_phone_id'], 'string'],
            [['point', 'buy_times', 'buy_total'], 'number'],
            [['name01', 'name02', 'kana01', 'kana02'], 'string', 'max' => 64],
            [['zip01'], 'string', 'max' => 3],
            [['zip02', 'tel02', 'tel03'], 'string', 'max' => 4],
            [['addr01', 'addr02', 'email', 'password', 'reminder_answer', 'salt'], 'string', 'max' => 255],
            [['tel01', 'mobile01', 'mobile02', 'mobile03'], 'string', 'max' => 5]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'status' => '会員種別',
            'name'   => 'お名前',
            'kana'   => 'ふりがな',
            'sex_id' => '性別',
            'birth'  => '誕生日',
            'addr'   => '住所',
            'email'  => 'メールアドレス',
            'tel'    => '電話',
            'point' => 'ポイント',
            'mailmaga_flg' => 'メルマガ送付について',
        ];
    }

    public function getAddr()
    {
        $pref = \common\models\Pref::findOne($this->pref);
            
        return sprintf('%s %s %s',
                       $pref ? $pref->name : '',
                       $this->addr01,
                       $this->addr02);
    }

    public function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_TY);
    }

    public function getCustomerid()
    {
        return $this->customer_id;
    }

    public function getName()
    {
        return sprintf('%s %s', $this->name01, $this->name02);
    }

    public function getKana()
    {
        return sprintf('%s %s', $this->kana01, $this->kana02);
    }

    public function getPref_id()
    {
        return $this->pref;
    }

    public function getTel()
    {
        return sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);
    }

    public static function getSchema()
    {
        return 'eccube';
    }

    public function getSex_id()
    {
        return $this->sex;
    }

    public function getZip()
    {
        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    public function migrateAttributes()
    {
        $attr = array_merge($this->attributes, [
            'membercode' => [
                'directive'  => $this->schema,
                'migrate_id' => $this->customer_id,
            ],
            'sex_id'     => $this->sex,
            'pref_id'    => $this->pref,
            'subscribe'  => $this->mailmaga_flg,
        ]);
        if (isset($attr['sex'])         ) unset($attr['sex']);
        if (isset($attr['pref'])        ) unset($attr['pref']);
        if (isset($attr['mailmaga_flg'])) unset($attr['mailmaga_flg']);

        if(3 == $this->status) // 会員とようけ
            $attr['memberships'] = [
                [
                    'membership_id' => \common\models\Membership::PKEY_TOYOUKE,
                    'satrt_date'    => $this->create_date,
                    'expire_date'   => \common\models\CustomerMembership::DATETIME_MAX,
                    'update_date'   => new \yii\db\Expression('NOW()'),
                ],
            ];

        return $attr;
    }

    public function validatePassword($str)
    {
        $seed = $str . ':' . self::AUTH_MAGIC;
        $hash = hash_hmac(self::ALGORITHM, $seed, $this->salt);

        return ($hash == $this->password);
    }

    /* @return bool */
    public function wasMigrated()
    {
        $membercode = \common\models\Membercode::find()->where([
            'directive'  => $this->schema,
            'migrate_id' => $this->customer_id,
        ])->one();

        if($membercode && (0 < $membercode->customer_id))
            return true;

        return false;
    }

}
