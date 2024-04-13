<?php

namespace common\models;

use Yii;
use common\models\StaffGrade;

/**
 * This is the model class for table "mtb_membership".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Membership.php $
 * $Id: Membership.php 4238 2020-03-12 04:40:39Z kawai $
 *
 * @property integer $membership_id
 * @property integer $company_id
 * @property integer $weight
 * @property string $name
 *
 * @property DtbCustomerMembership[] $dtbCustomerMemberships
 * @property DtbCustomer[] $customers

 * @property DtbProductPoint[] $dtbProductPoints
 * @property MtbCompany $company
 */
class Membership extends \yii\db\ActiveRecord
{
    const PKEY_TOYOUKE         =  1;

    const PKEY_HOMOEOPATH      =  2;
    const PKEY_JPHMA_TECHNICAL = 18;
    const PKEY_JPHMA_FH        =  3;
    const PKEY_JPHMA_IC        =  4;
    const PKEY_JPHMA_ANIMAL    = 23;
    const PKEY_JPHMA_ZEN       = 24;

    const PKEY_STUDENT_INTEGRATE     = 5; // ホメオパシー統合医療コース在学生	
    const PKEY_STUDENT_TECH_COMMUTE  = 6; // ホメオパシー専科通学コース在学生	
    const PKEY_STUDENT_TECH_ELECTRIC =19; // ホメオパシー専科eラーニングコース在校生	
    const PKEY_STUDENT_FH            = 7;
    const PKEY_STUDENT_IC            = 8;

    const PKEY_TORANOKO_GENERIC    =  9;
    const PKEY_TORANOKO_NETWORK    = 10;
    const PKEY_TORANOKO_FAMILY     = 11;
    const PKEY_TORANOKO_GENERIC_UK = 16;
    const PKEY_TORANOKO_NETWORK_UK = 17;

    const PKEY_CENTER_HOMOEOPATH   = 20;

    const PKEY_AGENCY_HE      = 12;
    const PKEY_AGENCY_HJ_A    = 13;
    const PKEY_AGENCY_HJ_B    = 14;
    const PKEY_AGENCY_HP      = 15;
    const PKEY_LIQUOR_LICENSE = 21;

    const PKEY_JPHF_FARMER    = 22;

    const PKEY_HAS_QX_SCIO    = 25;
    const PKEY_HOMOEOPATHY_CENTER = 26;

    const PKEY_STUDENT_FH_ELECTRIC = 36; // ファミリーホメオパスコースeラーニング在学生
    const PKEY_STUDENT_IC_ELECTRIC = 38; // インナーチャイルドセラピストコースeラーニング在学生

    const PKEY_SPECIAL_RANKUP = 40; // ポイント使用によるスペシャルランクアップ
    const PKEY_SPECIALPLUS_RANKUP = 41; // ポイント使用によるスペシャルプラスランクアップ

    const PKEY_STUDENT_SP_PHYTO = 42; // スピリチュアル・フィトセラピスト養成コース在校生
    const PKEY_JPHF_SP_PHYTO = 43; // JPHF認定スピリチュアル・フィトセラピスト

    /** 社員
     * Officer 役員
     * Regular Employee 正社員
     * Contact Employee 契約社員
     * Part Timer アルバイト
     */
    const PKEY_OF_TY = 46;
    const PKEY_OF_HE = 47;
    const PKEY_OF_HP = 48;
    const PKEY_OF_HJ = 49;
    const PKEY_RE_TY = 50;
    const PKEY_RE_HE = 51;
    const PKEY_RE_HP = 52;
    const PKEY_RE_HJ = 53;
    const PKEY_CE_TY = 54;
    const PKEY_CE_HE = 55;
    const PKEY_CE_HP = 56;
    const PKEY_CE_HJ = 57;
    const PKEY_PT_TY = 58;
    const PKEY_PT_HE = 59;
    const PKEY_PT_HP = 60;
    const PKEY_PT_HJ = 61;

    const PKEY_STUDENT_FLOWER = 62; // 日本のフラワーエッセンス通学コース在学生
    const PKEY_JPHF_FLOWER = 64; // JPHF認定フラワーエッセンスセラピスト
    const PKEY_STUDENT_FLOWER_ELECTRIC = 65; // 日本のフラワーエッセンスeラーニングコース在学生
    const PKEY_GRADUATE_FLOWER_ELECTRIC = 66; // 日本のフラワーエッセンスeラーニングコース卒業生
    const PKEY_STUDENT_PH_ELECTRIC = 67; // プロフェッショナルホメオパスeラーニングコース在校生
    const PKEY_GRADUATE_PH_ELECTRIC = 68; // プロフェッショナルホメオパスeラーニングコース卒業生

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_membership';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'name'], 'required'],
            [['company_id', 'weight'], 'integer'],
            [['weight'], 'default', 'value' => 1 ],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'membership_id' => 'Membership ID',
            'company_id' => 'Company ID',
            'weight' => 'Weight',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customer::className(), ['customer_id' => 'customer_id'])
                    ->viaTable(CustomerMembership::tableName(), ['membership_id' => 'membership_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDtbProductPoints()
    {
        return $this->hasMany(ProductPoint::className(), ['membership_id' => 'membership_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * 社員区分を取得する
     * @param membership_id
     * @return staff_grade_id
     */
    public static function getStaffGrade($membership_id) {
        $staff_grade_id = null;

        switch($membership_id) {
            case Membership::PKEY_OF_TY:
            case Membership::PKEY_OF_HE:
            case Membership::PKEY_OF_HP:
            case Membership::PKEY_OF_HJ:
                $staff_grade_id = StaffGrade::PKEY_OF;
                break;

            case Membership::PKEY_RE_TY:
            case Membership::PKEY_RE_HE:
            case Membership::PKEY_RE_HP:
            case Membership::PKEY_RE_HJ:
                $staff_grade_id = StaffGrade::PKEY_RE;
                break;

            case Membership::PKEY_CE_TY:
            case Membership::PKEY_CE_HE:
            case Membership::PKEY_CE_HP:
            case Membership::PKEY_CE_HJ:
                $staff_grade_id = StaffGrade::PKEY_CE;
                break;

            case Membership::PKEY_PT_TY:
            case Membership::PKEY_PT_HE:
            case Membership::PKEY_PT_HP:
            case Membership::PKEY_PT_HJ:
                $staff_grade_id = StaffGrade::PKEY_PT;
                break;

            default:
                break;
        }
        return $staff_grade_id;
    }    
}

