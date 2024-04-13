<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tblsyoho".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/KaruteItem.php $
 * $Id: KaruteItem.php 2664 2016-07-06 08:36:09Z mori $
 *
 * @property integer $syohoid
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
 *
 * @property Tblcustomer $customer
 * @property Tblkarute $karute
 */
class KaruteItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblsyoho';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb20');
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['syohoid'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['karuteid', 'customerid', 'sodan_kindid', 'syoho_homeopathid', 'syoho_rec_f', 'syoho_proc_end_f', 'std_proc_end_f', 'syoho_sure_f', 'syoho_historyid', 'denpyo_centerid', 'passwd'], 'integer'],
            [['syoho_date', 'syoho_advice', 'syoho_coment', 'customer_report_date', 'customer_report', 'user_report', 'syoho_std_name', 'std_name', 'std_proc_date', 'syoho_proc_date', 'syoho_sure_date'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'syohoid' => '子カルテID',
            'karuteid' => 'カルテID',
            'customerid' => '顧客ID',
            'syoho_date' => '作成日',
            'sodan_kindid' => '相談会種別',
            'syoho_homeopathid' => 'ホメオパス',
            'syoho_advice' => 'アドバイス',
            'syoho_coment' => '経過',
            'user_report' => '患者からの報告',
            'syoho_rec_f' => 'Syoho Rec F',
            'syoho_proc_end_f' => 'Syoho Proc End F',
            'syoho_std_name' => 'Syoho Std Name',
            'std_proc_end_f' => 'Std Proc End F',
            'std_name' => 'Std Name',
            'std_proc_date' => 'Std Proc Date',
            'syoho_proc_date' => 'Syoho Proc Date',
            'syoho_sure_f' => 'Syoho Sure F',
            'syoho_sure_date' => 'Syoho Sure Date',
            'syoho_historyid' => '初診・再診',
            'denpyo_centerid' => '拠点',
        ];
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
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(DenpyoCenter::className(), ['denpyo_centerid' => 'denpyo_centerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultationType()
    {
        return $this->hasOne(ConsultationType::className(), ['sodan_kindid' => 'sodan_kindid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHomoeopath()
    {
        return $this->hasOne(KaruteHomoeopath::className(), ['syoho_homeopathid' => 'syoho_homeopathid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKarute()
    {
        return $this->hasOne(Karute::className(), ['karuteid' => 'karuteid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecipeItems()
    {
        return $this->hasMany(RecipeItem::className(), ['syohoid' => 'syohoid']);
    }

    public function getNext()
    {
        return self::find()->where(['>','syohoid',$this->syohoid])
                           ->orderBy('syohoid ASC')
                           ->one();
    }

    public function getPrev()
    {
        return self::find()->where(['<','syohoid',$this->syohoid])
                           ->orderBy('syohoid DESC')
                           ->one();
    }
}
