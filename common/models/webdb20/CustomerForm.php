<?php

namespace common\models\webdb20;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the form class for table "tblcustomer".
 */
class CustomerForm extends \common\models\webdb\CustomerForm
{
    const DATETIME_MIN           = '1970-01-01 00:00:00';

    const MEMBER_TYPE_GENERIC    = 1; // 本会員
    const MEMBER_TYPE_NETWORK    = 2; // NETWORK会員
    const MEMBER_TYPE_GENERIC_UK = 3; // 本会員(UK)
    const MEMBER_TYPE_NETWORK_UK = 4; // NETWORK会員(UK)
    const MEMBER_TYPE_FAMILY     = 5; // 家族会員

    const SCENARIO_CHILD = 'child';

    public $db = 'webdb20';

    private $_mships;
    private $_children;
    private $_isStudent;
    private $_isToranoko;
    private $_wasToranoko;
    private $_toranokoParams;

    private $_matrix = [
        self::MEMBER_TYPE_GENERIC    => \common\models\Membership::PKEY_TORANOKO_GENERIC,
        self::MEMBER_TYPE_NETWORK    => \common\models\Membership::PKEY_TORANOKO_NETWORK,
        self::MEMBER_TYPE_GENERIC_UK => \common\models\Membership::PKEY_TORANOKO_GENERIC_UK,
        self::MEMBER_TYPE_NETWORK_UK => \common\models\Membership::PKEY_TORANOKO_NETWORK_UK,
        self::MEMBER_TYPE_FAMILY     => \common\models\Membership::PKEY_TORANOKO_FAMILY,
    ];

    public function scenarios()
    {
        return [
            \yii\base\Model::SCENARIO_DEFAULT => self::attributes(),
            self::SCENARIO_CHILD  => ['customerid','name','kana','sexid','entrydate','updatedate','birth',
                                      'name01','name02','kana01','kana02','sex_id'],
        ];
    }

