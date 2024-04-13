<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tblsyoho2".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/Syoho.php $
 * $Id: Syoho.php 1748 2015-11-01 22:36:58Z mori $
 *
 * @property integer $syoho2id
 * @property integer $karuteid
 * @property integer $customerid
 * @property string $syoho_date
 * @property integer $sodan_kindid
 * @property integer $syoho_homeopathid
 * @property string $syoho_advice
 * @property string $syoho_coment
 * @property string $customer_report_date
 * @property string $customer_report
 * @property integer $syoho_rec_f
 * @property integer $syoho_proc_end_f
 * @property string $user_report
 * @property string $syoho_std_name
 * @property integer $std_proc_end_f
 * @property string $std_name
 * @property string $std_proc_date
 * @property string $syoho_proc_date
 * @property integer $syoho_sure_f
 * @property string $syoho_sure_date
 * @property integer $syoho_historyid
 * @property integer $denpyo_centerid
 * @property integer $passwd
 * @property integer $syohoid_he
 */
class Syoho extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblsyoho2';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb18');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['karuteid', 'customerid', 'sodan_kindid', 'syoho_homeopathid', 'syoho_rec_f', 'syoho_proc_end_f', 'std_proc_end_f', 'syoho_sure_f', 'syoho_historyid', 'denpyo_centerid', 'passwd', 'syohoid_he'], 'integer'],
            [['syoho_date', 'syoho_advice', 'syoho_coment', 'customer_report_date', 'customer_report', 'user_report', 'syoho_std_name', 'std_name', 'std_proc_date', 'syoho_proc_date', 'syoho_sure_date'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'syoho2id' => 'Syoho2id',
            'karuteid' => 'Karuteid',
            'customerid' => 'Customerid',
            'syoho_date' => 'Syoho Date',
            'sodan_kindid' => 'Sodan Kindid',
            'syoho_homeopathid' => 'Syoho Homeopathid',
            'syoho_advice' => 'Syoho Advice',
            'syoho_coment' => 'Syoho Coment',
            'customer_report_date' => 'Customer Report Date',
            'customer_report' => 'Customer Report',
            'syoho_rec_f' => 'Syoho Rec F',
            'syoho_proc_end_f' => 'Syoho Proc End F',
            'user_report' => 'User Report',
            'syoho_std_name' => 'Syoho Std Name',
            'std_proc_end_f' => 'Std Proc End F',
            'std_name' => 'Std Name',
            'std_proc_date' => 'Std Proc Date',
            'syoho_proc_date' => 'Syoho Proc Date',
            'syoho_sure_f' => 'Syoho Sure F',
            'syoho_sure_date' => 'Syoho Sure Date',
            'syoho_historyid' => 'Syoho Historyid',
            'denpyo_centerid' => 'Denpyo Centerid',
            'passwd' => 'Passwd',
            'syohoid_he' => 'Syohoid He',
        ];
    }
}
