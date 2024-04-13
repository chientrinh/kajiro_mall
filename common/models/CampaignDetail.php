<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
// use \common\components\ean13\CheckDigit;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CampaignDetail.php $
 * $Id: $
 *
 */
class CampaignDetail extends \yii\db\ActiveRecord
{
    public static function primaryKey()
    {
        return ['campaign_id','category_id','subcategory_id','ean13','grade_id'];
    }

    public function init()
    {
        parent::init();

        if ($this->isNewRecord && ! $this->discount_rate)
            $this->discount_rate = 0;
        if ($this->isNewRecord && ! $this->point_rate)
            $this->point_rate = 0;

    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_campaign_detail';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'   => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => function ($event) { return new \yii\db\Expression('NOW()'); },
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
            [['campaign_id'], 'required'],
            [['campaign_id', 'category_id', 'subcategory_id', 'discount_rate'], 'integer'],
            [['discount_rate'], 'default', 'value'=>0],
            [['discount_rate'], 'string', 'min'=>1, 'max'=>3],
 //           [['discount_rate'], 'required', 'when' => function() { return ($this->discount_rate != 0); } ],
            ['discount_rate', 'validateRate'],
            [['grade_id'], 'default', 'value'=>NULL],
            [['point_rate'], 'default', 'value'=>0],
            [['point_rate'], 'string', 'min'=>1, 'max'=>3],
//            [['point_rate'], 'required', 'when' => function() { return ($this->point_rate != 0); } ],
            ['point_rate', 'validateRate'],
            [['campaign_id', 'category_id', 'grade_id', 'subcategory_id','ean13'], 'unique', 'targetAttribute' => ['campaign_id', 'category_id', 'grade_id', 'subcategory_id', 'ean13'],'message' => '既に当キャンペーンに登録されています。'],
            ['campaign_id', 'exist', 'targetClass' => Campaign::className() ],
            ['category_id', 'exist', 'targetClass' => Category::className() ],
            ['subcategory_id', 'exist', 'targetClass' => Subcategory::className() ],
            ['ean13', 'exist', 'targetClass' => ProductMaster::className() ],
            ['grade_id', 'exist', 'targetClass' => CustomerGrade::className() ],
            [['category_id', 'subcategory_id', 'ean13'], 'validateItems'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'campaign_id'      => 'キャンペーンID',
            'category_id'      => 'カテゴリーID',
            'subcategory_id'   => 'サブカテゴリーID',
            'ean13'            => 'バーコード',
            'discount_rate'    => '割引率（％）',
            'grade_id'         => '会員ランク',
            'point_rate'       => 'ポイント率（％）',
            'create_by'        => '作成者',
            'create_date'      => '作成日時',
            'update_by'        => '更新者',
            'update_date'      => '更新日時',
        ];
    }

    public function attributeHints()
    {
        return [
            'discount_rate'   => '半角数字(0～100)で入力してください。<br>
            0を指定した場合は割引なしとなります。',
            'point_rate'   => '半角数字(0～100)で入力してください。<br>
            0を指定した場合はポイント付与なしとなります。',
        ];
    }

    public function validateItems($attr, $params)
    {
        return (! $this->category_id && ! $this->subcategory_id && $this->ean13);
    }

    public function validateRate($attr, $params)
    {
        if (! is_numeric($this->discount_rate) || $this->discount_rate < 0 || $this->discount_rate > 100 || ! is_numeric($this->point_rate) || $this->point_rate < 0 || $this->point_rate > 100)
            $this->addError($attr, "0以上100以下の値を入力して下さい");

        return $this->hasErrors($attr);
    }

    /**
     * ランダム文字列生成 (英数字)
     * $length: 生成する文字数
     */
    protected function makeRandStr($length) {
        $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
        $r_str = null;
        for ($i = 0; $i < $length; $i++) {
            $r_str .= $str[rand(0, count($str) - 1)];
        }
        return $r_str;
    }

    /* @inheritdoc */
    public static function find()
    {
        return new CampaignDetailQuery(get_called_class());
    }

