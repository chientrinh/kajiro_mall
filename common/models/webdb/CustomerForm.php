<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the form class for table "tblcustomer".
 */
abstract class CustomerForm extends \yii\base\Model
{
    public $db;

    public $customerid;
    public $name;
    public $kana;
    public $sexid;
    public $entrydate;
    public $updatedate;
    public $birth;
    public $email;
    public $wireless;
    public $postnum;
    public $address1;
    public $address2;
    public $address3;
    public $tel;
    public $fax;
    public $mobile;
    public $email2;
    private $_name01;
    private $_name02;
    private $_kana01;
    private $_kana02;
    private $_pref;
    private $_zip01;
    private $_zip02;
    private $_addr01;
    private $_tel01;
    private $_tel02;
    private $_tel03;
    private $_birth_y;
    private $_birth_m;
    private $_birth_d;

    public function init()
    {
        parent::init();

        if(! in_array($this->db, ['webdb18','webdb20']))
            throw new \yii\db\IntegrityException(
                sprintf("'%s' is not supported as column of mall.dtb_customer",$this->db)
            );
    }

    public function attributes()
    {
        return ['customerid','name','kana','sexid','entrydate','updatedate','birth','email','email2','wireless','postnum','address1','address2','address3','tel','fax','mobile',
                'name01','name02','kana01','kana02','sex_id','pref_id','zip01','zip02','tel01','tel02','tel03','addr01','addr02'];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }

    public function rules()
    {
        return [
            [['customerid','name','kana','sexid','entrydate','updatedate','birth','email','email2','wireless','postnum','address1','address2','address3','tel','fax','mobile'], 'safe'],
        ];
    }

    public function getAddr()
    {
        if($this->pref)
            $pref = $this->_pref;

        return sprintf('%s %s %s',
                       $pref ? $pref->name : '',
                       $this->addr01,
                       $this->addr02);
    }

    public function getAddr01()
    {
        if($this->pref)
            return $this->_addr01;

        return $this->_addr01;
    }

    public function getAddr02()
    {
        if('.' !== $this->address3)
            return $this->address2 . $this->address3;

        return $this->address2;
    }

    public function getAnotherModel()
    {
        return null;
    }

    public function getBirth_y()
    {
        if(! $this->birth)
            return null;

        if(isset($this->_birth_y))
            return $this->_birth_y;

        if(preg_match('#([0-9]+)/([0-9]+)/([0-9]+)#', $this->birth, $match))
        {
            $this->_birth_y = $match[1];
            $this->_birth_m = $match[2];
            $this->_birth_d = $match[3];
        }

        return $this->_birth_y;
    }

    public function getBirth_m()
    {
        $this->birth_y;
        return $this->_birth_m;
    }

    public function getBirth_d()
    {
        $this->birth_y;
        return $this->_birth_d;
    }

    abstract public static function getCompany();

    public function getEmails()
    {
        $ret = [];

        if($this->email)
            $ret[] = $this->email;

        if($this->email2)
            $ret[] = $this->email2;

        return $ret;
    }

    public function getKana01()
    {
        if(! strlen($this->kana))
            return null;

        if(isset($this->_kana01))
            return $this->_kana01;

        if(preg_match('/(.*)[　 ](.+)/u', $this->kana, $match))
        {
            $this->_kana01 = $match[1];
            $this->_kana02 = $match[2];
        }
        else
        {
            $this->_kana01 = $this->kana;
            $this->_kana02 = '_'; // not null
        }

        return $this->_kana01;
    }

    public function getKana02()
    {
        if(! strlen($this->kana))
            return null;

        if(isset($this->_kana02))
            return $this->_kana02;

        $this->getKana01();

        return $this->_kana02;
    }

    public function getName01()
    {
        if(! strlen($this->name))
            return null;

        if(isset($this->_name01))
            return $this->_name01;

        if(preg_match('/(.*)[　 ](.+)/u', $this->name, $match))
        {
            $this->_name01 = $match[1];
            $this->_name02 = $match[2];
        }
        else
        {
            $this->_name01 = $this->name;
            $this->_name02 = '_'; // not null
        }

        return $this->_name01;
    }

