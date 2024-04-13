<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tblcustomer".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/Customer.php $
 * $Id: Customer.php 1637 2015-10-11 11:12:30Z mori $
 *
 * @property integer $customerid
 * @property string $name
 * @property string $kana
 * @property integer $titleid
 * @property integer $sexid
 * @property string $birth
 * @property integer $birthweight
 * @property string $occupation
 * @property integer $sendid
 * @property string $email
 * @property string $contact
 * @property integer $netmember
 * @property integer $machine
 * @property string $entrydate
 * @property string $updatedate
 * @property integer $label
 * @property string $wireless
 * @property integer $customer_1_divisionid
 * @property integer $customer_2_divisionid
 * @property integer $meiboid
 * @property integer $nyukinid
 * @property integer $soryoid
 * @property integer $tanka_calid
 * @property integer $sell_limitid
 * @property integer $mailmagid
 * @property integer $c_printid
 * @property string $old_name
 * @property integer $free_mailid
 * @property string $login_name
 * @property integer $email_divisionid
 * @property string $url
 * @property integer $oasys_sendid
 * @property integer $text_sendid
 * @property string $email_mobile
 * @property string $passwd
 * @property integer $dm1_sendid
 * @property integer $dm2_sendid
 * @property integer $dm3_sendid
 * @property string $credit_num
 * @property string $cart_limit_month
 * @property string $cart_limit_year
 * @property string $secret_num
 * @property string $chk_dedit
 * @property string $card_limit_month
 * @property string $card_limit_year
 * @property string $card_name
 * @property string $card_kind
 * @property integer $agencyid
 * @property string $card_status_hj
 * @property string $card_status_hp
 * @property string $card_status_hi
 * @property string $card_status_he
 * @property string $card_status_uk
 * @property string $cart_info_fl
 *
 * @property TbladdrList[] $tbladdrLists
 * @property Tbladdress[] $tbladdresses
 * @property Tmcustomer1Division $customer1Division
 * @property Tmcustomer2Division $customer2Division
 * @property Tmsend $send
 * @property Tmsex $sex
 * @property Tmtitle $title
 * @property Tbldatarequest[] $tbldatarequests
 * @property Tbldenpyo[] $tbldenpyos
 * @property TbldenpyoLog[] $tbldenpyoLogs
 * @property Tblfamilymember[] $tblfamilymembers
 * @property Tblfriendship[] $tblfriendships
 * @property TblfriendshipHis[] $tblfriendshipHis
 * @property Tblfukuyo[] $tblfukuyos
 * @property TblhomoeClub[] $tblhomoeClubs
 * @property TblhomoeHisClub[] $tblhomoeHisClubs
 * @property Tblhomoeopath[] $tblhomoeopaths
 * @property Tblintroduce[] $tblintroduces
 * @property Tbljphma[] $tbljphmas
 * @property TbljphmaHis[] $tbljphmaHis
 * @property Tbljphms[] $tbljphms
 * @property Tblkoenkaireg[] $tblkoenkairegs
 * @property Tblnote[] $tblnotes
 * @property Tbloffice[] $tbloffices
 * @property Tbloldaddr[] $tbloldaddrs
 * @property Tblreservation[] $tblreservations
 * @property Tblsomeadd[] $tblsomeadds
 * @property Tblstudent[] $tblstudents
 * @property TblstudentAnimal[] $tblstudentAnimals
 * @property TblstudentHis[] $tblstudentHis
 * @property TblstudentUk[] $tblstudentUks
 * @property Tblsyoho[] $tblsyohos
 * @property TmsyotenRate[] $tmsyotenRates
 * @property TmsyoutenRate[] $tmsyoutenRates
 */
class Customer extends \yii\db\ActiveRecord
{
    protected static $schema = '';
    
    public function init()
    {
        parent::init();

        if(! in_array(static::$schema, ['webdb18','webdb20']))
            throw new \yii\base\InvalidParamException("schema is not specified");
    }

    public static function getDb()
    {
        return Yii::$app->get(static::$schema);
    }

    /* public function getCustomerid()
       {
       return $this->customerid;
       } */

