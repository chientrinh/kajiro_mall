<?php

namespace common\models\ecorange;

use Yii;

/**
 * This is the model class for table "{{%dtb_customer}}".
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/Customer.php $
 * @version $Id: Customer.php 1532 2015-09-21 19:49:57Z mori $
 *
 * @property integer $customer_id
 * @property string $name01
 * @property string $name02
 * @property string $kana01
 * @property string $kana02
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
 * @property string $fax01
 * @property string $fax02
 * @property string $fax03
 * @property integer $contact_tel_kbn
 * @property string $contact_tel01
 * @property string $contact_tel02
 * @property string $contact_tel03
 * @property integer $sex
 * @property integer $job
 * @property string $birth
 * @property string $password
 * @property integer $reminder
 * @property string $reminder_answer
 * @property string $secret_key
 * @property string $first_buy_date
 * @property string $last_buy_date
 * @property string $buy_times
 * @property string $buy_total
 * @property string $point
 * @property string $note
 * @property integer $status
 * @property string $create_date
 * @property string $update_date
 * @property integer $del_flg
 * @property string $cell01
 * @property string $cell02
 * @property string $cell03
 * @property string $mobile_phone_id
 * @property integer $mailmaga_flg
 * @property integer $shop_id
 * @property integer $paygent_card
 * @property integer $crank_id
 * @property integer $alcoholic_flg
 * @property integer $credit_customer_flg
 * @property integer $dm_send_flg
 * @property integer $real_use_flg
 * @property integer $black_customer_flg
 * @property string $black_customer_memo
 * @property string $bank_code
 * @property string $bank_branch_code
 * @property integer $cst_withdrawal_day
 * @property integer $cst_deposit_kbn
 * @property string $cst_deposit_num
 * @property string $cst_deposit_name
 * @property integer $cst_withdrawal_price
 * @property string $customer_code
 * @property string $entry_date
 * @property string $expire_date
 */
