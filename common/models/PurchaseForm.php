<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/PurchaseForm.php $
 * $Id: PurchaseForm.php 4169 2019-06-08 16:34:41Z mori $
 *
 */
class PurchaseForm extends Purchase
{
    public $items;
    public $delivery;
    public $campaign_code;
    public $direct_code;
    //    public $campaign;

    public function init()
    {
        parent::init();

        if ($this->isNewRecord && !$this->items)
            $this->items = [];

        if (Yii::$app instanceof \yii\web\Application && ('app-frontend' == Yii::$app->id))
            if (Yii::$app->user->identity instanceof \common\models\Customer)
                $this->customer_id = Yii::$app->user->identity->customer_id;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        $rules = [
            ['items', 'required', 'message' => "商品がありません"],
            ['items', 'validateItems', 'skipOnError' => true],
            ['delivery', 'validateDelivery', 'skipOnError' => true, 'when' => function () {
                return ('app-frontend' !== Yii::$app->id);
            }],
            ['delivery', 'validateGift', 'when' => function ($model) {
                return isset($model->delivery);
            }],
        ];

        return ArrayHelper::merge(parent::rules(), $rules);
    }

    public function setCustomer($model)
    {
        $this->customer = $model;
        $this->customer_id = (null === $model) ? null : $model->id;
        $this->email = (null === $model) ? null : $model->email;
    }

    /**
     * サポート注文が設定されたら、agent_idにこれまでのcustomer_idをセット、Customerには直送先顧客をセットし直す
     *
     */
    public function setAgent($model)
    {
        $this->agent_id = $this->customer->customer_id;
        $this->setCustomer($model);
    }

    public function validateGift($attr, $params)
    {
        $gift = $this->delivery->gift;

        if (!$gift)
            return true;

        if (!$this->customer || !$this->customer_id) {
            $this->addError($attr, "納品書金額表示の指定は豊受モール会員のみ利用可能です");
            return false;
        }

           if (in_array($this->branch_id,[Branch::PKEY_ROPPONMATSU,Branch::PKEY_ATAMI])) {
            if (!in_array($this->payment_id, [Payment::PKEY_DIRECT_DEBIT, Payment::PKEY_BANK_TRANSFER]))
                $this->addError($attr, "そのお支払い方法は納品書金額表示の指定の対象となりません");
        } else if (!$this->customer->isAgency()) {
            if ($this->customer->grade_id < CustomerGrade::PKEY_KA)
                $this->addError($attr, "納品書金額表示の指定は豊受モールスペシャル会員以上でご利用可能です");

            if (Payment::PKEY_DIRECT_DEBIT != $this->payment_id)
                $this->addError($attr, "そのお支払い方法は納品書金額表示の指定の対象となりません");
        }

        return $this->hasErrors($attr);
    }

    public function validateDelivery($attr, $params)
    {
        if (!in_array($this->payment_id, [
            Payment::PKEY_YAMATO_COD,
            Payment::PKEY_POSTAL_COD,
            Payment::PKEY_BANK_TRANSFER,
            Payment::PKEY_DIRECT_DEBIT,
            Payment::PKEY_DROP_SHIPPING,
        ]))
            return true;

        if (!$delivery = $this->delivery) {
            $this->addError($attr, "お届け先の指定がありません");
            return false;
        } elseif ($delivery->validate())
            return true;

        if ($delivery->isNewRecord)
            $delivery->clearErrors('purchase_id');

        if ($delivery->hasErrors())
            $this->addError($attr, "お届け先の指定が完了していません");

        return $this->hasErrors($attr);
    }

    public function validateItems($attr, $params)
    {
        if ('app-frontend' === Yii::$app->id) // frontendの場合、$this->items には common/components/cart/CartItem が入っておりちょっと厄介なのでとりあえずこのvalidateは迂回する、もっときれいに作り直したい。。。
            return true;

        // とらのこ年会費にはCustomerが必須
        {
            if (array_key_exists('product_id', $this->items)) {


                $pid = ArrayHelper::getColumn($this->items, 'product_id');
                if (
                    in_array(Product::PKEY_TORANOKO_G_ADMISSION, $pid) ||
                    in_array(Product::PKEY_TORANOKO_N_ADMISSION, $pid)
                )
                    if (!$this->customer)
                        $this->addError($attr, "年会費のご購入にはお客様の指定が必要です");
            }
        }

        // 熱海・六本松では特定商品を追加できない
        {
            $com = ArrayHelper::getColumn($this->items, 'company');
            $cid = ArrayHelper::getColumn($com, 'company_id');
            $add_error = false;
            if(Branch::PKEY_ATAMI == $this->branch_id) {
                // トミーローズ商品は扱えない
                if(in_array(Company::PKEY_TROSE, $cid))
                    $add_error = true;

                if(in_array(Company::PKEY_TY, $cid)) {
                    // ActiveRecordオブジェクトからプレーンな配列に変換してから・・・で少しは軽くなると期待。
                   $data = ArrayHelper::toArray($this->items);
                   // TY書籍に属するproduct_idを配列で取得する
                    $ty_books = ArrayHelper::getColumn(Product::findBySql('SELECT product_id FROM dtb_product WHERE category_id ='.Category::TY_BOOK)->asArray()->all(),'product_id');

                    foreach($data as $item) {
                        // TY書籍に属さないTY商品は扱えない
                        if($item['company_id'] == Company::PKEY_TY && !in_array($item['product_id'],$ty_books)) {
                            $add_error = true;
                            break;
                        }
                    }   
                }
                if($add_error)
                    $this->addError($attr, "熱海発送所では扱えない商品が含まれています");
                // (in_array(Company::PKEY_TY,    $cid) || // TY商品、TROSE商品は取り扱えない
                //  in_array(Company::PKEY_TROSE, $cid))
            } 


            elseif ((Branch::PKEY_ROPPONMATSU == $this->branch_id) &&
                ((1 < count(array_unique($cid))) || // TY商品以外は取り扱えない
                    (Company::PKEY_TY != array_shift($cid)))
            )
                $this->addError($attr, "六本松発送所では扱えない商品が含まれています");
        }

        return $this->hasErrors($attr);
    }

