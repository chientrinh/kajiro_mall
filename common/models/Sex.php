<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_sex".
 * @see https://en.wikipedia.org/wiki/ISO/IEC_5218
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Sex.php $
 * $Id: Sex.php 2104 2016-02-18 05:29:09Z mori $
 *
 * @property integer $sex_id
 * @property string $name
 *
 * @property Customer[] $dtbCustomers
 */
class Sex extends \yii\db\ActiveRecord
{
    const PKEY_MALE   = 1;
    const PKEY_FEMALE = 2;
    const PKEY_LEGAL  = 9;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_sex';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sex_id', 'name'], 'required'],
            [['sex_id'], 'integer'],
            [['name'], 'string', 'max' => 4]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sex_id' => 'Sex ID',
            'name'   => '性別',
        ];
    }

    /**
     * self::PKEY に影響するので INSERT や UPDATE は禁止
     */
    public function beforeSave()
    {
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customer::className(), ['sex_id' => 'sex_id']);
    }

}