class Customer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ecOrange');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name01', 'name02', 'kana01', 'kana02', 'secret_key', 'create_date', 'shop_id'], 'required'],
            [['name01', 'name02', 'kana01', 'kana02', 'zip01', 'zip02', 'addr01', 'addr02', 'email', 'email_mobile', 'tel01', 'tel02', 'tel03', 'fax01', 'fax02', 'fax03', 'contact_tel01', 'contact_tel02', 'contact_tel03', 'password', 'reminder_answer', 'note', 'cell01', 'cell02', 'cell03', 'mobile_phone_id', 'black_customer_memo', 'cst_deposit_name', 'customer_code'], 'string'],
            [['pref', 'contact_tel_kbn', 'sex', 'job', 'reminder', 'status', 'del_flg', 'mailmaga_flg', 'shop_id', 'paygent_card', 'crank_id', 'alcoholic_flg', 'credit_customer_flg', 'dm_send_flg', 'real_use_flg', 'black_customer_flg', 'cst_withdrawal_day', 'cst_deposit_kbn', 'cst_withdrawal_price'], 'integer'],
            [['birth', 'first_buy_date', 'last_buy_date', 'create_date', 'update_date', 'entry_date', 'expire_date'], 'safe'],
            [['buy_times', 'buy_total', 'point'], 'number'],
            [['secret_key'], 'string', 'max' => 50],
            [['bank_code'], 'string', 'max' => 4],
            [['bank_branch_code'], 'string', 'max' => 3],
            [['cst_deposit_num'], 'string', 'max' => 8],
            [['secret_key'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id' => 'Customer ID',
            'name01' => 'Name01',
            'name02' => 'Name02',
            'kana01' => 'Kana01',
            'kana02' => 'Kana02',
            'zip01' => 'Zip01',
            'zip02' => 'Zip02',
            'pref' => 'Pref',
            'addr01' => 'Addr01',
            'addr02' => 'Addr02',
            'email' => 'Email',
            'email_mobile' => 'Email Mobile',
            'tel01' => 'Tel01',
            'tel02' => 'Tel02',
            'tel03' => 'Tel03',
            'fax01' => 'Fax01',
            'fax02' => 'Fax02',
            'fax03' => 'Fax03',
            'contact_tel_kbn' => '連絡先番号種別',
            'contact_tel01' => '連絡先番号1',
            'contact_tel02' => '連絡先番号2',
            'contact_tel03' => '連絡先番号3',
            'sex' => 'Sex',
            'job' => 'Job',
            'birth' => 'Birth',
            'password' => 'Password',
            'reminder' => 'Reminder',
            'reminder_answer' => 'Reminder Answer',
            'secret_key' => 'Secret Key',
            'first_buy_date' => 'First Buy Date',
            'last_buy_date' => 'Last Buy Date',
            'buy_times' => 'Buy Times',
            'buy_total' => 'Buy Total',
            'point' => 'Point',
            'note' => 'Note',
            'status' => 'Status',
            'create_date' => 'Create Date',
            'update_date' => 'Update Date',
            'del_flg' => 'Del Flg',
            'cell01' => 'Cell01',
            'cell02' => 'Cell02',
            'cell03' => 'Cell03',
            'mobile_phone_id' => 'Mobile Phone ID',
            'mailmaga_flg' => 'Mailmaga Flg',
            'shop_id' => 'Shop ID',
            'paygent_card' => 'Paygent Card',
            'crank_id' => 'Crank ID',
            'alcoholic_flg' => '酒類免許有無',
            'credit_customer_flg' => '売掛先有無',
            'dm_send_flg' => 'DM送信有無',
            'real_use_flg' => '店頭のみ利用可否',
            'black_customer_flg' => 'ブラック顧客',
            'black_customer_memo' => 'ブラック顧客メモ',
            'bank_code' => '引落金融機関',
            'bank_branch_code' => '引落支店番号',
            'cst_withdrawal_day' => '振替日',
            'cst_deposit_kbn' => '預金種目',
            'cst_deposit_num' => '口座番号',
            'cst_deposit_name' => '預金者名',
            'cst_withdrawal_price' => '振替金額',
            'customer_code' => 'Customer Code',
            'entry_date' => 'Entry Date',
            'expire_date' => 'Expire Date',
        ];
    }

    public function afterFind()
    {
//        if($this->customer_id == 84)
//            throw new \yii\base\Exception('test');
    }
    /* @brief search the similar customer from EC-CUBE
     */
    public function getAnotherModel()
    {
        return null;

        /**
         * do not search related model from HE to TY;
         * it will be taken care by elsewhere
         * 2015.09.20 mori
         */
        if(! $this->email && ! $this->tel01)
            return null;

        $query = \common\models\eccube\Customer::find()
           ->andFilterWhere(['OR',
                             ['email'                     => $this->email],
                             ['concat(tel01,tel02,tel03)' => $this->tel01.$this->tel02.$this->tel03],
                             ['concat(fax01,fax02,fax03)' => $this->tel01.$this->tel02.$this->tel03],
           ])
           ->andWhere("email <> ''")
           ->andWhere("concat(tel01,tel02,tel03) <> ''")
           ->andWhere(['status' => 3 /* 会員とようけ */])
           ->andWhere(['del_flg'=> 0 /* 有効 */]);

        $cnt = $query->count();
        if(0 == $cnt)
            return null;
        if(1 == $cnt)
            return $query->one();

        $query->andWhere(['name01' => $this->name01])
              ->andWhere(['name02' => $this->name02]);

        if(1 == $query->count())
            return $query->one();
                  
        return null;
    }

    public function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_HE);
    }

    /**
     * @brief  getting compatibile with webdb{18,20}/CustomerForm
     * @return integer | null
     */
    public function getCustomerid()
    {
        return $this->customer_id;
    }

    /**
     * @brief  getting compatibile with webdb{18,20}/CustomerForm
     * @return static string
     */
    public function getPref_id()
    {
        return $this->pref;
    }

    /**
     * @brief  getting compatibile with webdb{18,20}/CustomerForm
     * @return static string
     */
    public function getSchema()
    {
        return 'ecorange';
    }

    public function getOffice()
    {
        return null;
    }

    /**
     * @brief  getting compatibile with webdb{18,20}/CustomerForm
     * @return static string
     */
    public function getSex_id()
    {
        return $this->sex;
    }

    /* @return array */
    public function migrateAttributes()
    {
        return $this->attributes;
    }

    /* @return bool */
    public function wasMigrated()
    {
        $value = Yii::$app->db->createCommand("
SELECT customer_id from mtb_membercode where directive = 'ecOrange' and migrate_id = :mid
            ")
                 ->bindValues([':mid' => $this->customer_id])
                 ->queryScalar();

        if(0 < $value)
            return true;

        return false; // not migrated
    }

}
