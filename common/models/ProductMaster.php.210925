<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "vtb_product_master".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductMaster.php $
 * $Id: ProductMaster.php 3197 2017-02-26 05:22:57Z naito $
 *
 * @property integer $category_id
 * @property integer $product_id
 * @property integer $remedy_id
 * @property integer $potency_id
 * @property integer $vial_id
 * @property integer $sku_id
 * @property integer $keywords
 */
class ProductMaster extends \yii\db\ActiveRecord
{
    public $product_name;
    public $vender_key = "";
    public $bunrui_code1 = "";
    public $bunrui_code2 = "";
    public $bunrui_code3 = "";

    /* @inheritdoc */
    public static function tableName()
    {
        return 'mvtb_product_master';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            'ean13' =>[
                'class' => SubcategorySyncronizer::className(),
                'owner' => $this,
            ],
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
        ];
    }

    /* @inheritdoc */
    public function rules()
    {
        return [
            [['ean13', 'category_id', 'company_id', 'restrict_id', 'price'], 'required'],
            [['ean13', 'category_id', 'company_id', 'product_id', 'remedy_id', 'potency_id', 'vial_id', 'restrict_id','price'], 'integer'],
            [['name','kana', 'sku_id', 'keywords'],'string'],
            [['in_stock'],'in','range'=> [-1/*オーダーメイド*/, 0/*既製品、在庫なし*/, 1/*既製品、在庫あり*/] ],
            [['dsp_priority'],'integer'],
            [['vender_key', 'bunrui_code1', 'bunrui_code2', 'bunrui_code3' ], 'safe'],

        ];
    }

    /* @inheritdoc */
    public function attributeLabels()
    {
        return [
            'category_id' => 'カテゴリー',
            'company_id'  => '販社',
            'product_id'  => '商品',
            'remedy_id'   => 'レメディー',
            'potency_id'  => "ポーテンシー",
            'vial_id'     => "容器",
            'restrict_id' => "公開区分",
            'in_stock'    => "在庫",
            'dsp_priority'=> "表示順",
            'name'        => "表示名",
            'keywords'        => "検索キーワード",
            'vender_key'  => '製造元',
            'sku_id'      => 'SKU-ID',
            'bunrui_code1'  => '大分類',
            'bunrui_code2'  => '中分類',
            'bunrui_code3'  => '小分類',
            'create_date' => '作成日時',
        ];
    }

    public function beforeValidate() {
            $vender_key = $this->vender_key;
            $bunrui_code1 = $this->bunrui_code1;
            $bunrui_code2 = $this->bunrui_code2;
            $bunrui_code3 = $this->bunrui_code3;
       if($vender_key && $bunrui_code1 && $bunrui_code2 && $bunrui_code3) {
            if($this->isNewRecord) {
                $sales = new SalesCategory([
                    'sku_id' => $this->sku_id,
                    'vender_key' => strtoupper(Company::find()->where(['company_id' => $vender_key + 1])->one()->key),
                    'bunrui_code1' => SalesCategory1::find()->where(['bunrui_id' => $bunrui_code1 + 1])->one()->bunrui_code1,
                    'bunrui_code2' => SalesCategory2::find()->where(['bunrui_id' => $bunrui_code2 + 1])->one()->bunrui_code2,
                    'bunrui_code3' => SalesCategory3::find()->where(['bunrui_id' => $bunrui_code3 + 1])->one()->bunrui_code3,
                ]);
            } else {
                $sales = SalesCategory::find()->where(['sku_id' => $this->sku_id])->one();
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
        
        if($vender_key && $bunrui_code1 && $bunrui_code2 && $bunrui_code3) {
            try {
                $transaction = Yii::$app->db->beginTransaction();
                if($this->isNewRecord) {
                    $sales = new SalesCategory([
                        'sku_id' => $this->sku_id,
                        'vender_key' => strtoupper(Company::find()->where(['company_id' => (int)$vender_key + 1])->one()->key),
                        'bunrui_code1' => SalesCategory1::find()->where(['bunrui_id' => (int)$bunrui_code1 + 1])->one()->bunrui_code1,
                        'bunrui_code2' => SalesCategory2::find()->where(['bunrui_id' => (int)$bunrui_code2 + 1])->one()->bunrui_code2,
                        'bunrui_code3' => SalesCategory3::find()->where(['bunrui_id' => (int)$bunrui_code3 + 1])->one()->bunrui_code3,
                    ]);
                } else {
                    $sales = SalesCategory::find()->where(['sku_id' => $this->sku_id])->one();
                    if(!$sales) {
                        $sales = new SalesCategory([
                           'sku_id' => $this->sku_id,
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


    /* @inheritdoc */
    public static function find()
    {
        return new ProductMasterQuery(get_called_class());
    }

    public function getProductName()
    {
        return $this->product_name;
    }

    public function setProductName($val)
    {
        $this->product_name = $val;
    }

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }

    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'seller_id'])
            ->viaTable(Category::tableName(), ['category_id' => 'category_id']);
    }

    public function getExcerpt()
    {
        if($this->product)
            return $this->product->excerpt;
        /*
           if($this->remedy)
           return $this->remedy->excerpt; */
    }

    public function getImage()
    {
        if($images = $this->images)
            return array_shift($images);

        return null;
    }

    public function getImages()
    {
        if($this->product)
            return $this->product->images;
        if($this->stock)
            return $this->stock->images;
    }

    public function getModel()
    {
        if($this->product_id)
            return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
        else
            return $this->hasOne(RemedyStock::className(), [
                'remedy_id' => 'remedy_id',
                'potency_id'=> 'potency_id',
                'vial_id'   => 'vial_id',
            ]);
    }

    public function getName()
    {
        if($p = $this->product)
            return $p->name;

        if($s = $this->stock)
            return $s->name;

        return null;
    }

    public function getBarcode()
    {
        return $this->ean13;
    }

    public function getEan13()
    {
        return $this->ean13;
    }

    public function getSkuId()
    {
        return $this->sku_id;
    }

    public function getPickcode()
    {
        if($this->product)
            return $this->product->pickcode;

        if($this->stock && ($p = $this->stock->pickcode))
            return $p->pickcode;

        return null;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getKeywordsArray()
    {
        if($this->keywords == "")
            return "";

        return implode(",", $this->keywords);
    }

    public function getPrice()
    {
        return \yii\helpers\ArrayHelper::getValue($this->model, 'price');
    }

    public function getPotency()
    {
        return $this->hasOne(RemedyPotency::className(), ['potency_id' => 'potency_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    public function getRemedy()
    {
        return $this->hasOne(Remedy::className(), ['remedy_id' => 'remedy_id']);
    }

    public function getRestriction()
    {
        return $this->hasOne(ProductRestriction::className(), ['restrict_id' => 'restrict_id']);
    }

    public function getStock()
    {
        return $this->hasOne(RemedyStock::className(),[
            'remedy_id' => 'remedy_id',
            'potency_id'=> 'potency_id',
            'vial_id'   => 'vial_id',
        ]);
    }

    public function getRemedyStockForMasterUpdate()
    {
        return $this->hasOne(RemedyStock::className(),[
            'remedy_id' => 'remedy_id',
            'potency_id'=> 'potency_id',
        ]);
    }

    public function getPriceRangeItem()
    {
        return $this->hasOne(
                RemedyPriceRangeItem::className(),
                [
                    'prange_id' => 'prange_id',
                    'vial_id' => ProductMaster::tableName(). '.vial_id'
                ])
            ->via('remedyStockForMasterUpdate');
    }

    public function getLatestPrice()
    {
        $conditions = [
            'vial_id' => $this->vial_id,
            'prange_id' => $this->prange_id
        ];
        $target = RemedyPriceRangeItem::find()->andWhere(conditions)->one();

        if (!$target || !($target->hasAttribute('price')) )
            return false;

        return $target->price;

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcategories()
    {
        return $this->hasMany(Subcategory::className(), ['subcategory_id' => 'subcategory_id'])
                    ->viaTable(ProductSubcategory::tableName(), ['ean13' => 'ean13']);
    }

    public function getUrl()
    {
        if($product = $this->product)
            return $product->url;

        if($stock = $this->stock)
            return $stock->url;

        if($stock = $this->remedy)
            return \yii\helpers\Url::to(['/remedy/viewbyname', 'name'=>$this->remedy->name]);

        return null;
    }

    public function getVial()
    {
        return $this->hasOne(RemedyVial::className(), ['vial_id' => 'vial_id']);
    }

    public function isLiquor()
    {
        if(! $this->model)
        {
            Yii::error([
                sprintf('could not get model from (%s)', $this->name),
                $this->attributes,
            ], self::className().'::'.__FUNCTION__);
            return false;
        }

        return $this->model->isLiquor();
    }

    public function isProduct()
    {
        return ! isset($this->remedy_id);
    }

    public function isRemedy()
    {
        // レメディーカテゴリに属しているか判別する。
        return (Category::REMEDY == $this->category_id);
    }

    /**
     * 商品がHJ「雑貨」カテゴリに属するか
     * @return boolean
     */
    public function isRemedyVariety()
    {
        return (Category::REMEDY_GOODS == $this->category_id);
    }

    // TODO: （将来に備えて、現在未使用）商品のデフォルト画像の制御について。isRemedyでは厳密な区別を付けられないため、３種類に分割する。が、現状ではisSugarとisAlpoで判定し、あとはdefault.imgを使う制御となる 2017/03/22 kawai
    /**
     * レメディーか判別する
     */
    public function isSugar()
    {
        // レメディーカテゴリに属し、かつ容器ID（vial_id）が１〜４の商品をレメディーと判別する。
        return (Category::REMEDY == $this->category_id && isset($this->vial_id) && $this->vial_id < 5);
    }

    /**
     * チンクチャーか判別する
     */
    public function isTincture()
    {
        // レメディーカテゴリに属し、かつ容器ID（vial_id）が７〜９の商品をチンクチャーと判別する。
        // 容器IDが6の場合は、isFe()で判定
        if (Category::REMEDY == $this->category_id && isset($this->vial_id) && 6 <= $this->vial_id && $this->vial_id <= 12 && $this->vial_id != 10) {
            if(6 == $this->vial_id) {
                if(isFe()) {
                    return false;
                }
            }

            return true;
        }
        return false;
    }

    /**
     * フラワーエッセンスか判別する
     * FE)かFE2で先頭一致する、かつ 10ml容器（vial_id = 6）
     */
    public function isFe()
    {
        if ( preg_match("/^FE\)/", $this->name) || preg_match("/^FE2/", $this->name)) {
            return true;
        }

        return false;
    }

    /**
     * 容器がアルポであるか判別する
     */
    public function isAlpo()
    {
        // レメディーカテゴリに属し、かつ容器ID（vial_id）が5の商品をアルポと判別する。
        return (Category::REMEDY == $this->category_id && isset($this->vial_id) && $this->vial_id == 5);
    }

    /**
     * 商品マスタ（ビュー）の更新情報を取得する
     *
     */
    public function getTargetForUpdate()
    {
        $sql = ProductMaster::find()
                ->select([
                    ProductMaster::tableName(). '.ean13',
                    ProductMaster::tableName(). '.sku_id',
                    ProductMaster::tableName(). '.category_id',
                    ProductMaster::tableName(). '.product_id',
                    ProductMaster::tableName(). '.remedy_id',
                    ProductMaster::tableName(). '.potency_id',
                    ProductMaster::tableName(). '.vial_id',
                    ProductMaster::tableName(). '.restrict_id',
                    ProductMaster::tableName(). '.kana',
                    ProductMaster::tableName(). '.price',
                    ProductMaster::tableName(). '.in_stock',
                    ProductMaster::tableName(). '.company_id',
                    ProductMaster::tableName(). '.update_date',
                    ProductMaster::tableName(). '.dsp_priority',
                    ProductMaster::tableName(). '.name',
                ])
                // // 条件vial_id が3 5 10は対象外とする
                ->andWhere([
                    'not in',
                    ProductMaster::tableName(). '.vial_id',
                    RemedyVial::isPriceUpdateExclusion()
                ])
                ->innerJoinWith('remedyStockForMasterUpdate')
                ->groupBy(['remedy_id', 'potency_id', 'vial_id']);

                if(100 < $sql->count())
                {
                    ini_set("memory_limit",      "2048M"); // 2GB of total 32GB memory @ arnica.toyouke.com
                    ini_set("max_execution_time",    300); // 5 min
                }

        return $sql;
    }

    /**
     * mvtb_product_masterのpriceとupdate_dateの更新を行なう
     * 旧価格と新価格が同一の場合は更新処理を行なわない
     *
     * @param  integer $suc_total  正常更新件数
     * @param  integer $skip_total 同一金額件数
     * @param  integer $price      新価格
     *
     * @return なし
     */
    public function updateProductMasterPrice(&$suc_total, &$skip_total, $price)
    {
        if ($price == $this->price) {
            $skip_total++;
            return ;
        }

        $msg =  sprintf(
                        "価格を更新しました。　旧価格：%s => 新価格:%s　【remedy_id = %s, potency_id = %s, vial_id = %s, prange_id = %s】",
                        $this->price,
                        $price,
                        $this->remedy_id,
                        $this->potency_id,
                        $this->vial_id,
                        $this->remedyStockForMasterUpdate->prange_id
        );

        // 1件単位でトランザクションを実行する
        $tr = Yii::$app->db->beginTransaction();

        // 更新対象カラムは価格と最終更新日時
        $this->price = $price;
        $this->update_date = date('Y-m-d H:i:s');

        if ($this->update(['price', 'update_date'])) {
            // 価格変更メッセージがある場合
            if ($msg !== null)
                Yii::info($msg, $this->className().'::'.__FUNCTION__);

            $tr->commit();
            $suc_total++;
        } else {
            $tr->rollBack();
        }

        return ;
    }

    /**
     * レメディーの新価格をmtv_remedy_price_range_itemから取得する
     *
     * @return integer レメディーの新価格
     * @throw Exception 新価格が取得できない場合はExceptionを投げる
     */
    public function getRemedyPrice()
    {
        $stock = $this->remedyStockForMasterUpdate;
        if (! $stock)
            throw new \console\exception\PriceNotFoundException;

        $conditions = [
            'vial_id' => $this->vial_id,
            'prange_id' => $stock->prange_id
        ];

        $priceRange = RemedyPriceRangeItem::find()->andWhere($conditions)->one();

        // 価格を取得できない場合は例外を投げる
        if (!$priceRange || !$priceRange->hasAttribute('price') )
            throw new \console\exception\PriceNotFoundException;

        return $priceRange->price;
    }

}

/**
 * ActiveQuery for ProductMaster
 */
class ProductMasterQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function remedy()
    {
        return $this->andWhere(['mvtb_product_master.category_id' => Category::REMEDY]);
    }

    public function company($pk)
    {
        return $this->andWhere(['mvtb_product_master.category_id' =>
            Category::find()->where(['seller_id'=>$pk])->select('category_id')
        ]);
    }

    public function category($id)
    {
        return $this->andWhere(['mvtb_product_master.category_id' => $id]);
    }

    public function subcategory($id)
    {
        return $this->andWhere([
                    'ean13' => \common\models\ProductSubcategory::find()
                          ->andWhere(['subcategory_id' => $id])
                          ->select('ean13')
        ]);
    }

    public function restrict($customer=null)
    {
        if(! $customer)
            return $this->andWhere('mvtb_product_master.restrict_id = 0');

        return $this->andFilterWhere(['>=', 'mvtb_product_master.restrict_id', $customer->grade_id]);
    }

    public function vialRemedy($state = true)
    {
        $vial_ids_remedy = [RemedyVial::SMALL_BOTTLE, RemedyVial::LARGE_BOTTLE, RemedyVial::GLASS_5ML];
        if($state)
            return $this->andWhere(['in', 'vial_id', $vial_ids_remedy]);
        else
            return $this->andWhere(['not in', 'vial_id', $vial_ids_remedy]);
    }

    public function tinctureAndFlower($state = true)
    {
        $condition = ['mvtb_product_master.potency_id' => [
            RemedyPotency::MT,
            RemedyPotency::FE,
            RemedyPotency::FE2
        ]];

        if($state)
            return $this->andWhere($condition);
        else
            return $this->andWhere(['not', $condition]);
    }
}