    public function getName02()
    {
        if(! strlen($this->name))
            return null;

        if(isset($this->_name02))
            return $this->_name02;

        $this->getName01();

        return $this->_name02;
    }

    public function getOffice()
    {
        $db  = Yii::$app->get($this->db);
        $row = $db->createCommand('SELECT postnum, address1, address2, address3, tel FROM tbloffice WHERE customerid = :cid ')->bindValues([':cid'=>$this->customerid])->queryOne();

        if(! $row)
            return null;

        if('euc-jp' === $db->charset)
            foreach($row as $key => $val)
                $row[$key] = mb_convert_encoding($val, 'UTF-8', 'CP51932');

        return new static($row);
    }

    public function getPref()
    {
        if(! $this->address1)
            return null;

        if(isset($this->_pref))
            return $this->_pref->pref_id;

        foreach(\common\models\Pref::find()->all() as $pref)
        {
            $pattern = sprintf('/%s(.*)/', $pref->name);
            if(preg_match($pattern, $this->address1, $match))
            {
                $this->_pref = $pref;
                $this->_addr01 = $match[1]; // get and store addr01 also
                return $pref;
            }
        }

        return null;
    }

    public function getPref_id()
    {
        if($this->pref)
            return $this->_pref->pref_id;
    }

    public function getSchema()
    {
        return $this->db;
    }

    public function getSex()
    {
        return \common\models\Sex::findOne($this->sexid);
    }

    public function getSex_id()
    {
        return $this->sexid;
    }

    public function getTel01()
    {
        if(! $this->tel)
            return null;

        if(preg_match('/(0[0-9]{1,2}0?)([0-9]{1,5})([0-9]{4,})/', $this->tel, $match))
        {
            $this->_tel01 = $match[1];
            $this->_tel02 = $match[2];
            $this->_tel03 = $match[3];

            if(('03' == substr($this->_tel01, 0, 2)) || // Tokyo
               ('06' == substr($this->_tel01, 0, 2)))   // Osaka
            {
                $this->_tel02 = substr($this->_tel01, 2) . $this->_tel02;
                $this->_tel01 = substr($this->_tel01, 0, 2);
            }
        }

        return $this->_tel01;
    }

    public function getTel02()
    {
        $this->tel01;
        return $this->_tel02;
    }

    public function getTel03()
    {
        $this->tel01;
        return $this->_tel03;
    }

    public function getZip()
    {
        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    public function getZip01()
    {
        if(! $this->postnum)
            return null;

        if(isset($this->_zip01))
            return $this->_zip01;

        if(preg_match('/(.*)-(.*)/', $this->postnum, $match))
        {
            $this->_zip01 = $match[1];
            $this->_zip02 = $match[2];
            return $this->_zip01;
        }

        return null;
    }

    public function getZip02()
    {
        if(isset($this->_zip02))
            return $this->_zip02;

        $this->getZip01();

        return $this->_zip02;
    }

    public function setAddr01($str)
    {
        $this->_addr01 = $str;
    }

    public function setAddr02($str)
    {
        $this->address2 = $str;
    }

    public function migrateAttributes()
    {
        return array_merge($this->attributes, [
            'membercode' => [
                'directive'  => $this->db,
                'migrate_id' => $this->customerid,
            ],
            'subscribe' => 0,
        ]);
    }

    /* @return bool */
    public function wasMigrated()
    {
        $membercode = \common\models\Membercode::find()->where([
            'directive'  => $this->db,
            'migrate_id' => $this->customerid,
        ])->one();

        if($membercode && (0 < $membercode->customer_id))
            return true;

        $model = \common\models\Customer::find()->where([
            $this->db => $this->customerid,
        ])->one();
        if($model)
            return true;

        return false;
    }

}