    public function setDefaultDiscoutItemSession()
    {
        
   

//if(count($this->items) && $this->items[0] instanceof PurchaseItem && $this->items[0]->purchase_id){
                //foreach ($this->items as $key => $item) {
                  //$this->setDiscoutItemSession($item, $item->discount_rate, $item->discount_amount);
               //}
       //}
    }


    public function compute($updateItems = true)
    {

        if ($this->campaign)
            $this->setCampaignForItems();

        if ($updateItems) {
            foreach ($this->items as $k => $item) {
                $this->items[$k] = $this->updateItem($item);
            }
            if ($this->campaign)
                $this->setCampaignForItems($updateItems);
        }

        return parent::compute();
    }

    public function addItem($item)
    {
        $update_item = $this->updateItem($item);

        /*
        $hp_flg = $update_item->company->company_id == \common\models\Company::PKEY_HP ? true : false;

        foreach ($this->items as $item) {
            if($item->company->company_id != \common\models\Company::PKEY_HP && $hp_flg || $item->company->company_id == \common\models\Company::PKEY_HP && !$hp_flg) {
                Yii::$app->session->addFlash('error', "出版の商品を購入する場合は、出版の商品のみで伝票を作成してください");
                return;
            }
        }
*/
        $this->items[] = $update_item;
        $this->setCampaignForItems();
    }

    /**
     * 指定したアイテムについて優待率を取得する
     *
     **/
    public function getOfferRate($item, $customer = null)
    {
        if (!$customer)
            $customer = $this->customer;
        $point_rate     = 0;
        $discount_rate = 0;

        // PurchaseItem、CartItemとしてではなく、Product、Remedyなど各Modelに変更してから処理する
        if ($item instanceof PurchaseItem || $item instanceof CartItem)
            $item = $item->model;

        if (isset($item->code) && $item->code == "VEG")
            return array(
                'point' => $point_rate,
                'discount' => $discount_rate,
            );

        if (isset($item->category) && $cat = $item->category) {
            $q = Offer::find()->where(['category_id' => $cat->category_id]);
            if ($customer)
                $q->andWhere(['grade_id' => isset(Yii::$app->user->identity->grade_id) ? Yii::$app->user->identity->grade_id : $this->customer ? $this->customer->grade_id : null]);

            $point_rate = $q->max('point_rate');
            $discount_rate = $q->max('discount_rate');
        }

        //        if($item->category->isEvent())
        //            ; // do something?

        if (!isset($item->barcode))
            return array(
                'point' => $point_rate,
                'discount' => $discount_rate,
            );

        $barcode = $item->barcode;
        $query = \common\models\OfferSeasonal::find()
            ->where(['ean13' => $barcode])
            ->andWhere('start_date <= NOW()')
            ->andWhere('end_date   >= NOW()')
            ->andWhere([
                'OR',
                ['grade_id' => null],
                ['>=', 'grade_id',  isset(Yii::$app->user->identity->grade_id) ? Yii::$app->user->identity->grade_id : $this->customer ? $this->customer->grade_id : null]
            ]);
        if ($this->branch)
            $query->andWhere([
                'OR',
                ['branch_id' => null],
                ['branch_id' => $this->branch->branch_id]
            ]);
        else
            $query->andWhere(['branch_id' => null]);

        if ($query->max('point_rate'))
            $point_rate = $query->max('point_rate');


        if ($query->max('discount_rate'))
            $discount_rate = $query->max('discount_rate');


        // 6/9京都イベント対応
        if ($this->campaign && ($this->branch->branch_id == Branch::PKEY_EVENT && $this->campaign->campaign_id == 176)  && $this->campaign) {
            $discount_rate = $this->getCampaignDiscountRate($item, $discount_rate);
        }

        return array(
            'point' => $point_rate,
            'discount' => $discount_rate,
        );
    }

    /**
     * イベントキャンペーン向けサブカテゴリに該当する商品のとき、指定されたレートを返す
     *
     **/
    public function getCampaignDiscountRate($item, $rate)
    {
        if ($item instanceof \common\components\cart\ComplexRemedyForm) {
            return $rate;
        }
        $subcategories = null;
        // 2019/6/9京都イベント対応
        if ($item instanceof \common\components\cart\ProductItem) {
            if ($item->model instanceof Product && $item->model->product_id == 167) {
                $item->model->price = 3200;
                return '50';
            }
            $subcategories = $item->model->getSubcategories()->all();
        } else if ($item instanceof Product || $item instanceof PurchaseItem) {

            if ($item->product_id == 167) {
                $item->price = 3200;
                return '50';
            }
            $subcategories = $item->getSubcategories()->all();
        } else if ($item instanceof RemedyStock) {
            $subcategories = $item->getSubcategories()->all();
        } else if ($item->model instanceof RemedyStock) {
            $subcategories = $item->model->getSubcategories()->all();
        }

        $subcategory_ids = ArrayHelper::getColumn($subcategories, 'subcategory_id');
        $subcategory_parents = ArrayHelper::getColumn($subcategories, 'parent_id');
        $subcategories = array_merge($subcategory_ids, $subcategory_parents);
        $subcategories = array_filter($subcategories,  "strlen");

        if (in_array(275, $subcategories)) {
            return '30';
        }

        if (in_array(276, $subcategories)) {
            return '30';
        }

        if (in_array(277, $subcategories)) {
            return '50';
        }

        if (in_array(278, $subcategories)) {
            return '40';
        }

        if (in_array(279, $subcategories)) {
            return '50';
        }

        if (in_array(280, $subcategories)) {
            return '50';
        }

        if (in_array(281, $subcategories)) {
            return '20';
        }

        return $rate;
    }

