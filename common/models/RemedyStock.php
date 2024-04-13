<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;
use common\models\RemedyDescription;
use common\models\RemedyCategoryDescription;

/**
 * This is the model class for table "mtb_remedy_stock".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyStock.php $
 * $Id: RemedyStock.php 3197 2020-02-10 09:22:57Z kawai $
 *
 * @property integer $remedy_id
 * @property integer $potency_id
 * @property integer $vial_id
 * @property integer $prange_id
 * @property integer $in_stock
 *
 * @property remedyPotency $potency
 * @property remedyVial $vial
 * @property remedyPriceRange $prange
 * @property remedy $remedy
 * @property remedy $vender_key
 * @property remedy $sku_id
 * @property remedy $bunrui_code1
 * @property remedy $bunrui_code2
 * @property remedy $bunrui_code3
 */
class RemedyStock extends \yii\db\ActiveRecord
{
    const EAN13_PREFIX     = 25;
    const SCENARIO_COMPOSE = 'compose';
    private $_checkdigit;

    public $vender_key = "";
    public $bunrui_code1 = "";
    public $bunrui_code2 = "";
    public $bunrui_code3 = "";

    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_stock';
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
            'sync'=>[
                'class' => ProductMasterSyncronizer::className(),
                'owner' => $this,
            ],
            'img' =>[
                'class' => ProductImageSyncronizer::className(),
                'owner' => $this,
            ],
            'log'=>[
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
            ['prange_id', 'default', 'value' => self::getSimilarPrange($this->potency_id,$this->vial_id) ],
            [['in_stock','restrict_id'], 'default', 'value' => 0 ],
            [['remedy_id', 'potency_id', 'vial_id', 'prange_id'], 'required'],
            [['remedy_id', 'potency_id', 'vial_id', 'prange_id', 'in_stock'], 'integer'],
            [['remedy_id'],  'exist', 'targetClass' => Remedy::className()],
            [['potency_id'], 'exist', 'targetClass' => RemedyPotency::className()],
            [['prange_id','vial_id'], 'exist', 'targetClass' => RemedyPriceRangeItem::className()],
            ['restrict_id', 'exist', 'targetClass' => ProductRestriction::className(), ],
            ['restrict_id', 'compare','compareValue'=> $this->remedy ? $this->remedy->restrict_id : $this->restrict_id,'operator'=>'>=','type'=>'number','when'=>function($model){ return ! $model->hasErrors('remedy_id'); }],
            [['remedy_id', 'potency_id','vial_id'], 'unique', 'targetAttribute' => ['remedy_id', 'potency_id','vial_id'], 'message' => 'レメディー、ポーテンシー、容器の組み合わせは登録済みです。'],
            [['vender_key', 'bunrui_code1', 'bunrui_code2', 'bunrui_code3'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return array_merge(parent::scenarios(),[
            self::SCENARIO_COMPOSE => self::attributes(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'potency.name' => "ポーテンシー",
            'vial.name'    => "容器",
            'price'        => "価格",
            'prange.name'  => "価格帯",
            'restrict_id'  => "公開区分",
            'in_stock'     => "在庫",

            'potency_id'   => "ポーテンシー",
            'vial_id'      => "容器",
            'pickcode'     => "ピックコード",
            'vender_key'  => '製造元',
            'sku_id'      => 'SKU-ID',
            'bunrui_code1'  => '大分類',
            'bunrui_code2'  => '中分類',
            'bunrui_code3'  => '小分類',

        ];
    }

    // this function works, but not used anywhere 2015-07-20
    //
    // public static function constructByBarcode($ean13, $strict=false)
    // {
    //     $attr = self::parseBarcode($ean13);

    //     if(empty($attr))
    //         return null;

    //     $model = new self([
    //         'scenario'   => self::SCENARIO_COMPOSE,
    //         'remedy_id'  => $attr['remedy_id'],
    //         'potency_id' => $attr['potency_id'],
    //         'vial_id'    => $attr['vial_id'],
    //     ]);

    //     if($strict && ($model->checkdigit != $attr['checkdigit']))
    //         return null;

    //     return $model;
    // }

    public static function find()
    {
        return new RemedyStockQuery(get_called_class());
    }

    public static function findByCode($ean13)
    {
        return self::findByBarcode($ean13, false);
    }

    /**
     * @brief return a found stock, if not found return a new model
     * @return RemedyStock | false
     */
    public static function getOneByBarcode($ean13, $strict=true)
    {
        if(($model = self::findByBarcode($ean13, $strict)) !== null)
            return $model;

        $attr = self::parseBarcode($ean13);
        return new self([
            'remedy_id'  => $attr->remedy_id,
            'potency_id' => $attr->potency_id,
            'vial_id'    => $attr->vial_id,
        ]);
    }

    /**
     * 受注レコードのコードを元にRemedyStockのレコードを取得する。いわゆる「オリジナル」はRemedyStockにレコードを持たない（在庫がない）ため内部作成して返す
     * @brief return a stock in record
     * @return RemedyStock | false (when barcode is not valid)
     */
    public static function findByBarcode($ean13, $strict=false)
    {
        if($jan = RemedyStockJan::find()->where(['OR',['jan'=>$ean13],['sku_id'=>$ean13]])->one()) {
            return $jan->stock;
        }

        if(self::EAN13_PREFIX != substr($ean13, 0, strlen(self::EAN13_PREFIX))) {
            return null;
        }

        $rid = (int) substr($ean13, 3,5);
        $pid = (int) substr($ean13, 8,2);
        $vid = (int) substr($ean13,10,2);

        // 既製品はJANコードを持っている。JANコードを持たないレメディーたちのために行う処理であるため、RemedyStockへの検索は不要。
        $stock = new self([
            'remedy_id' => $rid,
            'potency_id'=> $pid,
            'vial_id'   => $vid,
            'in_stock'  => false,
        ]);

        if($strict && ($stock->checkdigit != substr($ean13, -1)))
            return null;

        return $stock;
    }

    /* @return string */
    public function getCode()
    {
        $base = sprintf('%02d%06d%02d%02d', self::EAN13_PREFIX, $this->remedy_id, $this->potency_id, $this->vial_id);
        if(!isset($this->_checkdigit))
            $this->_checkdigit = \common\components\ean13\CheckDigit::generate($base);

        return $base . $this->_checkdigit;

    }

    public function getBarcode()
    {
        if($this->jancode)
            return $this->jancode->jan;

        return $this->sku_id;
    }

    public function getSku_id()
    {
          return $this->code;
    }

    public function getSkuId()
    {
          return $this->code;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJancode()
    {
        return $this->hasOne(RemedyStockJan::className(),['sku_id' => 'sku_id']);
    }

    public function getCategory()
    {
        return Category::findOne(Category::REMEDY);
    }

    public function getCompany()
    {
        return Company::findOne(Company::PKEY_HJ);
    }

    public function getCheckdigit()
    {
        if(isset($this->_checkdigit))
            return $this->_checkdigit;

        $cd = new \common\components\ean13\CheckDigit();
        if(false === ($this->_checkdigit = $cd->generate($this->code)))
            Yii::error(sprintf('CheckDigit was not generate from code(%s)', $this->code), $this->className()."::".__FUNCTION__);

        return $this->_checkdigit;
    }

    public function getProductMaster()
    {
        return $this->hasMany(\common\models\ProductMaster::className(), ['remedy_id' => 'remedy_id']);
    }


    /* @return ActiveQuery */
    public function getImages()
    {
        $query = ProductImage::find()->where(['ean13'=> [$this->sku_id, $this->barcode]]);

        $query->multiple = true;

        return $query;
    }

    // this function works, but not used anywhere 2015-07-20
    //
    // private static function getModelByBarcode($ean13, $strict)
    // {
    //     $model = self::findByBarcode($ean13, $strict);

    //     if(! $model)
    //         $model = self::constructByBarcode($ean13, $strict);

    //     return $model;
    // }

    /* @return ActiveQuery */
    public function getOffer()
    {
        return Offer::find()->where(['category_id'=>$this->category->category_id]);
    }

    /* @return ActiveQuery */
    public function getSeasonalOffer()
    {
        return OfferSeasonal::find()->where(['ean13'=>$this->barcode]);
    }

    /* @return string */
    public function getName()
    {
        $name = [];

        if((RemedyPotency::MT != $this->potency_id)){
            $vial_name = "";

            if($this->remedy_id && $this->potency_id && $this->vial_id) {
                $vial_name = ProductMaster::find()->where(['remedy_id'=>$this->remedy_id,
                                          'potency_id'=>$this->potency_id,
                                          'vial_id'   =>$this->vial_id])
                                 ->select('name')
                                 ->scalar();
                $name[] = $vial_name;
            } else {
                // 滴下である場合、先頭に＋を入れる
                if(RemedyVial::DROP == $this->vial_id)
                    $name[] = '+';

                if($this->remedy_id /* 空レメディーは除く */ && ($r = $this->getRemedy()->one()))
                {
                    $name[] = $r->abbr;

                    if($r->ja)
                        $name[] = $r->ja;
                }
                // ポーテンシーがセットされていて、かつポーテンシーIDがコンビネーションでない場合
                if($this->potency && (RemedyPotency::COMBINATION !== $this->potency_id))
                    $name[] = $this->potency->name;

                if($this->vial && (RemedyVial::DROP != $this->vial_id)) {
                    $name[] = $this->vial->name;
                }
            }
        } else {
            
            $vial_name = ProductMaster::find()->where(['remedy_id'=>$this->remedy_id,
                                                  'potency_id'=>$this->potency_id,
                                                  'vial_id'   =>$this->vial_id])
                                         ->select('name')
                                         ->scalar();

            if($vial_name) {
                $name[] = $vial_name;
            } else {
                $name[] = $this->vial->name;
            }
        }
        $name = implode(' ', $name);
        $name = preg_replace('/combination/','', $name);
        return $name;
    }

    /* @return string */
    public function getKana()
    {
        $name = [];

        if((RemedyPotency::MT != $this->potency_id)){
            $vial_name = "";

            if($this->remedy_id && $this->potency_id && $this->vial_id) {
                $vial_name = ProductMaster::find()->where(['remedy_id'=>$this->remedy_id,
                                          'potency_id'=>$this->potency_id,
                                          'vial_id'   =>$this->vial_id])
                                 ->select('kana')
                                 ->scalar();
                $name[] = $vial_name;
            } else {
                // 滴下である場合、先頭に＋を入れる
                if(RemedyVial::DROP == $this->vial_id)
                    $name[] = '+';

                if($this->remedy_id /* 空レメディーは除く */ && ($r = $this->getRemedy()->one()))
                {
                    $name[] = $r->abbr;

                    if($r->ja)
                        $name[] = $r->ja;
                }
                // ポーテンシーがセットされていて、かつポーテンシーIDがコンビネーションでない場合
                if($this->potency && (RemedyPotency::COMBINATION !== $this->potency_id))
                    $name[] = $this->potency->name;

                if($this->vial && (RemedyVial::DROP != $this->vial_id)) {
                    $name[] = $this->vial->name;
                }
            }
        } else {

            $vial_name = ProductMaster::find()->where(['remedy_id'=>$this->remedy_id,
                                                  'potency_id'=>$this->potency_id,
                                                  'vial_id'   =>$this->vial_id])
                                         ->select('kana')
                                         ->scalar();

            if($vial_name) {
                $name[] = $vial_name;
            } else {
                if($this->remedy_id /* 空レメディーは除く */ && ($r = $this->getRemedy()->one()))
                {
                    $name[] = $r->abbr;

                    if($r->ja)
                        $name[] = $r->ja;
                }
                $name[] = $this->vial->name;
            }
        }
        $name = implode(' ', $name);
        $name = preg_replace('/combination/','', $name);
        return $name;
    }

    /**
     * @return integer or null
     */
    public function getPrice()
    {

        if(! $this->prange_id)
             $this->prange_id = RemedyStock::find()->andWhere(['remedy_id'  => $this->remedy_id,
                                                               'potency_id' => $this->potency_id])
                                            ->select('prange_id')
                                            ->scalar();


        if(RemedyPotency::MT <= $this->potency_id)
        {
            $query = RemedyPriceRangeItem::find()
                       ->where([
                           'prange_id' => $this->prange_id,
                           'vial_id'   => $this->vial_id,
                       ]);

            if($price = $query->max('price'))
                return $price;
        }
        if(RemedyVial::GLASS_5ML == $this->vial_id)
        {
            $drop = RemedyPriceRangeItem::find()->andwhere(['vial_id'   => RemedyVial::DROP,
                                                            'prange_id' => $this->prange_id])
                                                 ->one();
            $vial = RemedyPriceRangeItem::find()->andWhere(['vial_id'   => $this->vial_id,
                                                            'prange_id' => RemedyPriceRange::PKEY_COMPOSE_BASE])
                                                 ->one();

            if($drop && $vial)
                return (int)($drop->price + $vial->price);
        }

        if($row = RemedyPriceRangeItem::findOne([
                'prange_id' => $this->prange_id,
                'vial_id'   => $this->vial_id]))
        {
            return $row->price;
        }

        $this->addError('price','未定義です');
        return null;
    }

    /* @return int */
    public function getTax()
    {
        return $this->isLiquor() ? \common\models\Tax::findOne(1)->compute($this->price) : \common\models\Tax::findOne(2)->compute($this->price);
    }


    /**
     * @return (string | null)
     */
    public function getPickcode()
    {
        $query = ProductPickcode::find()->orFilterWhere(['ean13' => $this->sku_id]);
        $query->multiple = false;

        if($j = $this->jancode)
            $query->orFilterWhere(['ean13' => $j->jan]);

        if($model = $query->one())
            return $model->pickcode;

        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPotency()
    {
        return $this->hasOne(RemedyPotency::className(), ['potency_id' => 'potency_id']);
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

    public function getUrl()
    {
        if((RemedyPotency::MT == $this->potency_id) || (RemedyPotency::JM == $this->potency_id) ||
           (in_array($this->vial_id, [RemedyVial::GLASS_20ML,
                                      RemedyVial::GLASS_150ML,
                                      RemedyVial::GLASS_720ML,
                                      RemedyVial::PLASTIC_SPRAY_100ML,
                                      RemedyVial::PLASTIC_SPRAY_50ML,
                                      RemedyVial::PLASTIC_SPRAY_20ML
               ]))
          )
            return \yii\helpers\Url::to(['/hj/tincture', 'name'=>$this->remedy->name]);

        $rname = ArrayHelper::getValue($this->remedy,  'name', null);
        $pname = ArrayHelper::getValue($this->potency, 'name', null);

        return \yii\helpers\Url::to(['/remedy/viewbyname',
                                     'name' => $rname,
                                     '#'    => $pname,
                                     'vid'  => $this->vial_id,
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRemedy()
    {
        return $this->hasOne(Remedy::className(), ['remedy_id' => 'remedy_id']);
    }

    public function getRemedyDescription()
    {
        return $this->hasMany(RemedyDescription::className(), ['remedy_id' => 'remedy_id']);
    }
    /**
     * @brief set default value for $this->p_range
     *        alternative for \yii\validators\DefaultValidator
     * @return int
     */
    public function getSimilarPrange($potency_id, $vial_id)
    {
        $query = RemedyStock::find()
               ->select('prange_id')
               ->where([
                   'potency_id' => $potency_id,
                   'vial_id'    => $vial_id,
               ]);

        return $query->min('prange_id');
    }

    public function getSubcategories()
    {
        $query = Subcategory::find()
            ->where([
                'subcategory_id' => ProductSubcategory::find()
                                   ->orWhere(['ean13' => [$this->barcode, $this->sku_id]])
                                   ->select('subcategory_id')
            ]);

        $query->multiple = true;

        return $query;
    }

    public function getInStock()
    {
        return $this->in_stock;
    }

    /**
     * レメディーのカテゴリーを判定し、カテゴリー値を返す
     * 3:           砂糖玉
     * 2:           マザーティンクチャー
     * 1:           フラワーエッセンス
     * それ以外：   false
     * @return boolean|number
     */
    public function getRemedyCategory()
    {
        return RemedyCategory::getRemedyCategory();
    }

    /**
     * レメディーの広告説明を取得する
     * 条件:レメディーID、説明区分(1)、表示区分(1)
     *
     * @see RemedyDescription
     * @see mtb_remedy_description
     * @return array[\common\models\RemedyDescription]
     */
    public function getRemedyAdDescription()
    {
        return RemedyDescription::getRemedyAdDescription();

    }

    /**
     * レメディーの補足説明を取得する
     * 条件:レメディーID、説明区分(2)、レメディーカテゴリーID、表示区分(1)
     *
     * @see RemedyDescription
     * @see mtb_remedy_description
     * @return array[\common\models\RemedyDescription]
     */
    public function getRemedyDescriptions()
    {
        return RemedyDescription::getRemedyDescriptions();
    }

    /**
     * レメディーカテゴリーごとの広告用説明（説明区分：1）を取得する
     * 条件:レメディーID、レメディーカテゴリーID、表示区分(1)
     *
     * @see RemedyCategoryDescription
     * @see mtb_remedy_category_description
     * @return array[\common\models\RemedyCategoryDescription]
     */
    public function getCategoryAd()
    {
        return RemedyCategoryDescription::getCategoryAd($this);

    }

    /**
     * レメディーカテゴリーごとの補足説明（説明区分：2）を取得する
     * 条件:レメディーID、レメディーカテゴリーID、表示区分(1)
     *
     * @see RemedyCategoryDescription
     * @see mtb_remedy_category_description
     * @return array[\common\models\RemedyCategoryDescription]
     */
    public function getCategoryDescriptions()
    {
        return RemedyCategoryDescription::getCategoryDescriptions($this);
    }

    public function isLiquor()
    {
        $uid = ArrayHelper::getValue($this, 'vial.unit.utype_id', null);
        return (UnitType::PKEY_LIQUID == $uid && $this->vial_id != 10); // 滴下はLiquorではない
    }

    public function isRemedy()
    {
        return true;
    }

    /**
     * 商品がHJ「雑貨」カテゴリに属するか
     * @return boolean
     */
    public function isRemedyVariety()
    {
        return false;
    }


    private static function parseBarcode($ean13)
    {
        $pattern = sprintf('/^%02d([0-9]{6})([0-9]{2})([0-9]{2})([0-9])$/u', self::EAN13_PREFIX);

        if(! preg_match($pattern, $ean13, $match))
            return (object) [
                'remedy_id'  => null,
                'potency_id' => null,
                'vial_id'    => null,
                'checkdigit' => null,
            ];

        $param = (object) [
            'remedy_id'  => (int) $match[1],
            'potency_id' => (int) $match[2],
            'vial_id'    => (int) $match[3],
            'checkdigit' => (int) $match[4],
        ];

        return $param;
    }

    public static function parseCode($code)
    {
        $ean13 = $code . '0';
        return self::parseBarcode($ean13);
    }

   public function beforeValidate() {
       $vender_key = (int)$this->vender_key;
       $bunrui_code1 = (int)$this->bunrui_code1;
       $bunrui_code2 = (int)$this->bunrui_code2;
       $bunrui_code3 = (int)$this->bunrui_code3;
       $sku_id = $this->code;
       if($vender_key && $bunrui_code1 && $bunrui_code2 && $bunrui_code3) {
           if($this->isNewRecord) {
               $sales = new SalesCategory([
                   'sku_id' => $sku_id,
                   'vender_key' => strtoupper(Company::find()->where(['company_id' => $vender_key + 1])->one()->key),
                   'bunrui_code1' => SalesCategory1::find()->where(['bunrui_id' => $bunrui_code1 + 1])->one()->bunrui_code1,
                   'bunrui_code2' => SalesCategory2::find()->where(['bunrui_id' => $bunrui_code2 + 1])->one()->bunrui_code2,
                   'bunrui_code3' => SalesCategory3::find()->where(['bunrui_id' => $bunrui_code3 + 1])->one()->bunrui_code3,
               ]);
           } else {
               $sales = SalesCategory::find()->where(['sku_id' => $sku_id])->one();
                   if(!$sales) {
                    $sales = new SalesCategory([
                        'sku_id' => $sku_id,
                    ]);
                }
                $sales->vender_key =  strtoupper(Company::find()->where(['company_id' => $vender_key + 1])->one()->key);
                $sales->bunrui_code1 = SalesCategory1::find()->where(['bunrui_id' => $bunrui_code1 + 1])->one()->bunrui_code1;
                $sales->bunrui_code2 = SalesCategory2::find()->where(['bunrui_id' => $bunrui_code2 + 1])->one()->bunrui_code2;
                $sales->bunrui_code3 = SalesCategory3::find()->where(['bunrui_id' => $bunrui_code3 + 1])->one()->bunrui_code3;

           }
           if($sales->validate()){
           } else {
               return false;
           }
        }
        return parent::beforeValidate();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);


        $vender_key = $this->vender_key;
        $bunrui_code1 = $this->bunrui_code1;
        $bunrui_code2 = $this->bunrui_code2;
        $bunrui_code3 = $this->bunrui_code3;
        $sku_id = $this->code;

        if($vender_key && $bunrui_code1 && $bunrui_code2 && $bunrui_code3) {

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if($this->isNewRecord) {
                    $sales = new SalesCategory([
                        'sku_id' => $this->code,
                        'vender_key' => strtoupper(Company::find()->where(['company_id' => (int)$vender_key + 1])->one()->key),
                        'bunrui_code1' => SalesCategory1::find()->where(['bunrui_id' => (int)$bunrui_code1 + 1])->one()->bunrui_code1,
                        'bunrui_code2' => SalesCategory2::find()->where(['bunrui_id' => (int)$bunrui_code2 + 1])->one()->bunrui_code2,
                        'bunrui_code3' => SalesCategory3::find()->where(['bunrui_id' => (int)$bunrui_code3 + 1])->one()->bunrui_code3,
                    ]);
                } else {
                    $sales = SalesCategory::find()->where(['sku_id' => $sku_id])->one();
                    if(!$sales) {
                        $sales = new SalesCategory([
                            'sku_id' => $sku_id,
                        ]);

                    }
                    $sales->vender_key =  strtoupper(Company::find()->where(['company_id' => (int)$vender_key + 1])->one()->key);
                    $sales->bunrui_code1 = SalesCategory1::find()->where(['bunrui_id' => (int)$bunrui_code1 + 1])->one()->bunrui_code1;
                    $sales->bunrui_code2 = SalesCategory2::find()->where(['bunrui_id' => (int)$bunrui_code2 + 1])->one()->bunrui_code2;
                    $sales->bunrui_code3 = SalesCategory3::find()->where(['bunrui_id' => (int)$bunrui_code3 + 1])->one()->bunrui_code3;

                }
                if($sales->validate() && $sales->save()){
                } else {
                    Yii::warning($sales->errors);
                    $transaction->rollBack();
                    return false;
                }

            }
            catch (Exception $e)
            {
                Yii::warning($e->__toString(), $this->className().'::'.__FUNCTION__);
                $transaction->rollBack();
                return false;
            }

            $transaction->commit();
        }
        return true;
    }



    /* @return bool */
    public function beforeSave($insert)
    {
        if(in_array($this->scenario, [self::SCENARIO_COMPOSE, ]))
            return false; // do not save

        return parent::beforeSave($insert);
    }
}

class RemedyStockQuery extends \yii\db\ActiveQuery
{
    public function drops($state = true)
    {
        if($state)
            return $this->andWhere(['vial_id' => RemedyVial::DROP]);
        else
            return $this->andWhere(['not', ['vial_id' => RemedyVial::DROP]]);
    }

    public function active($state = true)
    {
        if($state)
            return $this->andWhere(['in_stock' => 1]);
        else
            return $this->andWhere(['in_stock' => 0]);
    }

    public function tincture($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::MT]);

        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::MT]]);
    }

    public function flower($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::FE]);
        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::FE]]);
    }

    public function flower2($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::FE2]);
        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::FE2]]);
    }

    public function flowers($state = true)
    {
        $condition = ['potency_id' => [RemedyPotency::FE, RemedyPotency::FE2]];

        if($state)
            return $this->andWhere($condition);
        else
            return $this->andWhere(['not', $condition]);
    }

    public function tinctureAndFlower($state = true)
    {
        $condition = ['potency_id' => [RemedyPotency::MT, RemedyPotency::FE, RemedyPotency::FE2]];

        if($state)
            return $this->andWhere($condition);
        else
            return $this->andWhere(['not', $condition]);
    }

    public function foreveryone()
    {
        /**
         *  1: conbination
         * 24: MM
         */
        return $this->andWhere([
            'between','potency_id', 1/*combination*/, 24/*MM*/
        ])->andWhere([
            'not', ['remedy_id' => 0] /*情報なし*/
        ]);
    }

    public function forcustomer($customer = null)
    {
        $grade_id = ArrayHelper::getValue($customer, 'grade_id', 0);

        if($customer && $customer->isAgencyOf(Company::PKEY_HJ))
            $grade_id = CustomerGrade::PKEY_NA;

        return $this->andWhere(['<=', 'restrict_id', $grade_id]);
    }
}
