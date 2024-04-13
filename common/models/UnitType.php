<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_unit_type".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/UnitType.php $
 * $Id: UnitType.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $utype_id
 * @property string $name
 *
 * @property MtbUnit[] $mtbUnits
 */
class UnitType extends \yii\db\ActiveRecord
{
    const PKEY_LIQUID = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_unit_type';
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
            [['name'], 'required'],
            [['name'], 'string', 'max' => 4]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'utype_id' => 'Utype ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbUnits()
    {
        return $this->hasMany(MtbUnit::className(), ['utype_id' => 'utype_id']);
    }
}