    public static function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_HE);
    }

    public function migrateAttributes()
    {
        $attr = parent::migrateAttributes();
        $attr['children']    = $this->getChildren();
        $attr['memberships'] = $this->memberships;

        return $attr;

    }

    public function getAnotherModel()
    {
        return \common\models\ecorange\Customer::find()->where([
            'customer_code' => $this->customerid,
            'shop_id'       => 1,
            'del_flg'       => 0,
        ])->one();
    }

    public function getMemberships()
    {
        if(isset($this->_mships))
            return $this->_mships;

        $toranoko = $this->getToranokoMemberships();
        $jphma    = $this->getJphmaMemberships();
        $student  = $this->getStudentMemberships();
        $this->_mships = [];
        
        if($toranoko)
            $this->_mships = $toranoko;

        if($jphma)
            $this->_mships = array_merge($this->_mships, $jphma);

        if($student)
            $this->_mships = array_merge($this->_mships, $student);
        
        return $this->_mships;
    }

    private function getToranokoMemberships()
    {
        $db = Yii::$app->get($this->db);

        // とらのこ
        $rows = $db->createCommand(
'SELECT
 admissionid
,dateofadmission AS start_date
,dateofcontinuation AS update_date
FROM tblfriendship WHERE customerid = :cid
ORDER BY dateofcontinuation ASC
'
        )->bindValues([':cid' => $this->customerid])
        ->queryAll();

        $mships = [];
        foreach($rows as $row)
        {
            $a = $row['admissionid'];
            $update_date = $this->translateDate($row['update_date']);
            $expire_date = $this->calcExpireDate($update_date);

            if(null === ($mship_id = $this->translatePkey('admissionid', $a)))
                continue; // ignore invalid rows

            // overwrite if the same admissionid exists
            $mships[$a] = [
                'membership_id' => $mship_id,
                'start_date'    => $this->translateDate($row['start_date']),
                'update_date'   => $update_date,
                'expire_date'   => $expire_date,
            ];
        }
        $mships = array_values($mships);

        return $mships;
    }

    private function getJphmaMemberships()
    {
        $db = Yii::$app->get($this->db);

        $rows = $db->createCommand(
'SELECT
 jphmadivisionid
,dateofadmission as start_date
,update as update_date
FROM tbljphma WHERE customerid = :cid
ORDER BY dateofadmission ASC
'
        )->bindValues([':cid' => $this->customerid])
        ->queryAll();

        $mships = [];
        foreach($rows as $row)
        {
            $a = $row['jphmadivisionid'];
            $update_date = $this->translateDate($row['update_date']);
            $expire_time = strtotime($update_date) + 3 * (365 * 24 * 60 * 60); // 3 years later
            $expire_date = date('Y-m-d 23:59:59', $expire_time);

            if(null === ($mship_id = $this->translatePkey('jphmadivisionid', $a)))
                continue; // ignore invalid rows

            // overwrite if the same admissionid exists
            $mships[$a] = [
                'membership_id' => $mship_id,
                'start_date'    => $this->translateDate($row['start_date']),
                'update_date'   => $update_date,
                'expire_date'   => $expire_date,
            ];
        }
        $mships = array_values($mships);

        if(Jphma::isFamilyHomoeopath($this->customerid))
            $mships[] = [
                'membership_id' => \common\models\Membership::PKEY_JPHMA_FH,
                'start_date'    => null,
                'update_date'   => null,
                'expire_date'   => null,
            ];

        if(Jphma::isInnerChildTherapist($this->customerid))
            $mships[] = [
                'membership_id' => \common\models\Membership::PKEY_JPHMA_IC,
                'start_date'    => null,
                'update_date'   => null,
                'expire_date'   => null,
            ];

        return $mships;
    }

    private function getStudentMemberships()
    {
        $mships = [];

        if($this->isChhomStudent())
            if($attr = $this->getStudentAttribute(0))
                $mships[] = $attr;

        if($this->isFhStudent())
            if($attr = $this->getStudentAttribute(\common\models\Membership::PKEY_STUDENT_FH))
                $mships[] = $attr;

        if($this->isIcStudent())
            if($attr = $this->getStudentAttribute(\common\models\Membership::PKEY_STUDENT_IC))
                $mships[] = $attr;

        return $mships;
    }

    private function getStudentAttribute($pkey)
    {
        if($pkey == \common\models\Membership::PKEY_STUDENT_IC)
            return [
                'membership_id' => $pkey,
                'start_date'    => null,
                'update_date'   => null,
                'expire_date'   => null,
            ];

        if($pkey == \common\models\Membership::PKEY_STUDENT_FH)
            return [
                'membership_id' => $pkey,
                'start_date'    => null,
                'update_date'   => null,
                'expire_date'   => null,
            ];

        $matrix = [
            '1' => \common\models\Membership::PKEY_STUDENT_INTEGRATE,   // 統合医療
            '5' => \common\models\Membership::PKEY_STUDENT_TECH_COMMUTE,// 専科通学
            '6' => \common\models\Membership::PKEY_STUDENT_TECH_ELECTRIC,// 専科 E-learning
        ];
        $course_id = Yii::$app->get($this->db)->createCommand("SELECT schoolcourseid FROM tblstudent WHERE customerid = :cid AND kisei > 0 AND studentdivisionid = 1")
                              ->bindValues([':cid'=>$this->customerid])
                              ->queryScalar();

        if(! $course_id || ! array_key_exists($course_id, $matrix))
        {
            Yii::error([
                'customerid'     => $this->customerid,
                'schoolcourseid' => $course_id,
                'course_id does not in array: '. implode(',', array_keys($matrix)),
            ],
                       self::className().'::'.__FUNCTION__);

            return null;
        }

        return [
            'membership_id' => $matrix[$course_id],
            'start_date'    => null,
            'update_date'   => null,
            'expire_date'   => null,
        ];
    }

    private function getChildren()
    {
        if($this->scenario == self::SCENARIO_CHILD)
            return [];

        if(isset($this->_children))
            return $this->_children;

        $children_id = Yii::$app->get($this->db)->createCommand(sprintf(
            'SELECT customerid from tblfamilymember where parentid = %d',
            $this->customerid))->queryColumn();

        if(! $children_id)
            return [];

        $children = [];
        foreach($children_id as $customerid)
        {
            $child = SearchCustomer::findOne($customerid);
            if(! $child)
            {
                Yii::warning(sprintf('customerid of family member(%s) was not found tblcustomer', $customerid));
                continue;
            }
            if(0 == strlen(trim($child->name)))
                continue; // possibly a deleted record, skip this

            $child->scenario = self::SCENARIO_CHILD;
            $child->postnum  = null; // clear attributes, for they should be the same as parent
            $child->address1 = null;
            $child->address2 = null;
            $child->address3 = null;
            $child->tel      = null;
            $child->fax      = null;
            $child->email    = null;

            $children[] = $child;
        }
        return $children;
    }

    /* @return bool */
    public function isStudent()
    {
        if(isset($this->_isStudent))
            return $this->_isStudent;
        
        $this->_isStudent = $this->isChhomStudent() || $this->isFhStudent() || $this->isIcStudent();

        return $this->_isStudent;
    }

    /* CHhom 在校生かどうか
     * @return bool
     */
    public function isChhomStudent()
    {
        return Yii::$app->get($this->db)->createCommand(
            "SELECT customerid FROM tblstudent WHERE customerid = :cid AND kisei > 0 AND studentdivisionid = 1"
        )
                        ->bindValues([':cid'=>$this->customerid])
                        ->queryScalar();
    }

    /* FH 在校生かどうか
     * @return bool
     */
    private function isFhStudent()
    {
        return Yii::$app->get($this->db)->createCommand(
            "SELECT customerid FROM tblstudent_familyhomoeopath WHERE customerid = :cid AND kisei_fh > 0 AND studentdivision_fhid = 1"
        )
                        ->bindValues([':cid'=>$this->customerid])
                        ->queryScalar();
    }

    /* InnerChild 在校生かどうか
     * @return bool
     */
    private function isIcStudent()
    {
        return Chhom::isInnerChildStudent($this->customerid);
    }

    /* @return bool */
    public function isToranoko()
    {
        if(isset($this->_isToranoko))
            return $this->_isToranoko;

        if('webdb20' != $this->db)
            return ($this->_isToranoko = false);

        $rows = Yii::$app->get($this->db)->createCommand(sprintf(
            'SELECT customerid, dateofcontinuation FROM tblfriendship WHERE customerid = %d AND admissionid IN (%s)',
            $this->customerid,
            implode(',', [self::MEMBER_TYPE_GENERIC,
                          self::MEMBER_TYPE_NETWORK,
                          self::MEMBER_TYPE_GENERIC_UK,
                          self::MEMBER_TYPE_NETWORK_UK,
                          self::MEMBER_TYPE_FAMILY
            ])
        ))->queryAll();

        if(0 == count($rows))
        {
            $this->_wasToranoko = false;
            $this->_isToranoko  = false;

            return false;
        }
        $this->_wasToranoko = true;

        foreach($rows as $row)
        {
            $update_date = $this->translateDate($row['dateofcontinuation']);
            $expire_date = $this->calcExpireDate($update_date);
            if(time() < strtotime($expire_date))
                return ($this->_isToranoko = true);
        }

        return ($this->_isToranoko = false);
    }

    public function wasToranoko()
    {
        $this->isToranoko(); // $this->_wasToranoko to be set as side effect

        return $this->_wasToranoko;
    }

    public function getToranokoParams()
    {
        if(isset($this->_toranokoParams))
            return $this->_toranokoParams;

        $item = [
            'membership_id' => null,
            'start_date'    => null,
            'expire_date'   => null,
        ];

        if(! $this->wasToranoko()) return ($this->_toranokoParams = [ $item ]);

        $rows = Yii::$app->get($this->db)->createCommand(sprintf(
            'SELECT customerid, admissionid, dateofadmission, dateofcontinuation
             FROM tblfriendship
             WHERE customerid = %d AND admissionid IN (%s)',
            $this->customerid,
            implode(',', [self::MEMBER_TYPE_GENERIC,
                          self::MEMBER_TYPE_NETWORK,
                          self::MEMBER_TYPE_GENERIC_UK,
                          self::MEMBER_TYPE_NETWORK_UK,
                          self::MEMBER_TYPE_FAMILY
            ])
        ))->queryAll();
        if(0 == count($rows)) return [ $item ];

        $params = [];
        foreach($rows as $row)
        {
            if(! $row['dateofadmission'] && ! $row['dateofcontinuation'])
                continue; // ignore this row

            $start_date  = $this->translateDate($row['dateofadmission']);
            $update_date = $this->translateDate($row['dateofcontinuation']);
            $expire_date = $this->calcExpireDate($update_date);

            $params[] = [
                'membership_id' => $this->_matrix[$row['admissionid']],
                'start_date'    => $start_date,
                'expire_date'   => $expire_date,
            ];
        }

        return ($this->_toranokoParams = $params);
    }

    /**
     * extend 365 days, or get May 4th of that year (either shorter one)
     */
    private function calcExpireDate($datetime)
    {
        $expire_time = strtotime($datetime) + (365 * 24 * 3600); // extend 1 year
        $time1 = strtotime(date('Y-m-d',   $expire_time));
        $time2 = strtotime(date('Y-05-04', $expire_time));
        
        return date('Y-m-d', min($time1, $time2));
    }

    private function translateDate($str)
    {
        if(! preg_match('/^([0-9]+)\/([0-9]+)\/([0-9]+)$/', $str, $match))
            return '1970-01-01';

        $y = $match[1];
        $m = $match[2];
        $d = $match[3];
                
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    private function translatePkey($label, $value)
    {
        if('admissionid' == $label)
            if(isset($this->_matrix[$value]))
                return $this->_matrix[$value];

        if('jphmadivisionid' == $label)
            return static::translatePkeyJphma($value);

        return null;
    }

    /* convert webdb pkeys to Ebisu pkeys
     * @see select distinct jphmadivisionid from tbljphma
     */
    private static function translatePkeyJphma($value)
    {

        if("2" == $value) // 専門会員
            return \common\models\Membership::PKEY_JPHMA_TECHNICAL;

        if("3" == $value) // ホメオパス
            return \common\models\Membership::PKEY_HOMOEOPATH;

        return null;
    }
        
}
