<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Subcategory.php $
 * $Id: Subcategory.php 4109 2019-02-01 07:54:45Z kawai $
 *
 * This is the model class for table "mtb_subcategory".
 *
 * @property integer $subcategory_id
 * @property string $name
 * @property integer $company_id
 * @property integer $parent
 * @property integer $weight
 *
 * @property DtbProductSubcategory $dtbProductSubcategory
 * @property MtbCompany $company
 */
class Subcategory extends \yii\db\ActiveRecord
{
    const PKEY_TROSE_COLOR = 67;
    const PKEY_TROSE_SIZE  = 79;
    const PKEY_DISCOUNT_FOR_AGENT_B  = 169;
    const PKEY_HP_OTHER_PUBLISHER    = 119;

    const PKEY_REMEDY_KIT = 7;
    const PKEY_REMEDY_SET = 8;
    const PKEY_FE = 9;      // フラワーエッセンス
    const PKEY_MT = 10;     // マザーティンクチャ―
    const PKEY_SUGER = 123; // 砂糖玉
    const PKEY_REMEDY_SEPARATE = 31;
    const PKEY_TOUYA = 167; // 洞爺産
    //const PKEY_REMEDY_VARIETY = 130; // レメディー雑貨
    // 代理店卸価格対象外のサブカテゴリ
    const PKEY_HJ_AGENCY_EXCLUDE = 227;
    const PKEY_HE_AGENCY_EXCLUDE = 228;
    const PKEY_HP_AGENCY_EXCLUDE = 229;
    const PKEY_SODAN_SYUBETSU = 247;
    const PKEY_SODAN_COUPON = 248;
    const PKEY_ONLY_HE = 249;
    const PKEY_SODAN_TICKET = 253;
    const PKEY_MAGAZINE_CAMPAIGN = 266; // メルマガ読者キャンペーン（豊受自然農カート）

    const PKEY_RESTRICT = 99; // サイト非公開

    // レメディー「雑貨」系サブカテゴリ
    private static $pkeyRemedyVarieties = array(130, 220);

    public static function getPkeyRemedyVarieties()
    {
    	return self::$pkeyRemedyVarieties;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_subcategory';
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
            ['parent_id','filter','filter'=>function($value){ return $value ? $value : ''; } ],
            [['company_id', 'parent_id', 'weight', 'restrict_id'], 'integer'],
            ['company_id', 'exist', 'targetClass'=>Company::className()],
            ['parent_id', 'exist', 'targetClass'=>self::className(), 'targetAttribute'=>'subcategory_id'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name'        => '名前',
            'company_id'  => '販社',
            'parent_id'   => '親カテゴリ',
            'weight'      => '順位',
            'restrict_id' => "公開区分",
            'children'    => '子カテゴリ',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'restrict_id' => '制限なしの場合、つねに仮想店舗に表示されます。実店舗のみの場合、仮想店舗には決して表示されません。',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
       return new SubcategoryQuery(get_called_class());
    }

    public function getAncestors()
    {
        $ancestor = [];
        $p = $this;

        do
        {
            $ancestor[] = $p;
        }
        while($p = $p->parent);

        array_shift($ancestor); // exclude $this

        return array_reverse($ancestor);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(self::className(), ['parent_id' => 'subcategory_id'])
                    ->orderBy('weight DESC, subcategory_id ASC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    public function getFullName()
    {
        if($a = $this->ancestors)
        {
            $a[] = $this;
            return implode(' > ', \yii\helpers\ArrayHelper::getColumn($a,'name'));
        }

        return $this->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::className(), ['subcategory_id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(ProductMaster::className(), ['ean13' => 'ean13'])
                    ->viaTable(ProductSubcategory::tableName(), ['subcategory_id' => 'subcategory_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRestriction()
    {
        return $this->hasOne(ProductRestriction::className(), ['restrict_id' => 'restrict_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcategory()
    {
        return $this->hasOne(ProductSubcategory::className(), ['subcategory_id' => 'subcategory_id']);
    }
}

class SubcategoryQuery extends \yii\db\ActiveQuery
{
    public function topItem($state = true)
    {
        return $this->andWhere('NOW() <= dtb_customer.expire_date');
    }

    public function remedy($state = true)
    {
        if ($state)
            return $this->andWhere(['parent_id' => 31]);

        return $this->andWhere(['not', ['parent_id' => 31]]);
    }

    public function tincture($state = true)
    {
        $company_id = Yii::$app->request->get('comapny');
        if ($company_id && $company_id == Company::PKEY_HE)
            $condition = ['subcategory_id' => Subcategory::PKEY_TOUYA];
        else
            $condition = ['parent_id' => 10];

        if ($state)
            return $this->andWhere($condition);

        return $this->andWhere(['not', $condition]);
    }

    public function flower($state = true)
    {
        $condition = ['or', ['subcategory_id' => 9], ['parent_id' => 9]];

        if ($state)
            return $this->andWhere($condition);

        return $this->andWhere(['not', $condition]);
    }

    public function flower2($state = true)
    {
        $ids = [196, 197, 198];

        if ($state)
            return $this->andWhere(['subcategory_id' => $ids]);

        return $this->flower()->andWhere(['not', ['subcategory_id' => $ids]]);
    }

    public function popular()
    {
        $ids = [5, 7, 8, 130, 220, 225, 226];
        return $this->andWhere(['subcategory_id' => $ids]);
    }

    public function modular()
    {
        return $this->andWhere(['parent_id' => 8]);
    }

    public function kit()
    {
        return $this->andWhere(['parent_id' => 7]);
    }

    public function rxt()
    {
        return $this->remedy();
    }

    public function cosme_food()
    {
        $ids = [4,201,202,203,5,6,2,204,161,162,205,208];

//        $ids = [211,212,213,214,215,216];
        return $this->andWhere(['or',
                                ['subcategory_id' => $ids],
                                ['parent_id' => $ids]
        ]);
    }

    public function book_dvd()
    {
        $ids = [95,96];
        return $this->andWhere(['or',
                                ['subcategory_id' => $ids],
                                ['parent_id' => $ids]
        ]);
    }

    public function restaurant()
    {
        $ids = [];
        return $this->andWhere(['or',
                                ['subcategory_id' => $ids],
                                ['parent_id' => $ids]
        ]);

    }

    /**
     * 六本松レジのタブ「豊受」の表示サブカテゴリー
     *
     */
    public function products()
    {
//        $ids = [2, 3, 4, 5, 6, 201, 202, 203, 204];
        // 豊受のsubcategory_idを取得するようにする
        $ids = \yii\helpers\ArrayHelper::map(Subcategory::find()->where(['company_id' => Company::PKEY_TY])->all(), 'subcategory_id', 'subcategory_id');
        return $this->andWhere(['or',
                                ['subcategory_id' => $ids],
                                ['parent_id' => $ids]
        ]);
    }
}
