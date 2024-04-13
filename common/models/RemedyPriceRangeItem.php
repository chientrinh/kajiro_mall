<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_remedy_price_range_item".
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyPriceRangeItem.php $
 * $Id: RemedyPriceRangeItem.php 3277 2017-04-28 10:37:29Z kawai $
 *
 * @property integer $prange_id
 * @property integer $vial_id
 * @property integer $price
 * @property string $start_date
 * @property string $expire_date
 *
 * @property MtbRemedyVial $vial
 * @property MtbRemedyPriceRange $prange
 */
class RemedyPriceRangeItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_price_range_item';
    }

    public static function primaryKey()
    {
        return ['prange_id','vial_id'];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return date('Y-m-d H:i:s');
                },
            ],
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
            [['prange_id', 'vial_id', 'price', 'start_date'], 'required'],
            [['prange_id', 'vial_id', 'price'], 'integer'],
            [['start_date', 'expire_date'], 'safe']
        ];
    }

    /* @inheritdoc */
    public function attributeLabels()
    {
        return [
            'prange_id'    => '価格帯',
            'vial_id'      => "容器",
            'vial.unit_id' => "容量",
            'price'        => "価格",
            'expire_date'  => "有効期限",
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVial()
    {
        return $this->hasOne(RemedyVial::className(), ['vial_id' => 'vial_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrange()
    {
        return $this->hasOne(RemedyPriceRange::className(), ['prange_id' => 'prange_id']);
    }

    public function getStocks()
    {
        return $this->hasMany(RemedyStock::className(), ['prange_id' => 'prange_id','vial_id'=>'vial_id']);
    }

}
