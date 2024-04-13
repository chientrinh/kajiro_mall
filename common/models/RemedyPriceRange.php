<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_remedy_price_range".
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyPriceRange.php $
 * $Id: RemedyPriceRange.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $prange_id
 * @property string $name
 *
 * @property MtbRemedyPriceRangeItem[] $mtbRemedyPriceRangeItems
 */
class RemedyPriceRange extends \yii\db\ActiveRecord
{
    const PKEY_COMPOSE_BASE = 8;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_price_range';
    }

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
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'prange_id' => 'Prange ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(RemedyPriceRangeItem::className(), ['prange_id' => 'prange_id']);
    }
}
