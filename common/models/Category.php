<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_category".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Category.php $
 * $Id: Category.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $category_id
 * @property string  $name
 * @property integer $vendor_id
 * @property integer $seller_id
 *
 * @property Product[] $Products
 * @property Company $vendor
 * @property Company $seller
 */
class Category extends \yii\db\ActiveRecord
{
    const FOOD        = 2;
    const BOOK        = 3;
    const COSMETIC    = 4;
    const REMEDY      = 6;
    const REMEDY_GOODS       = 9;
    const GOODS       = 10;
    const RESTAURANT  = 5;
    const TY_BOOK     = 12;
    const TEXT        = 17;
    const EVENT_TY    = 13;
    const EVENT_HE    = 14;
    const EVENT_JPHMA = 15;
    const TROSE       = 16;
    const OTHER       = 18;
    const TOYOUKE     = 1; // カテゴリーが豊受
    const SODAN       = 8;
    const LIVE       = 24;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_category';
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
            [['name', 'vendor_id', 'seller_id'], 'required'],
            [['vendor_id', 'seller_id'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name'       => "カテゴリー",
            'vendor_id'  => "製造",
            'seller_id'  => "販売",
        ];
    }

    public static function find()
    {
        return new CategoryQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['category_id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVendor()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'vendor_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeller()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'seller_id']);
    }

    public function getSubcategories()
    {
        return Subcategory::find()->where(['subcategory_id' =>
            $this->hasMany(ProductSubcategory::className(), ['ean13' => 'ean13'])
                 ->from(ProductSubcategory::tableName())
                 ->viaTable(ProductMaster::tableName(), ['category_id' => 'category_id'])
                 ->select('subcategory_id')
                 ->distinct()
        ]);
    }

    public function getFullName()
    {
        if($company = $this->seller)
        {
            return sprintf('%s （%s）  ', $this->name,$company->name);
        }

        return $this->name;
    }

    public function isEvent()
    {
        return in_array($this->category_id, [self::EVENT_TY, self::EVENT_HE, self::EVENT_JPHMA]);
    }
}

class CategoryQuery extends \yii\db\ActiveQuery
{
    public function getCosmeAndFood()
    {
        return $this->andWhere(['category_id' => [Category::FOOD, Category::COSMETIC]]);
    }

    public function getBookAndDVD()
    {
        return $this->andWhere(['category_id' => [Category::BOOK,Category::TY_BOOK]]);
    }

    public function getText()
    {
        return $this->andWHere(['category_id' => [Category::TEXT]]);
    }
}
