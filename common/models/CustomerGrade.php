<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_customer_grade".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerGrade.php $
 * $Id: CustomerGrade.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $grade_id
 * @property string $nickname
 * @property string $name
 * @property string $summary
 * @property string $privileges
 *
 * @property DtbCustomer[] $dtbCustomers
 */
class CustomerGrade extends \yii\db\ActiveRecord
{
    const PKEY_AA = 1;
    const PKEY_KA = 2;
    const PKEY_SA = 3;
    const PKEY_TA = 4;
    const PKEY_NA = 5;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_customer_grade';
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
                'user'   => Yii::$app->has('user') ? Yii::$app->get('user') : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['nickname', 'name', 'summary', 'privileges'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'grade_id'   => '区分ID',
            'nickname'   => 'あだ名',
            'name'       => '名称',
            'longname'   => '名称',
            'summary'    => '概要',
            'privileges' => '特典',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customer::className(), ['grade_id' => 'grade_id']);
    }

    public function getLongName()
    {
        return sprintf('(%s)%s', mb_substr($this->nickname,0,1), $this->name);
    }

}