    public static function getSchema()
    {
        return static::$schema;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblcustomer';
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        if('euc-jp' == $this->db->charset)
            foreach($this->attributes as $attr => $value)
                if(mb_detect_encoding($value, ['CP51932'])) // is value EUC-WIN-JP ?
                    $this->$attr = mb_convert_encoding($value, 'UTF-8', 'CP51932');// convert to utf8

        return parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        return false; // never allow save!!
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'kana', 'birth', 'occupation', 'email', 'contact', 'entrydate', 'updatedate', 'wireless', 'old_name', 'login_name', 'url', 'email_mobile', 'passwd', 'credit_num', 'cart_limit_month', 'cart_limit_year', 'secret_num', 'chk_dedit', 'card_limit_month', 'card_limit_year', 'card_name', 'card_kind', 'card_status_hj', 'card_status_hp', 'card_status_hi', 'card_status_he', 'card_status_uk', 'cart_info_fl'], 'string'],
            [['titleid', 'sexid', 'birthweight', 'sendid', 'netmember', 'machine', 'label', 'customer_1_divisionid', 'customer_2_divisionid', 'meiboid', 'nyukinid', 'soryoid', 'tanka_calid', 'sell_limitid', 'mailmagid', 'c_printid', 'free_mailid', 'email_divisionid', 'oasys_sendid', 'text_sendid', 'dm1_sendid', 'dm2_sendid', 'dm3_sendid', 'agencyid'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customerid' => 'Customerid',
            'name' => 'Name',
            'kana' => 'Kana',
            'titleid' => 'Titleid',
            'sexid' => 'Sexid',
            'birth' => 'Birth',
            'birthweight' => 'Birthweight',
            'occupation' => 'Occupation',
            'sendid' => 'Sendid',
            'email' => 'Email',
            'contact' => 'Contact',
            'netmember' => 'Netmember',
            'machine' => 'Machine',
            'entrydate' => 'Entrydate',
            'updatedate' => 'Updatedate',
            'label' => 'Label',
            'wireless' => 'Wireless',
            'customer_1_divisionid' => 'Customer 1 Divisionid',
            'customer_2_divisionid' => 'Customer 2 Divisionid',
            'meiboid' => 'Meiboid',
            'nyukinid' => 'Nyukinid',
            'soryoid' => 'Soryoid',
            'tanka_calid' => 'Tanka Calid',
            'sell_limitid' => 'Sell Limitid',
            'mailmagid' => 'Mailmagid',
            'c_printid' => 'C Printid',
            'old_name' => 'Old Name',
            'free_mailid' => 'Free Mailid',
            'login_name' => 'Login Name',
            'email_divisionid' => 'Email Divisionid',
            'url' => 'Url',
            'oasys_sendid' => 'Oasys Sendid',
            'text_sendid' => 'Text Sendid',
            'email_mobile' => 'Email Mobile',
            'passwd' => 'Passwd',
            'dm1_sendid' => 'Dm1 Sendid',
            'dm2_sendid' => 'Dm2 Sendid',
            'dm3_sendid' => 'Dm3 Sendid',
            'credit_num' => 'Credit Num',
            'cart_limit_month' => 'Cart Limit Month',
            'cart_limit_year' => 'Cart Limit Year',
            'secret_num' => 'Secret Num',
            'chk_dedit' => 'Chk Dedit',
            'card_limit_month' => 'Card Limit Month',
            'card_limit_year' => 'Card Limit Year',
            'card_name' => 'Card Name',
            'card_kind' => 'Card Kind',
            'agencyid' => 'Agencyid',
            'card_status_hj' => 'Card Status Hj',
            'card_status_hp' => 'Card Status Hp',
            'card_status_hi' => 'Card Status Hi',
            'card_status_he' => 'Card Status He',
            'card_status_uk' => 'Card Status Uk',
            'cart_info_fl' => 'Cart Info Fl',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Address::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer1Division()
    {
        return $this->hasOne(Tmcustomer1Division::className(), ['customer_1_divisionid' => 'customer_1_divisionid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer2Division()
    {
        return $this->hasOne(Tmcustomer2Division::className(), ['customer_2_divisionid' => 'customer_2_divisionid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSex()
    {
        return $this->hasOne(Tmsex::className(), ['sexid' => 'sexid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblfriendships()
    {
        return $this->hasMany(Friendship::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return bool
     */
    public function getChildren()
    {
        return $this->hasMany(Familymember::className(), ['parentid' => 'customerid'])->viaTable(self::tabelName(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblfukuyos()
    {
        return $this->hasMany(Tblfukuyo::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblhomoeClubs()
    {
        return $this->hasMany(TblhomoeClub::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblhomoeHisClubs()
    {
        return $this->hasMany(TblhomoeHisClub::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblhomoeopaths()
    {
        return $this->hasMany(Tblhomoeopath::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblintroduces()
    {
        return $this->hasMany(Tblintroduce::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTbljphmas()
    {
        return $this->hasMany(Tbljphma::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTbljphmaHis()
    {
        return $this->hasMany(TbljphmaHis::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTbljphms()
    {
        return $this->hasMany(Tbljphms::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblkoenkairegs()
    {
        return $this->hasMany(Tblkoenkaireg::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblnotes()
    {
        return $this->hasMany(Tblnote::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTbloffices()
    {
        return $this->hasMany(Tbloffice::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTbloldaddrs()
    {
        return $this->hasMany(Tbloldaddr::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblreservations()
    {
        return $this->hasMany(Tblreservation::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblsomeadds()
    {
        return $this->hasMany(Tblsomeadd::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblstudents()
    {
        return $this->hasMany(Tblstudent::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblstudentAnimals()
    {
        return $this->hasMany(TblstudentAnimal::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblstudentHis()
    {
        return $this->hasMany(TblstudentHis::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblstudentUks()
    {
        return $this->hasMany(TblstudentUk::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTblsyohos()
    {
        return $this->hasMany(Tblsyoho::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTmsyotenRates()
    {
        return $this->hasMany(TmsyotenRate::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTmsyoutenRates()
    {
        return $this->hasMany(TmsyoutenRate::className(), ['customerid' => 'customerid']);
    }

    public function isParent()
    {
        if($this->children)
            return true;

        return false;
    }

}