    public static function getStatus($status = null)
    {
        $statuses = ['利用可', '利用不可'];

        if (! $status)
            return $statuses;

        if (! array_key_exists($status, $statuses))
            return null;

        return $statuses[$status];
    }

    public function getCampaign()
    {
        return $this->hasOne(Campaign::className(), ['campaign_id' => 'campaign_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13']);
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

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }

    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['category_id' => 'category_id']);
    }

    public function getCategoryItem()
    {
        return $this->hasMany(ProductMaster::className(), ['category_id' => 'category_id']);
    }

    public function getGrade()
    {
        return $this->hasOne(CustomerGrade::className(), ['grade_id' => 'grade_id']);
    }

    public function getGrades()
    {
        return $this->hasMany(CustomerGrade::className(), ['grade_id' => 'grade_id']);
    }


    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    public static function searchCategories($campaign_id, $withProducts = true)
    {
        $query = CampaignDetail::find()
                    ->andWhere(['campaign_id' => $campaign_id])
                    ->andWhere(CampaignDetail::tableName().'.category_id IS NOT NULL')
                    ->leftJoin(['c' => Category::tableName()], CampaignDetail::tableName(). '.category_id=c.category_id');

        if ($withProducts)
            $query->leftJoin(['p' => ProductMaster::tableName()], CampaignDetail::tableName().'.category_id=p.category_id');

        return $query->all();

    }

    public static function searchSubCategories($campaign_id, $withProducts = true)
    {
        $query = CampaignDetail::find()
                    ->andWhere(['campaign_id' => $campaign_id])
                    ->andWhere(CampaignDetail::tableName().'.subcategory_id IS NOT NULL')
                    ->innerJoin(['c' => Subcategory::tableName()], CampaignDetail::tableName(). '.subcategory_id=c.subcategory_id');

        if ($withProducts)
            $query->innerJoin(['sp' => ProductSubcategory::tableName()], 
                                    CampaignDetail::tableName(). '.subcategory_id=sp.subcategory_id'
                                )
                  ->innerJoin(['m' => ProductMaster::tableName()], 'sp.ean13=m.ean13');

        return $query->all();
    }

    public static function searchProducts($campaign_id)
    {
        $query = CampaignDetail::find()
                    ->andWhere(['campaign_id' => $campaign_id])
                    ->andWhere(CampaignDetail::tableName().'.ean13 IS NOT NULL')
                    ->innerJoin(['m' => ProductMaster::tableName()], CampaignDetail::tableName().'.ean13=m.ean13');

        return $query->all();
    }   
}

/**
 * ActiveQuery for ProductMaster
 */
class CampaignDetailQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function products($campaign_id)
    {
        return $this->andWhere(['campaign_id' => $campaign_id])
                    ->andWhere(CampaignDetail::tableName().'.ean13 IS NOT NULL')
                    ->leftJoin(['m' => ProductMaster::tableName()], CampaignDetail::tableName().'.ean13=m.ean13');
    }

    public function categories($campaign_id, $withProducts = false)
    {
        $query = $this->andWhere(['campaign_id' => $campaign_id])
                      ->andWhere(CampaignDetail::tableName().'.category_id IS NOT NULL')
                      ->leftJoin(['c' => Category::tableName()], CampaignDetail::tableName(). '.category_id=c.category_id');

        if ($withProducts)
            return $query->leftJoin(['m' => ProductMaster::tableName()], CampaignDetail::tableName().'.category_id=m.category_id');

        return $query;
    }

    public function subcategories($campaign_id, $withProducts = false)
    {
        $query = $this->andWhere(['campaign_id' => $campaign_id])
                      ->andWhere(CampaignDetail::tableName().'.subcategory_id IS NOT NULL')
                      ->leftJoin(['c' => Subcategory::tableName()], CampaignDetail::tableName(). '.subcategory_id=c.subcategory_id');

        if ($withProducts)
            return $query->leftJoin(['sp' => ProductSubcategory::tableName()], CampaignDetail::tableName(). '.subcategory_id=sp.subcategory_id')
                         ->leftJoin(['m' => ProductMaster::tableName()], 'sp.ean13=m.ean13');

        return $query;
    }


}
