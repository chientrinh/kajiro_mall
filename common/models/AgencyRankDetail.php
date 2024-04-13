<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "mtb_agency_rank_detail".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/AgencyRankDetail.php $
 * $Id: AgencyRank.php $
 */
class AgencyRankDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_agency_rank_detail';
    }

    public static function primaryKey()
    {
        return ['rank_id','subcategory_id','sku_id'];
    }

    public function init()
    {
        parent::init();

        if ($this->isNewRecord && ! $this->discount_rate)
            $this->discount_rate = 0;

    }


    public function behaviors()
    {
        return [
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function () {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
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
            ['sku_id','required', 'when' => function ($model) {
                return $model->subcategory_id == null;
             }],
            ['subcategory_id','required', 'when' => function ($model) {
                return $model->sku_id == null;
             }],
            [['rank_id','discount_rate'], 'required'],
            [['rank_id', 'discount_rate', 'subcategory_id'], 'integer'],
            [['sku_id'], 'string', 'max' => 13],
            [['discount_rate'], 'integer', 'min'=>0, 'max'=>100],
            //['discount_rate', 'validateRate'],
            ['rank_id', 'exist', 'targetClass' => AgencyRank::className() ],
            ['subcategory_id', 'exist', 'targetClass' => Subcategory::className() ],
            ['sku_id', 'exist', 'targetClass' => ProductMaster::className() ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rank_id'     => 'ランクID',
            'subcategory_id'        => 'サブカテゴリID',
            'sku_id' => 'SKU_ID',
            'discount_rate'  => '割引率'
        ];
    }

    public function attributeHints()
    {
        return [
            'discount_rate'   => '半角数字(0～100)で入力してください。<br>
            0を指定した場合は割引なしとなります。',
        ];
    }

    public function validateRate($attr, $params)
    {
        if (! is_numeric($this->discount_rate) || $this->discount_rate < 0 || $this->discount_rate > 100)
            $this->addError($attr, "0以上100以下の値を入力して下さい");

        return $this->hasErrors($attr);
    }

    /* @inheritdoc */
    public static function find()
    {
        return new AgencyRankDetailQuery(get_called_class());
    }

    public function getAgencyRank()
    {
        return $this->hasOne(AgencyRank::className(), ['rank_id' => 'rank_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['sku_id' => 'sku_id']);
    }

    public function getSubCategory()
    {
        return $this->hasOne(Subcategory::className(), ['subcategory_id' => 'subcategory_id']);
    }

    public function getProductSubcategory()
    {
        return $this->hasOne(ProductSubcategory::className(), ['subcategory_id' => 'subcategory_id']);
    }

    public function getSubCategoryItem()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13'])
                    ->via('productSubcategory');
    }

    public static function searchSubCategories($rank_id, $withProducts = true)
    {
        $query = AgencyRankDetail::find()
                    ->andWhere(['rank_id' => $rank_id])
                    ->andWhere(AgencyRankDetail::tableName().'.subcategory_id IS NOT NULL')
                    ->innerJoin(['c' => Subcategory::tableName()], AgencyRankDetail::tableName(). '.subcategory_id=c.subcategory_id');

        if ($withProducts)
            $query->innerJoin(['sp' => ProductSubcategory::tableName()], 
                                    AgencyRankDetail::tableName(). '.subcategory_id=sp.subcategory_id'
                                )
                  ->innerJoin(['m' => ProductMaster::tableName()], 'sp.ean13=m.ean13');

        return $query->all();
    }

    public static function searchProducts($rank_id)
    {
        $query = AgencyRankDetail::find()
                    ->andWhere(['rank_id' => $rank_id])
                    ->andWhere(AgencyRankDetail::tableName().'.sku_id IS NOT NULL')
                    ->innerJoin(['m' => ProductMaster::tableName()], AgencyRankDetail::tableName().'.sku_id=m.sku_id');

        return $query->all();
    }   

}

/**
 * ActiveQuery for ProductMaster
 */
class AgencyRankDetailQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function products($rank_id)
    {
        return $this->andWhere(['rank_id' => $rank_id])
                    ->andWhere(AgencyRankDetail::tableName().'.sku_id IS NOT NULL')
                    ->leftJoin(['m' => ProductMaster::tableName()], AgencyRankDetail::tableName().'.sku_id=m.sku_id');
    }

    public function subcategories($rank_id, $withProducts = false)
    {
        $query = $this->andWhere(['rank_id' => $rank_id])
                      ->andWhere(AgencyRankDetail::tableName().'.subcategory_id IS NOT NULL')
                      ->leftJoin(['c' => Subcategory::tableName()], AgencyRankDetail::tableName(). '.subcategory_id=c.subcategory_id');

        if ($withProducts)
            return $query->leftJoin(['sp' => ProductSubcategory::tableName()], AgencyRankDetail::tableName(). '.subcategory_id=sp.subcategory_id')
                         ->leftJoin(['m' => ProductMaster::tableName()], 'sp.ean13=m.ean13');

        return $query;
    }


}