    /**
     * あえて引数にCustomerを指定して率を取得する
     * @param type $item
     * @param type $customer
     * @return type
     */
    public function getOfferRateByCustomer($item, $customer = null)
    {
        if (!$customer)
            $customer = isset(Yii::$app->user->identity->grade_id) ? Yii::$app->user->identity : null;
        $point_rate     = 0;
        $discount_rate = 0;

        // PurchaseItem、CartItemとしてではなく、Product、Remedyなど各Modelに変更してから処理する
        if ($item instanceof PurchaseItem || $item instanceof CartItem)
            $item = $item->model;

        if (isset($item->code) && $item->code == "VEG")
            return array(
                'point' => $point_rate,
                'discount' => $discount_rate,
            );

        if (isset($item->category) && $cat = $item->category) {
            $q = Offer::find()->where(['category_id' => $cat->category_id]);
            if ($customer)
                $q->andWhere(['grade_id' => isset($customer) ? $customer->grade_id : null]);

            $point_rate = $q->max('point_rate');
            $discount_rate = $q->max('discount_rate');
        }

        //        if($item->category->isEvent())
        //            ; // do something?

        if (!isset($item->barcode))
            return array(
                'point' => $point_rate,
                'discount' => $discount_rate,
            );

        $barcode = $item->barcode;
        $query = \common\models\OfferSeasonal::find()
            ->where(['ean13' => $barcode])
            ->andWhere('start_date <= NOW()')
            ->andWhere('end_date   >= NOW()')
            ->andWhere([
                'OR',
                ['grade_id' => null],
                ['>=', 'grade_id',  isset($customer) ? $customer->grade_id : null]
            ]);
        if ($this->branch)
            $query->andWhere([
                'OR',
                ['branch_id' => null],
                ['branch_id' => $this->branch->branch_id]
            ]);
        else
            $query->andWhere(['branch_id' => null]);

        if ($query->max('point_rate'))
            $point_rate = $query->max('point_rate');


        if ($query->max('discount_rate'))
            $discount_rate = $query->max('discount_rate');

        return array(
            'point' => $point_rate,
            'discount' => $discount_rate,
        );
    }


