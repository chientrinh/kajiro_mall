<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_offer_seasonal".
 *
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/OfferSeasonal.php $
 * @version $Id: OfferSeasonal.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $seasonal_id
 * @property integer $ean13
 * @property integer $grade_id
 * @property integer $discount_rate
 * @property integer $point_rate
 * @property string $start_date
 * @property string $end_date
 *
 * @property MtbCustomerGrade $grade
 */
class OfferSeasonal extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_offer_seasonal';
    }

    public static function find()
    {
        return new OfferSeasonalQuery(get_called_class());
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            'pkey' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'seasonal_id',
                ],
                'value' => function ($event) {
                    return self::find()->select('seasonal_id')->max('seasonal_id') + 1;
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
            [['ean13', 'start_date', 'end_date'], 'required'],
            [['ean13', 'branch_id', 'grade_id', 'discount_rate', 'point_rate'], 'integer'],
            [['ean13'],'string','length'=>13],
            [['branch_id',   ],'exist', 'targetClass'=>\common\models\Branch::className(),'skipOnEmpty'=>true],
            [['grade_id',   ],'exist', 'targetClass' =>\common\models\CustomerGrade::className(),'skipOnEmpty'=>true],
            [['discount_rate','point_rate'],'default','value'=>0],
            [['discount_rate','point_rate'],'integer','min'=>0,'max'=>101],
            [['start_date', 'end_date'], 'safe']
        ];
    }

    public function beforeValidate()
    {
        if(0 == $this->grade_id)
            $this->grade_id = null;

        if(-1 == $this->branch_id)
            $this->branch_id = null;
            
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'branch_id'     => '拠点',
            'category_id'   => 'カテゴリー',
            'grade_id'      => '会員区分',
            'discount_rate' => '値引き率',
            'point_rate'    => 'ポイント率',
            'start_date'    => '開始',
            'end_date'      => '終了',
        ];
    }

    public function attributeHints()
    {
        return [
            'branch_id'     => '拠点を指定すると、その拠点だけを対象とします',
            'discount_rate' => '※100より大きい数字(101)を指定すると「絶対値引きしない」を意味します。つまり、他のご優待と競合する場合、この値が最優先され、そして値引率がゼロになります',
            'point_rate'    => '※100より大きい数字(101)を指定すると「絶対Pt付与しない」を意味します',
            'start_date'    => 'この日からご優待が有効になります',
            'end_date'      => 'この日までご優待が有効です',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrade()
    {
        return $this->hasOne(CustomerGrade::className(), ['grade_id' => 'grade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaster()
    {
        return $this->hasOne(\common\models\ProductMaster::className(),['ean13' => 'ean13']);
    }

    /**
     * @return (\yii\data\ActiveRecord | null)
     */
    public function getModel()
    {
        $finder = new \common\components\ean13\ModelFinder([
            'barcode' => $this->ean13,
        ]);

        return $finder->getOne();
    }
}

class OfferSeasonalQuery extends \yii\db\ActiveQuery
{
    public function current()
    {
        $now = date('Y-m-d');

        return $this->andWhere(['<=', 'start_date', $now])
                    ->andWhere(['>=', 'end_date',   $now]);
    }

    public function future()
    {
        $now = date('Y-m-d H:i:s');

        return $this->andWhere(['>', 'start_date', $now]);
    }

    public function past()
    {
        $now = date('Y-m-d H:i:s');

        return $this->andWhere(['<', 'end_date', $now]);
    }

}
