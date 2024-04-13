<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_unit".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Unit.php $
 * $Id: Unit.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $unit_id
 * @property string $unit
 * @property string $description
 * @property integer $utype_id
 * @property integer $magnify
 *
 * @property MtbProductMaterial[] $mtbProductMaterials
 * @property MtbUnitType $utype
 */
class Unit extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_unit';
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
            [['unit', 'description'], 'required'],
            [['utype_id', 'magnify'], 'integer'],
            [['unit'], 'string', 'max' => 4],
            [['description'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'unit_id' => 'Unit ID',
            'unit' => 'Unit',
            'description' => 'Description',
            'utype_id' => 'Utype ID',
            'magnify' => 'Magnify',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMtbProductMaterials()
    {
        return $this->hasMany(MtbProductMaterial::className(), ['unit_id' => 'unit_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUtype()
    {
        return $this->hasOne(UnitType::className(), ['utype_id' => 'utype_id']);
    }
}