    private function setCampaignItem($item, $seq, $updateItem = false)
    {
        if (isset($item->campaign_id) && !$updateItem)
            return;

               // キャンペーン対象外商品なら適用対象外とする
        if($item->company->company_id == Company::PKEY_HJ) {

            if($item instanceof \common\components\cart\ComplexRemedyForm) {
                $exclude = false;       
                $items = $item->convertToPurchaseItem($this->purchase_id, $seq);

                // 分解した複数レメディー（配列）
                foreach($items as $convert_item) {
                    if(0 === strncmp($convert_item->barcode, '25', 2)) {
                        // var_dump("対象外");
                        $exclude = true;
                        break;
                    }
                    $exclude_item = \common\models\CampaignExcludeItem::find()->where(['ean13' => $convert_item->barcode]);
                    if($exclude_item->count() > 0) {
                        // var_dump("対象外テーブルにヒット");
                        $exclude = true;
                        break;
                    }
    
                    $master = ProductMaster::find()->where(['and',['like','ean13', '45%', false],['ean13' => $convert_item->barcode]])->orWhere(['and',['like','ean13', '24%', false],['ean13' => $item->barcode]]);
                    if($master->count() == 0) {
                        // var_dump("Masterに無いので対象外");
                        $exclude = true;
                        break;
                    }
    
                }
                if($exclude) {
                    // var_dump("終了");
                    return;
                }
                            
            } else {            
                // $exclude_item = \common\models\CampaignExcludeItem::find()->where(['or',['ean13' => $item->barcode],['sku_id' => $item->model->sku_id]]);
                $exclude_item = \common\models\CampaignExcludeItem::find()->where(['ean13' => $item->barcode]);
                if($exclude_item->count() > 0) {
                    return;
                }

                $master = ProductMaster::find()->where(['and',['like','ean13', '45%', false],['ean13' => $item->barcode]])->orWhere(['and',['like','ean13', '24%', false],['ean13' => $item->barcode]]);
                if($master->count() == 0) {

                    return;
                }
            }
        }


        $campaign_id = $this->campaign_id;
        if (!isset($this->campaign))
            return false;

        $grade_id = isset(Yii::$app->user->identity->grade_id) ? Yii::$app->user->identity->grade_id : $this->customer ? $this->customer->grade_id : null;
        $rate = 0;
        if ($item instanceof \common\components\cart\ComplexRemedyForm) {
            $category = CampaignDetail::find()->where(['campaign_id' => $campaign_id])->andWhere(['category_id' => Category::REMEDY]);
            if ($this->campaign->campaign_type == Campaign::POINT) {
                $category->andWhere(['grade_id' => $grade_id]);
                $rate = $category->max('point_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::POINT, 'grade_id' => $grade_id]);
                //return $this->applyReduceForOriginal($item, ['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::POINT, 'grade_id' => $grade_id]);
            } else {
                $rate = $category->max('discount_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::DISCOUNT]);
                //return $this->applyReduceForOriginal($item, ['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::DISCOUNT]);
            }
            return false;
        }


        // product
        if ($item->model) {
            $product = CampaignDetail::find()->andWhere(['campaign_id' => $campaign_id, 'ean13' => $item->model->barcode]);
            if ($grade_id && $this->campaign->campaign_type == Campaign::POINT)
                $product->andWhere(['grade_id' => $grade_id]);

            $rate = $product->max('point_rate');


            if ($this->campaign->campaign_type == Campaign::POINT) {

                $rate = $product->max('point_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::POINT, 'grade_id' => $grade_id]);
            } else {
                $rate = $product->max('discount_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::DISCOUNT]);
            }

            // subcategory
            $subcategories = $item->model->getSubcategories()->all();
            $subcategory_ids = ArrayHelper::getColumn($subcategories, 'subcategory_id');
            $subcategory_parents = ArrayHelper::getColumn($subcategories, 'parent_id');
            $subcategories = array_merge($subcategory_ids, $subcategory_parents);
            $subcategories = array_filter($subcategories,  "strlen");
            $subcategory = CampaignDetail::find()->where(['campaign_id' => $campaign_id])->andWhere(['in', 'subcategory_id', $subcategories]);

            if ($this->campaign->campaign_type == Campaign::POINT) {
                $subcategory->andWhere(['grade_id' => $grade_id]);
                $rate = $subcategory->max('point_rate');
                // 6/9京都イベント対応のため判定後半追加
                if (isset($rate) || ($this->branch->branch_id == Branch::PKEY_EVENT && $this->campaign->campaign_id == 176) && $this->getCampaignDiscountRate($item, 0) > 0)
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::POINT, 'grade_id' => $grade_id]);
            } else {
                $rate = $subcategory->max('discount_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::DISCOUNT]);
            }

            // Category
            $category_id = $item->model->category->category_id;

            $category = CampaignDetail::find()->where(['campaign_id' => $campaign_id])->andWhere(['category_id' => $category_id]);
            if ($this->campaign->campaign_type == Campaign::POINT) {
                $category->andWhere(['grade_id' => $grade_id]);
                $rate = $category->max('point_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::POINT, 'grade_id' => $grade_id]);
            } else {
                $rate = $category->max('discount_rate');
                if (isset($rate))
                    return $this->applyReduce(['seq' => $seq, 'per' => $rate, 'campaign_type' => Campaign::DISCOUNT]);
            }
        }

        // いずれにも該当しないものはキャンペーン対象外
        return false;
    }

    private function updateItem($item)
    {

        $search = new \common\models\SearchProductFavor([
            'branch'    => $this->branch,
            'customer'  => $this->customer,
            'timestamp' => $this->isNewRecord ? time() : strtotime($this->create_date),
            'agent_id'  => $this->agent_id,
            'item'      => $item,
        ]);

        $point    = $search->point;
 

        $discount = $search->wholesale == 0 ? $search->discountForMember : $search->discount;
        $discount_amount = $discount->amount ? $discount->amount : floor($item->price * $discount->rate / 100);
        $point_amount = $point->amount ? $point->amount : floor(($item->price - $item->getDiscountAmount()) * $item->getPointRate() / 100);

 
        if ($this->campaign && $item->campaign_id == $this->campaign_id) {
            if ($this->campaign->campaign_type == Campaign::DISCOUNT) {
                $item->setPointRate(0);
                $item->setPointAmount(0);
            } else if ($this->campaign->campaign_type == Campaign::POINT) {
                // 6/9京都イベント対応のため、判定追加
                if ($this->campaign_id != 176) {
                    if ($item instanceof \common\components\cart\ProductItem) {
                        $product_id = $item->model->product_id;
                        if ($product_id == 167) {
                            $product = ProductMaster::find()->where(['product_id' => 167])->asArray()->one();
                            $item->model->price = $product['price'];
                        }
                    } else if ($item instanceof PurchaseItem || $item instanceof Product) {
                        $product_id = $item->product_id;
                        if ($product_id == 167) {
                            $product = ProductMaster::find()->where(['product_id' => 167])->asArray()->one();
                            $item->price = $product['price'];
                        }
                    }
                    $item->setDiscountRate(0);
                    $item->setDiscountAmount(0);
                }
                // 現在アイテムにセットされている値引率を確認し、代理店／優待と同じ値なら０にリセットする
                if ($item->discountRate > 0 && $item->discountRate == $discount->rate) {
                    $item->setDiscountRate(0);
                    $item->setDiscountAmount(0);
                }

    


            }
            $item = $this->setPointRateItem($item,$point);
           
            return $item;
        } else {
            $item->is_wholesale = $search->wholesale;

            $get = Yii::$app->request->get();
            // キャンペーンリセットはリクエストパラメータに「campaign_id=0」を含むため、その有無で値引きを保持するか否か、分岐させる 2018/05/04
    //        if (!isset($get['campaign_id']))
                // 現在アイテムにセットされている値引を確認し、代理店／優待と異なる値ならポイント周りのみ再計算させる
      //          if ($item->discountRate > 0 && $item->discountRate != $discount->rate) {
        //            $item->setPointRate($point->rate);
          //          $item->setPointAmount(floor(($item->price - $item->getDiscountAmount()) * ($item->getPointRate() / 100)));
            //        return $item;
              //  }
        }


        $item->setDiscountRate($discount->rate);
        $item->setDiscountAmount($discount->amount ? $discount->amount : floor($item->price * $discount->rate / 100));
        $item->setPointRate($point->rate);
        $item->setPointAmount($point->amount ? $point->amount : floor(($item->price - $item->getDiscountAmount()) * $item->getPointRate() / 100));

        $item = $this->getDiscoutItemSession($item);
//        $item = $this->setPointRateItem($item,$point);
       
        return $item;
    }

    public function setPointRateItem($item,$point){
        if($this->campaign_id && $this->agent_id && $this->campaign->campaign_type == Campaign::POINT){
            $product = CampaignDetail::find()
            ->andWhere(['campaign_id' => $this->campaign_id, 
            'ean13' => $item->model->barcode]);
            $rate = $product->max('point_rate');
            $point_amount = floor(($item->price - $item->getDiscountAmount()) * ($rate - $point->rate) / 100);
       if($point_amount >= 0){
                $item->setPointAmount($point_amount);
            }
        }
        return $item;
    }

    public function getDiscoutItemSession($item)
    {
   
        $discount_items = Yii::$app->session->get('discount_items');
        $discount_items = ($discount_items) ? $discount_items : [];

        if (isset($discount_items["'" . $item->code . "'"])) {
            $item->setDiscountRate($discount_items["'" . $item->code . "'"]['rate']);
            $item->setDiscountAmount($discount_items["'" . $item->code . "'"]['amount']);
        }
       
        return $item;
    }



    public function setDiscoutItemSession($item, $rate, $amount)
    {
        $discount_items = Yii::$app->session->get('discount_items');
        $discount_items = ($discount_items) ? $discount_items : [];
        $discount = [
            'rate' => $rate,
            'amount' => $amount,
        ];
        $discount_items["'" . $item->code . "'"] = $discount;
        Yii::$app->session->set('discount_items', $discount_items);
    }

    /**
     * カート（レジ）内の全商品に対して、キャンペーンを適用する
     *
     **/
    public function setCampaignForItems($updateItems = false)
    {
        // if (!isset($this->campaign_id) || (isset($this->customer) && $this->customer->isAgency())) {
        if (!isset($this->campaign_id)) {
            return;
        }

        $campaign_id = $this->campaign_id;
        $grade_id = isset(Yii::$app->user->identity->grade_id) ? Yii::$app->user->identity->grade_id : $this->customer ? $this->customer->grade_id : null;

        if (($campaign = \common\models\Campaign::find()->andWhere(['campaign_id' => $campaign_id])->one()) === false) {
            return;
        }
        foreach ($this->items as $seq => $item) {
            $params = array();

            $apply_rate = 0;
            $search = new \common\models\SearchProductFavor([
                'branch'    => $this->branch,
                'customer'  => $this->customer,
                'timestamp' => $this->isNewRecord ? time() : strtotime($this->create_date),
                'item'      => $item,
            ]);
            $is_wholesale = $search->wholesale;

            if (isset($item->campaign_id) && !$updateItems)
                continue;


            // 優待率をチェックし、101となっていれば同じタイプのキャンペーンからは対象外となる
            $array = $this->getOfferRate($item);
            if (isset($array)) {
                if (!isset($this->campaign) || (($this->campaign->campaign_type == Campaign::DISCOUNT &&  $array['discount'] == 101) || ($this->campaign->campaign_type == Campaign::POINT &&  $array['point'] == 101)) && ($this->branch->branch_id != Branch::PKEY_EVENT)) {
                    $item->campaign_id = null;
                    continue;
                }
            }

            $member_point = $search->getPointForMember();
            // 社員なら社員割引（指定率のポイント付与） // 独自仕入れ品はOfferで０となっており、別途category_id = 18 で判定する
            if ($this->customer && $this->customer->isStaff()) {
                // PurchaseItem、CartItemとしてではなく、Product、Remedyなど各Modelに変更してから処理する
                if ($item instanceof PurchaseItem || $item instanceof CartItem) {
                    $category_id = $item->model->category->category_id;
                } else if ($item instanceof \common\components\cart\ComplexRemedyForm) {
                    $category_id = \common\models\Category::REMEDY;
                }

                if (($member_point['rate'] != 0 && $member_point['rate'] != 101) || $category_id == 18) {
                    $item->campaign_id = null;
                    continue;
                }
            }


            // $item->is_wholesale = 0;

            if ($this->setCampaignItem($item, $seq, $updateItems)) {
                $item->campaign_id = (int)$this->campaign_id;
                $item->is_wholesale = 0;
            } else {
                $item->is_wholesale = $is_wholesale;
                $item->campaign_id = null;
            }
        }
    }

    public function applyReduceForOriginal($item, $params)
    {
        $seq = ArrayHelper::getValue($params, 'seq', null);
        $per = ArrayHelper::getValue($params, 'per', null);
        $yen = ArrayHelper::getValue($params, 'yen', null);
        $campaign_type = ArrayHelper::getValue($params, 'campaign_type', null);
        $discount = ArrayHelper::getValue($params, 'discount', null);

        $apply_rate = 0;
        $rate_type = "category";
        // $items = $item->convertToPurchaseItem($this->purchase_id, $seq);
        $convert_items = $item->convertToPurchaseItem($this->purchase_id, $seq);

        foreach ($convert_items as $convertItem) {

            if (Campaign::DISCOUNT == $this->campaign->campaign_type) {
                $apply_rate = $per;

                if ($yen == 0) {
                    $apply_rate = 0;
                }
                $this->applyReduce(['seq' => $seq, 'per' => $apply_rate, 'campaign_type' => Campaign::DISCOUNT]);
            }

            if (Campaign::POINT == (int)$this->campaign->campaign_type) {
                $grade_id = isset(Yii::$app->user->identity->grade_id) ? Yii::$app->user->identity->grade_id : $this->customer ? $this->customer->grade_id : null;
                $apply_rate = $per;
                $this->applyReduce(['seq' => $seq, 'per' => $apply_rate, 'campaign_type' => Campaign::POINT, 'grade_id' => $grade_id]);
                $item->is_wholesale = 0;
            }
            if ($apply_rate != 0) {
                $this->items[$seq]->campaign_id = (int)$this->campaign_id;
                $this->items[$seq]->is_wholesale = 0;
            } else {
                $this->items[$seq]->campaign_id = null;
            }
        }
    }



    /**
     * 与えたパラメータに応じて商品に値引きやポイントをセットする
     *
     **/
    public function applyReduce($params)
    {

        $seq = ArrayHelper::getValue($params, 'seq', null);
        $per = ArrayHelper::getValue($params, 'per', null);
        $yen = ArrayHelper::getValue($params, 'yen', null);
        $campaign_type = ArrayHelper::getValue($params, 'campaign_type', null);
        $discount = ArrayHelper::getValue($params, 'discount', false);

        if ($per == 101) // 101は付与しない、0と同義
            $per = 0;

        if (!$item = $this->items[$seq])
            $msg = "$seq 番目の商品がみつかりません";
        elseif (100 < $per)
            $msg = " $per ％は大き過ぎます";
        elseif (0 > $per)
            $msg = " $per ％は小さ過ぎます";

        if (isset($msg)) {
            Yii::$app->session->addFlash('error', $msg);
            return false;
        }

        // 6/9京都イベント対応
        if (!$discount && isset($this->campaign->campaign_id) && ($this->campaign->campaign_id == 176)) {
            $discountRate = $this->getCampaignDiscountRate($item, $item->getDiscountRate());
            $item->discountRate = $discountRate;
            $item->setDiscountAmount(floor($item->price * $discountRate / 100));
        }
        // ここまで6/9京都イベント対応


        // 独自に値引きを適用したい場合
        if ($discount || isset($yen)) {

            $this->setDiscoutItemSession($item,round($yen / $item->price * 100),$yen);


            if (!isset($yen) && isset($per) && $per != $item->discountRate)
                $item->setDiscountAmount(floor($item->price * $per / 100));
            $item->setDiscountRate($per); // TBD: not working!!

            if (!isset($per) && isset($yen) && $yen != $item->discountAmount)
                $item->setDiscountRate(round($yen / $item->price * 100));
            $item->setDiscountAmount($yen);
            $item->setUnitPrice($item->price - $yen);

            $item->setPointAmount(floor(($item->price - $item->getDiscountAmount()) * $item->getPointRate() / 100));

            if ($this->campaign && $this->campaign->campaign_type == Campaign::DISCOUNT && $yen == 0) {
                return false;
            }
            return true;
        }

        if (!isset($campaign_type) || $campaign_type == Campaign::DISCOUNT) {
            if (!isset($yen) && isset($per) && $per != $item->discountRate)
                $item->setDiscountAmount(floor($item->price * $per / 100));
            $item->setDiscountRate($per); // TBD: not working!!

            if (!isset($per) && isset($yen) && $yen != $item->discountAmount)
                $item->setDiscountRate(round($yen / $item->price * 100));
            $item->setDiscountAmount($yen);
        }

        // 値引きキャンペーン、ポイントキャンペーンは併用できない仕様のため、値引きキャンペーン適用時はポイント付与をゼロとする
        if (Campaign::DISCOUNT == $campaign_type) {
            $item->is_wholesale = 0;
            $item->campaign_id = (int)$this->campaign_id;
            $item->setPointAmount(0);
            $item->setPointRate(0);
            $item = $this->getDiscoutItemSession($item);
            return true;
        } else if (Campaign::POINT == $campaign_type) {
            $item->is_wholesale = 0;
            $item->campaign_id = (int)$this->campaign_id;
            $item->setPointRate($per);
            $item->setPointAmount(floor(($item->price - $item->getDiscountAmount()) * $item->getPointRate() / 100));
            $item = $this->getDiscoutItemSession($item);

            return true;
        }
        var_dump("キャンペーンではないのよ。", $item->campaign_id);
        return false;
    }


    public function addCampaign($campaign_id)
    {
        $this->campaign_id = $campaign_id;
        $this->campaign = Campaign::find()->where(['campaign_id' => $campaign_id])->one();
        $this->campaign_code = $this->campaign->campaign_code;
    }

    public function setCampaign($model)
    {
        $this->campaign = $model;
        $this->campaign_id = (null === $model) ? 0 : $model->campaign_id;
        $this->campaign_code = (null === $model) ? null : $model->campaign_code;

        $this->setCampaignForItems(true);
    }

    public function unsetCampaign()
    {
        $this->campaign = null;
        $this->campaign_id = 0;
        $this->campaign_code = null;

        if (count($this->items) > 0) {
            foreach ($this->items as $seq => $item) {
                // 6/9京都イベント対応
                if ($item->product_id == 167) {
                    $product = ProductMaster::find()->where(['product_id' => 167])->asArray()->one();
                    $item->price = $product['price'];
                }

                if (!$item instanceof PurchaseItem && !$item instanceof \common\components\cart\ComplexRemedyForm) {
                    $item = $item->convertToPurchaseItem($this->purchase_id, $seq);
                } else if ($item instanceof \common\components\cart\ComplexRemedyForm) {
                    $items = $item->convertToPurchaseItem($this->purchase_id, $seq);

                    // 分解した際に複数レメディー（配列）になる場合、ならない単品の場合両方を考慮
                    if (is_array($items)) {
                        foreach ($items as $convert_item) {
                            if ($convert_item->campaign_id != null) {
                                $convert_item->campaign_id = null;
                                $convert_item = $this->updateItem($convert_item, true);
                            }
                            $convert_item->is_wholesale = $item->is_wholesale;
                        }
                    } else {
                        if ($items->campaign_id != null) {
                            $items->campaign_id = null;
                            $this->items[$seq] = $this->updateItem($items, true);
                        }
                        $items->is_wholesale = $item->is_wholesale;
                    }
                }
                if ($item->campaign_id != null) {
                    $item->campaign_id = null;
                    $item = $this->updateItem($item, true);
                }
            }
        }
        $this->campaign = null;
        $this->campaign_id = 0;
        $this->campaign_code = null;
        $this->compute();
    }

    public function dump()
    {
        $dump = $this->attributes;

        $dump['items'] = [];
        foreach ($this->items as $item) {
            if (method_exists($item, 'dump'))
                $dump['items'][] = array_merge($item->dump(), ['class' => $item->className()]);
            else
                $dump['items'][] = array_merge($item->attributes, ['class' => $item->className()]);
        }
        if ($this->delivery)
            $dump['delivery'] = $this->delivery->attributes;

        return $dump;
    }

    public function feed($dump)
    {
        if (!$dump)
            return;

        foreach ($dump as $name => $value) {
            if ($this->hasAttribute($name)) {
                $this->$name = $value;
            }
        }

        if (isset($dump['delivery']))
            $this->delivery = new PurchaseDelivery($dump['delivery']);

        if (!isset($dump['items']))
            return;

        $this->items = [];

        foreach ($dump['items'] as $k => $data) {
            $item = new $data['class'];
            if (method_exists($item, 'feed'))
                $item->feed($data);
            else
                foreach ($data as $name => $value)
                    if ($item->hasAttribute($name)) {
                        $item->$name = $value;
                    }

            $this->items[$k] = $item;
        }

        return;
    }

    /**
     * @brief override parent::getItemCount()
     * @return integer
     */
    public function getItemCount()
    {
        return array_sum(\yii\helpers\ArrayHelper::getColumn($this->items, 'quantity'));
    }

    /**
     * @brief override parent::getItemsOfCompany()
     * - because items are dynamic object, not fetched by ActiveQuery::find()
     * @var $id int company_id
     */
    public function getItemsOfCompany($id)
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($item->company->company_id == $id)
                $items[] = $item;
        }

        return $items;
    }

    /**
     * @brief merge identical items
     * [A,B,C,A,D] becomes [B,C,A,D] 重複していたら若い番号の方が消去される
     */
    public function mergeItems()
    {
        $newItems = [];

        foreach ($this->items as $item) {
            if ($item->quantity <= 0)
                continue;

            foreach ($newItems as $i => $newItem) {
                $identical = true;
                foreach (['name', 'code'] as $attr) {
                    $identical = $identical && ($item->$attr == $newItem->$attr);
                }
                if ($identical) {

                    $item->quantity += $newItems[$i]->quantity;
                    unset($newItems[$i]);
                    break;
                }
            }
            $newItems[] = $item;
        }
        $this->items = $newItems;

        // おまけ: company_id を決定する
        $companies = [];
        foreach ($newItems as $i)
            $companies[] = $i->company->company_id;
        if (1 == count(array_unique($companies)))
            $this->company_id = array_shift($companies);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $campaign_id = null;

            if (!$this->isNewRecord && $runValidation && (null === $attributeNames)) { { // update company_id
                    $cid = ArrayHelper::getColumn($this->items, 'company.company_id');
                    $cid = array_unique($cid);

                    if (1 == count($cid))
                        $this->company_id = array_shift($cid);
                    else
                        $this->company_id = null;
                }

                PurchaseItem::deleteAll('purchase_id = :id', [
                    ':id' => $this->purchase_id,
                ]);

                PurchaseDelivery::deleteAll('purchase_id = :id', [
                    ':id' => $this->purchase_id,
                ]);
            }

            $this->compute(false);


            // TODO:HE代理店にTY商品を卸す
            if (Branch::PKEY_ROPPONMATSU == $this->branch_id && $this->customer && $this->customer->isAgencyOf(Company::PKEY_HE)) {
                $this->is_com_off = in_array(1, ArrayHelper::getColumn($this->items, 'is_wholesale'));
                // var_dump($this->is_com_off);
                // 納品書はcompany_id=3 販売者がHEとなるように変更
                $this->company_id = Company::PKEY_HE;
            }

            // 伝票データを作成
            if (!parent::save($runValidation, $attributeNames)) {
                var_dump($this->errors);
                exit;
                throw new \yii\db\Exception('failed to save dtb_purchase');
            }
            // TODO:HE代理店にTY商品を卸す
            if ($this->is_com_off) {
                $com_off_purchase = \common\models\ComOffPurchase::findOne($this->purchase_id);
                if (!$com_off_purchase)
                    $com_off_purchase = new \common\models\ComOffPurchase();

                $com_off_purchase->feed($this->dump());
                $rating = $this->customer->getAgencyRating(Company::PKEY_HE) ? $this->customer->getAgencyRating(Company::PKEY_HE)->discount_rate : 20;
                $com_off_purchase->customer_msg = $this->purchase_id . " " . $com_off_purchase->customer_msg;
                $com_off_purchase->note = "$rating";
                if (!$com_off_purchase->save($runValidation, $attributeNames)) {
                    var_dump($com_off_purchase->errors);
                    exit;
                    throw new \yii\db\Exception('伝票データ保存時にエラーが発生しました。恐れ入りますがサポートまでご連絡下さい');
                }
            }

            $pkey = $this->purchase_id;
            $seq = 0;
            foreach ($this->items as $item) {
                // アイテムが代理店卸売か
                $wholesale = $item->is_wholesale;
                // キャンペーンID
                $campaign_id = $item->campaign_id;
                $tax_rate = $item->getTaxRate();

                if (method_exists($item, 'convertToPurchaseItem')) {
                    $model = $item->convertToPurchaseItem($seq++, [
                        'purchase_id' => $pkey,
                    ]);
                    // 配列になる場合・・・オリジナルレメディー
                    if (is_array($model)) {
                        foreach ($model as $index => $i) {
                            $i->is_wholesale = $wholesale;
                            $i->tax_rate = $tax_rate;
                            $i->campaign_id = $campaign_id;
                            $i->sku_id = $i->getSkuId();
                            $this->saveItem($i);
                        }
                        $seq += count($model) - 1;
                    } else {
                        $model->seq = $seq++;
                        $model->is_wholesale = $wholesale;
                        $model->tax_rate = $tax_rate;
                        $model->sku_id = $model->getSkuId();
                        $this->saveItem($model);
                    }
                } else {
                    $item->seq = $seq++;
                    $item->is_wholesale = $wholesale;
                    $item->tax_rate = $tax_rate;
                    $item->sku_id = $item->getSkuId();
                    $this->saveItem($item);
                }

                if (Yii::$app->id === 'app-backend') {
                    $d_product = ($item->model instanceof Product) ? \common\models\ProductDiscount::find()->where(['product_id' => $item->model->product_id])->one() : null;
                    $sodan_query = \common\models\ProductSubcategory::find()->where(['ean13' => $item->code]);
                    $sodan_ticket = \yii\helpers\ArrayHelper::map($sodan_query->all(), 'ean13', 'subcategory_id');
                    if ($d_product) {
                        for ($i = 0; $i < $item->qty; $i++) {
                            $ticket = new \common\models\DiscountProductLog([
                                'customer_id' => $this->customer->customer_id,
                                'discount_id' => $d_product->discount_id,
                                'use_count'   => $d_product->use_count,
                                'subcategory_id' => (in_array(Subcategory::PKEY_SODAN_TICKET, $sodan_ticket)) ? Subcategory::PKEY_SODAN_TICKET : null
                            ]);
                            $ticket->save();
                        }
                    }
                }
            }

            $this->insertToranokoItems($seq++);

            if ($this->delivery) {
                $this->delivery->purchase_id = $this->purchase_id;
                $this->delivery->save(false);
            }

            if ($this->customer_id) {
                $pt = $this->customer->currentPoint();
                if ($pt != $this->customer->point) {
                    $this->customer->point = $pt;
                    $this->customer->save(false, ['point']);
                }
            }
        } catch (\yii\db\Exception $e) {
            var_dump($e);
            exit;
            Yii::warning($e->__toString(), $this->className() . '::' . __FUNCTION__);

            $transaction->rollBack();
            return false;
        }
        $transaction->commit();

        return true;
    }

    /**
     * 熱海から発送するWEB注文に とらのこ年会費 が含まれていたら会員証と会報誌を送付する（かもしれない）
     */
    private function insertToranokoItems($seq)
    {
        if ($this->branch_id != Branch::PKEY_ATAMI) // 熱海発送じゃないので中止
            return;

        if (!$customer = $this->customer) // 顧客IDが未定義なので中止
            return;

        if (!parent::getItems()->andWhere(['product_id' => [
            Product::PKEY_TORANOKO_G_ADMISSION,
            Product::PKEY_TORANOKO_N_UPGRADE,
            Product::PKEY_TORANOKO_N_ADMISSION
        ]])
            ->exists())
            return; // とらのこ年会費を含んでいないので中止

        $items = [];

        if (($code = $customer->membercode) &&
            $code->isVirtual()
        ) // 仮会員証を使っている顧客には会員証を送付する
        {
            $query = Product::find()->active()->where(['name' => '豊受モール会員証']);
            if ($query->exists())
                $items[] = $query->one();
            else
                Yii::error([
                    '[豊受モール会員証]の検索に失敗しました',
                    $query->createCommand()->sql,
                    $query->createCommand()->params
                ]);
        }

        $q1 = parent::getItems()->andWhere(['product_id' => Product::PKEY_TORANOKO_N_UPGRADE]);
        $q2 = parent::getItems()->andWhere(['product_id' => Product::PKEY_TORANOKO_G_ADMISSION]);
        if ($q1->exists() || ($q2->exists() && !$customer->isToranoko() /*既存の会員なら会報誌送付済みと見なす*/)) {   // INSERT とらのこ会報誌
            $query = Product::find()
                ->active()
                ->andWhere(['like', 'name', 'Oasis%', false]);

            if (!$query->exists())
                Yii::error([
                    '[とらのこ会報誌オアシス]の検索に失敗しました',
                    $query->createCommand()->sql,
                    $query->createCommand()->params
                ]);

            foreach ($query->all() as $model) {
                $items[] = $model;
            }
        }

        foreach ($items as $i) {
            if (parent::getItems()->where(['product_id' => $i->product_id])->exists())
                continue; // selfにてINSERT済み

            $q2 = $customer->getPurchases()->active()
                ->innerJoinWith('items')
                ->andWhere(['dtb_purchase_item.product_id' => $i->product_id]);

            if ($q2->exists())
                continue; // 他の売り上げにて購入済み

            $item = new PurchaseItem([
                'seq'        => $seq++,
                'product_id' => $i->product_id,
                'code'       => $i->code,
                'name'       => $i->name,
                'price'      => $i->price,
                'quantity'   => 1,
                'company_id' => Company::PKEY_HE,
                'discount_amount' => $i->price,
            ]);
            $this->saveItem($item);
        }
    }

    private function saveItem($item)
    {
        $item->purchase_id = $this->purchase_id;
        if (!$item->save()) {
            Yii::error($item->errors);
            throw new \yii\db\Exception('failed to save dtb_purchase_item');
        }

        // TODO:HE代理店にTY商品を卸す
        if ($this->is_com_off) {
            $com_off_item = new \common\models\ComOffPurchaseItem();
            $com_off_item->feed($item->dump());
            if (!$com_off_item->save()) {
                Yii::error($com_off_item->errors);
                throw new \yii\db\Exception('failed to save dtb_com_off_purchase_item');
            }
        }

        return true;
    }

    private function isComplexRemedy($item)
    {
        if (0 < $item->product_id)
            return false;

        if (null !== $item->parent)
            return true;

        foreach ($this->items as $i) {
            if ($i->parent === $item->seq)
                return true;
        }

        return false;
    }
   
 public function removeDiscoutItemSession($item)
    {
        $discount_items = Yii::$app->session->get('discount_items');
        if($discount_items && isset($discount_items["'" . $item->code . "'"])){
            unset($discount_items["'" . $item->code . "'"]);
            Yii::$app->session->set('discount_items', $discount_items);
        }
    }

  public function removeAllDiscoutItemSession(){
        Yii::$app->session->set('discount_items', []);
    }


}
