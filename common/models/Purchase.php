<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the model class for table "dtb_purchase".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Purchase.php $
 * $Id: Purchase.php 4223 2020-01-14 05:05:03Z mori $
 *
 * @property integer $purchase_id
 * @property integer $customer_id
 * @property integer $subtotal
 * @property integer $tax
 * @property integer $tax10_price
 * @property integer $tax8_price
 * @property integer $taxHP_price
 * @property integer $include_frozen
 * @property integer $frozen_items_count
 * @property integer $postage
 * @property integer $postage_frozen
 * @property integer $receive
 * @property integer $change
 * @property integer $payment_id
 * @property integer $paid
 * @property integer $shipped
 * @property integer $shipping_id
 * @property integer $shipping_frozen_id
 * @property integer $include_pre_order
 * @property string $create_date
 * @property string $update_date
 * @property string $note
 *
 * @property DtbCustomer $customer
 * @property MtbPayment $payment
 * @property DtbPurchaseDeliv[] $dtbPurchaseDelivs
 * @property DtbPurchaseItem[] $dtbPurchaseItems
 */
class Purchase extends \yii\db\ActiveRecord
{
    const SCENARIO_CREATE = 'create';
    const SCENARIO_CASHER = 'casher';

    private $_companies = null;
    private $_itemsOfCompany = [];
    private $_itemsOfFrozen = [];

    public $is_agency;
    public $agencies;
    public $direct_code;

    public $school_product;

    // 冷凍便を含むか
    public $frozen_total;
    public $chilled_total;

    // 軽減税率対応
    public $tax_total;
    public $reduced_tax_total;

    // TODO:HE代理店にTY商品を卸す
    public $is_com_off = false;

    // pickcodeを持たない商品があるか（RXT作業ありか）
    public $include_rxt; // 0:なし 1:あり 99:すべて 
    
    /* @inheritdoc */
    public static function tableName()
    {
        return 'dtb_purchase';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        $params = [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date', 'update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    if ($event->name == 'beforeInsert' && $event->sender->create_date)
                        return $event->sender->create_date;
                    return date('Y-m-d H:i:s');
                },
            ],
            'commission' => [
                'class' => FixCommission::className(),
            ],
            'toranoko' => [
                'class'  => FixMembership::className(),
                'owner'  => $this,
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];

        if ('app-backend' == Yii::$app->id)
            $params[] = [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by', 'staff_id'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['staff_id'],
                ],
                'value' => function ($event) {
                    return Yii::$app->user->id;
                },
            ];

        return $params;
    }

    /* @inheritdoc */
    public function rules()
    {
        // 現在ポイントがマイナスの場合は最大値０としてチェックする
        $current_point = (0 <= (int)ArrayHelper::getValue($this, 'customer.point')) ? (int)ArrayHelper::getValue($this, 'customer.point') : 0;

        // ポイント使用前のお支払合計
        //$total_charge_exp_point = $this->subtotal + $this->tax + $this->discount + $this->postage + $this->handling;
        $total_charge_exp_point = $this->total_charge + $this->point_consume;
        // 値引き使用前のお支払合計
        $total_charge_exp_discount = $this->subtotal + $this->tax + $this->point_consume + $this->postage + $this->handling;
        $rules = [
            [['subtotal', 'tax', 'tax10_price', 'tax8_price', 'taxHP_price', 'tax10_point', 'tax8_point', 'total_charge'], 'default', 'value' => 0],
            [['subtotal', 'tax', 'tax10_price', 'tax8_price', 'taxHP_price', 'total_charge', 'postage_frozen', 'include_frozen'], 'default', 'value' => 0],
            [['customer_id', 'total_charge', 'tax', 'discount', 'postage', 'receive', 'change', 'payment_id', 'paid', 'shipped', 'staff_id', 'taxedSubtotal', 'agent_id'], 'integer'],
            [['subtotal'], 'integer', 'max' => 5 * 1000 * 1000, 'tooBig' => 'ご注文は1回あたり小計500万円まででお願いします'],
            [['postage', 'handling'], 'integer', 'when' => function () {
                return ('app-backend' == Yii::$app->id);
            }],
            [['total_charge', 'subtotal', 'tax', 'payment_id'], 'required'],
            [['create_date', 'update_date', 'staff_id'], 'safe'],
            [['status'], 'default', 'value' => PurchaseStatus::PKEY_INIT],
            ['branch_id', 'exist', 'targetClass' => Branch::className()],
            ['company_id', 'exist', 'targetClass' => Company::className()],
            ['customer_id', 'exist', 'targetClass' => Customer::className(), 'when' => function ($model) {
                return ($model->customer_id);
            }],
            ['customer_id', 'exist', 'targetClass' => Customer::className(), 'when' => function ($model) {
                return ($model->agent_id);
            }],
            ['campaign_id', 'exist', 'targetClass' => Campaign::className(), 'when' => function ($model) {
                return ($model->campaign_id && $model->campaign_id != 0);
            }],
            ['campaign_id', 'default', 'value' => function ($model) {
                // 仮想店舗＝フロントではデフォルト適用しないように変更
                if ($model->branch_id != Branch::PKEY_FRONT || !isset($model->campaign_id)) {
                    $campaign = Campaign::getCampaignOneWithBranch($model->branch_id);
                    return ($campaign) ? $campaign->campaign_id : null;
                }
            }],
            [['note', 'customer_msg'], 'string'],
            //            [['note','customer_msg'], 'string', 'max' => 255],
            //            ['point_consume', 'compare','compareAttribute'=>'subtotal', 'operator'=>'<=','message'=>"ポイント値引きが小計を超えています"],
            ['point_consume', 'integer', 'min' => 0, 'max' => $total_charge_exp_point, 'tooBig' => "ポイント値引きがお支払い合計を超えています"],
            ['point_consume', 'integer', 'min' => 0, 'max' => $current_point, 'tooBig' => "ポイント値引きが保有ポイントを超えています", 'when' => function ($model) {
                return $model->customer_id;
            }, 'skipOnError' => true],
            ['point_consume', 'integer', 'min' => 0, 'max' => 0, 'tooBig' => "お客様の指定がないためポイント値引きは適用できません", 'when' => function ($model) {
                return !$model->customer_id;
            }, 'skipOnError' => true],
            ['receive', 'compare', 'compareAttribute' => 'total_charge', 'operator' => '>=', 'message' => "お預かりが不足しています",'when' => function ($model) {
                return $this->payment_id == Payment::PKEY_CASH;
            }, 'skipOnError' => true],
            ['discount', 'compareWithSubtotal'],
            ['payment_id', 'exist', 'targetClass' => Payment::className()],
            ['payment_id', 'validatePayment'],
            ['email', 'default', 'value' => function ($model) {
                return ($c = $model->customer) ? $c->email : null;
            }],
            [['shipped', 'paid', 'frozen_items_count'],     'default', 'value' => 0],
            [['created_by', 'staff_id'], 'exist', 'targetClass' => \backend\models\Staff::className(), 'targetAttribute' => 'staff_id'],
            [['created_by', 'staff_id'], 'default', 'value' => null],
            [['is_agency', 'agencies', 'tax_total', 'reduced_tax_total', 'shipping_id', 'delivery_company_id', 'arrangement_date', 'postage_frozen', 'shipping_frozen_id', 'include_frozen', 'frozen_items_count', 'shipping_mail_date', 'shipping_frozen_mail_date', 'include_pre_order', 'is_com_off'], 'safe'],
        ];

        /* if($this->scenario == self::SCENARIO_CASHER)
           $rules = array_merge($rules, [
           [['branch_id','payment_id','receive','change','staff_id'], 'required', ],
           [['handling','postage','point_consume'], 'default', 'value'=>0],
           [['shipped','paid'],     'default', 'value'=>1],
           [['payment_id'],  'default', 'value' => \common\models\Payment::PKEY_CASH],
           [['payment_id'],  'in', 'range' => [\common\models\Payment::PKEY_CASH]],
           [['receive'], 'required', 'message'=>'お預りを入力してください'],
           [['branch_id'],   'exist', 'targetClass'=>\common\models\Branch::className() ],
           [['customer_id'], 'exist', 'targetClass'=>\common\models\Customer::className() ],
           [['payment_id'],  'exist', 'targetClass'=>\common\models\Payment::className() ],
           [['receive'], 'compare', 'compareValue'=>'total_charge', 'operator' => '>=','message'=>'お預りが不足しています'],
           ]); */

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return array_merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['subtotal', 'tax', 'point_consume', 'discount'],
            self::SCENARIO_CASHER => $this->attributes,
        ]);
    }

    public static function primaryKey()
    {
        return ['purchase_id'];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'purchase_id'   => "注文番号",
            'company_id'    => "販社",
            'branch_id'     => "受付",
            'customer_id'   => "顧客",
            'total_charge'  => "お支払い合計",
            'subtotal'      => "商品計",
            'taxedSubtotal' => "お会計",
            'tax'           => "消費税",
            'tax10_price'   => "税込（標準）",
            'tax8_price'    => "税込（軽減）",
            'taxHP_price'   => "税込（出版）",
            //'tax10_point'   => "Pt（標準）",
            //'tax8_point'    => "Pt（軽減）",
            'point_consume' => "モールPt値引き",
            'point_given'   => "Pt付与",
            'include_frozen'       => "冷凍便有無",
            'postage'       => "送料",
            'postage_frozen'       => "送料（冷凍便）",
            'handling'      => "代引手数料",
            'discount'      => "現金値引き",
            'receive'       => "お預かり",
            'change'        => "おつり",
            'payment_id'    => "お支払い方法",
            'paid'          => "入金の状態",
            'shipped'       => "発送の状態",
            'create_date'   => "購入日",
            'shipping_id'   => "送り状番号",
            'shipping_frozen_id'   => "送り状番号（冷凍便）",
            'shipping_mail_date'   => "出荷メール送信日",
            'shipping_frozen_mail_date'   => "出荷メール送信日（冷凍便）",
            'delivery_company_id'       => "配送会社",
            'arrangement_date'       => "出荷手配日",
            'update_date'   => "更新日",
            'note'          => "お客様への言葉（納品書備考欄）",
            'customer_msg'  => "備考",
            'status'        => "ステータス",
            'delivery'      => "お届け先",
            'is_agency'     => '代理店',
            'agencies'      => '代理店所属',
            'agent_id'      => 'サポート注文_代行者ID',
            'include_pre_order' => '予約商品有無',
            'payment_amount'    => '現金',
            'gift_certificate'    => '商品券',
            'gnavi_point'    => 'ぐるなびPt',
            'hotpepper_point'    => 'ホットペッパーPt',
        ];
    }

    public function attributeHints()
    {
        return [
            'customer_msg' => '当社へのメッセージをご記入いただけます',
            'note'         => 'お客様へのメッセージを入力します。納品書の備考欄に印刷されます。',
            'email'        => 'ご注文の確定後、メールを配信します',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'is_agency';      //the value must be the same as the new attribute
        $fields[] = 'agencies';
        $fields[] = 'agent_id';
        return $fields;
    }

    public function cancelate()
    {
        if (PurchaseStatus::PKEY_CANCEL <= $this->status)
            return false;

        $this->status = PurchaseStatus::PKEY_CANCEL;

        return $this->save(false);
    }

    /* validate `discount` */
    public function compareWithSubtotal($attr, $param)
    {
        //        if($this->discount <= $this->subtotal)
        if ($this->discount <= $this->total_charge + $this->discount)
            return true;

        $this->addError($attr, "値引きが大きすぎます");

        return false;
    }

    /* @return void */
    public function compute()
    {
        
        if (null === $this->payment_id) {
            $this->payment_id =  Payment::PKEY_CASH;
        }
        
        if (0 == count($this->items)) {
            $attrs = ['total_charge', 'subtotal', 'tax', 'discount', 'postage', 'handling', 'change', 'point_given'];
            foreach ($attrs as $attr) {
                $this->$attr = 0;
            }

            return;
        }

        //  予約商品の有無
        $this->include_pre_order = false;

        // 冷凍便の計算
        $this->include_frozen = false;
        $this->frozen_total = 0;
        $this->chilled_total = 0;
        $this->frozen_items_count = 0;

        // 商品計の計算
        $this->subtotal = 0;

        // 代行発送の場合（もう不要かな？？ 2018/07/29 kawai）
        if ($this->payment_id == Payment::PKEY_DROP_SHIPPING) {
            if (!$this->customer || !$this->customer->isAgency()) {
                $this->addError('payment_id', "この支払い方法は代理店にのみ適用可能です");
                // 商品計
                $this->subtotal = array_sum(\yii\helpers\ArrayHelper::getColumn($this->items, 'unitPrice', 0));
            } else {
                $this->subtotal = 0;
                // アイテムごとの単価＊個数の合計、アイテムの消費税を計算する
                foreach ($this->items as $item) {
                    if ($this->customer->isAgencyOf($item->company->company_id)) {
                        $this->subtotal += $item->getUnitPrice() * $item->quantity;
                        //$item->unitTax = Yii::$app->tax->compute($item->unitPrice);
                        $item->unitTax = \common\models\Tax::compute($item->unitPrice);
                    } else {
                        $this->subtotal += $item->charge;
                    }
                }
            }
        }

        foreach (['receive', 'discount', 'point_consume', 'postage', 'handling'] as $attr)
            $this->$attr = (int) $this->$attr;


        // 消費税
        $tax = 0;
        // 軽減税率を別に集計する
        $tax_total = 0;
        $reduced_tax_total = 0;

        $tax10_price = 0;
        $tax8_price = 0;
        $taxHP_price = 0;

        // 値引きキャンペーン適用外商品を除きポイント付与
        $point_given = 0;
        $item_point_consumes = 0;
        $campaign_type = 0;

        if ($this->campaign)
            $campaign_type = Campaign::findOne($this->campaign_id)->campaign_type;

        foreach ($this->items as $index => $item) {
            // 冷凍商品があるかチェックし数、額を加算していく
            // 予約商品チェックを追加 2021/01/26
            if ($item instanceof \common\models\PurchaseItem || $item instanceof \common\components\cart\ProductItem) {
                $product_item = $item->model;
                if (($product_item instanceof \common\models\Product) && $product_item->pre_order == 1) {
                    $this->include_pre_order = true;
                }

                if (($product_item instanceof \common\models\Product) && $product_item->cool_id == \common\models\Product::COOL_FROZEN) {
                    $this->include_frozen = true;
                    $this->frozen_items_count += 1;
                    $this->frozen_total += $item->getUnitPrice() * $item->quantity;
                }
            }

            // 商品合計（税込み） = 販売単価 * 個数
            $this->subtotal += $item->getUnitPrice() * $item->quantity;
            // 消費税 = 消費税1アイテムあたりの金額＊個数
            //$item->unitTax = Yii::$app->tax->compute($item->getUnitPrice());
            $item->unit_tax = null;

            // 各種税込価格の合計を計算する　販売単価＋消費税＊個数
            $taxed_price = ($item->unit_price + $item->getUnitTax()) * $item->quantity;

            //var_dump($item->name, $item->unit_price, $item->getUnitTax(), $taxed_price);

            if ($this->create_date && strtotime($this->create_date) <= \common\models\Tax::newDate()) {
                $tax += $item->getUnitTax(true) * $item->quantity;
            } else {
                if ($item->isReducedTax()) {
                    $reduced_tax_total += ($item->getUnitTax() * $item->quantity);
                    $tax8_price += $taxed_price;
                } else {
                    $item->company->company_id == \common\models\Company::PKEY_HP ? $taxHP_price += $taxed_price : $tax10_price += $taxed_price;
                    $tax_total += ($item->getUnitTax() * $item->quantity);
                }
            }

            // 値引きキャンペーンがセットされていたアイテムの場合、付与ポイントは0とする
            if ($item->campaign_id && $campaign_type == Campaign::DISCOUNT) {
                $item->setPointAmount(0);
                $item->setPointRate(0);
                continue;
            }

            $point_given += $item->pointTotal;
        }
        //var_dump("ポイント値引き前", $this->subtotal, $tax_total, $reduced_tax_total);
        // ポイント値引き有りの場合の計算方法（旧）

        // reduce given point when (0 < point_consume)
        if ((0 < $point_given) && (0 < $this->point_consume)) {
            $numer = $this->subtotal - $this->point_consume;
            $denom = $this->subtotal;

            $point_given = floor($point_given * ($numer / $denom));
        }

        //var_dump($tax_total,$reduced_tax_total, $tax, $tax10_price, $taxHP_price, $tax8_price);
        // 試作
        $taxed_subtotal = $tax8_price + $tax10_price + $taxHP_price;
        $this->tax10_price = $tax10_price;
        $this->tax8_price = $tax8_price;
        $this->taxHP_price = $taxHP_price;

        if (0 < $this->point_consume) {
            //var_dump($tax8_price, $tax10_price, $taxHP_price, $taxed_subtotal, $this->postage, $this->handling);
            $tax10_discount_price = $tax10_price;
            $tax8_discount_price = $tax8_price;
            $taxHP_discount_price = $taxHP_price;

            // ポイント値引きの按分のため、合計金額に対しての比率（値引き率）を求める
            // 【ポイント値引き額】÷【合計金額】× 100　※小数点第2位で四捨五入
            $point_discount_rate = $this->point_consume / $taxed_subtotal * 100;
            //var_dump($point_discount_rate);
            $point_discount_rate = round($this->point_consume / $taxed_subtotal * 100, 1);
            //var_dump($point_discount_rate);

            $tax8_discount = sprintf('%.2f', $tax8_price * $point_discount_rate / 100);
            //var_dump($tax8_discount);
            $tax10_discount = sprintf('%.2f', $tax10_price * $point_discount_rate / 100);
            //var_dump($tax10_discount);

            $taxHP_discount = sprintf('%.2f', $taxHP_price * $point_discount_rate / 100);
            //var_dump($taxHP_discount);

            // 上記の按分結果は、小数点以下で切り捨てる。切り捨ての結果の合計が値引き額と差額がでた場合は、切り捨てた端数（小数点以下）の一番大きなところに、差額を足しこむ。
            //var_dump($this->point_consume, floor($tax8_discount) + floor($tax10_discount) + floor($taxHP_discount));
            $point_discount = floor($tax8_discount) + floor($tax10_discount) + floor($taxHP_discount);
            if ($this->point_consume != $point_discount) {
                if ($tax8_discount > 0) {
                    $tmp8 = explode(".", $tax8_discount);
                    $data8 = substr($tmp8[1], 0, 2);;
                    //var_dump($data10);
                } else {
                    $data8 = 0;
                }
                if ($tax10_discount > 0) {
                    $tmp10 = explode(".", $tax10_discount);
                    $data10 = substr($tmp10[1], 0, 2);
                    //var_dump($data10);
                } else {
                    $data10 = 0;
                }
                if ($taxHP_discount > 0) {
                    $tmpHP = explode(".", $taxHP_discount);
                    $dataHP = substr($tmpHP[1], 0, 2);
                    //var_dump($data10);
                } else {
                    $dataHP = 0;
                }
                $dataArray = array('tax10_discount' => $data10, 'tax8_discount' => $data8, 'taxHP_discount' => $dataHP);
                //var_dump($dataArray);
                $remain = $this->point_consume - $point_discount;
                $max = max(array_values($dataArray));
                if (($key = array_search($max, $dataArray)) !== false) {
                    //echo "キーが{$key}の値が{$max}です。";
                    //var_dump("比率が0.01だけ足りないと仮定");
                    ${$key} += $remain;
                }
            }
            //var_dump($tax8_discount, $tax10_discount, $taxHP_discount, $this->point_consume);

            // 値引き額を税率内訳に反映させる
            $tax8_discount_price = $tax8_price - floor($tax8_discount);
            $tax10_discount_price = $tax10_price - floor($tax10_discount);
            $taxHP_discount_price = $taxHP_price - floor($taxHP_discount);
            //var_dump($tax8_discount_price, $tax10_discount_price, $taxHP_discount_price, $this->point_consume);

            //var_dump($tax8_discount_price, $tax10_discount_price);exit;
            // 割り返しをやめる
            //$tax_total = floor($tax10_discount_price / (100+\common\models\Tax::findOne(1)->getRate()) * \common\models\Tax::findOne(1)->getRate());
            //$tax_total += floor($taxHP_discount_price / (100+\common\models\Tax::findOne(1)->getRate()) * \common\models\Tax::findOne(1)->getRate());
            //$tax_total -= ($this->postage + $this->handling);
            //$reduced_tax_total = floor($tax8_discount_price / (100+ \common\models\Tax::findOne(2)->getRate()) * \common\models\Tax::findOne(2)->getRate());
            //var_dump($tax_total, $reduced_tax_total);             
            //$this->subtotal = $tax8_discount_price + $tax10_discount_price + $taxHP_discount_price - $tax_total - $reduced_tax_total;
            //var_dump($tax8_price, $tax10_price, $taxHP_price); 
            //var_dump($tax8_discount, $tax10_discount, $taxHP_discount, $tax8_discount_price, $tax10_discount_price, $taxHP_discount_price, $tax_total, $reduced_tax_total, $this->subtotal+$this->postage+$this->handling+$tax_total+$reduced_tax_total);
            $this->tax10_price = $tax10_discount_price;
            $this->tax8_price = $tax8_discount_price;
            $this->taxHP_price = $taxHP_discount_price;
        }

        if (!($this->create_date && strtotime($this->create_date) <= \common\models\Tax::newDate())) {
            $tax = $tax_total + $reduced_tax_total;
        }


        // フロント、熱海、六本松以外、レジ利用時、銀行振込OR口座振替を選択した場合は、送料を０とする
        if (!in_array($this->branch_id, [Branch::PKEY_ROPPONMATSU, Branch::PKEY_ATAMI, Branch::PKEY_FRONT]) && in_array($this->payment_id, [Payment::PKEY_BANK_TRANSFER, Payment::PKEY_DIRECT_DEBIT])) {
            $this->postage = 0;
        }

        /*
 // この処理はもう使われない
        // 2017/12/22：豊受自然農の無添加おせち　も送料を0円とする
        if(($this->branch_id == Branch::PKEY_ROPPONMATSU && $this->items[0]->getModel()->product_id == Product::PKEY_OSECHI) || ($this->branch_id == Branch::PKEY_FRONT && count($this->items) > 0 && isset($this->items[0]->getModel()->product_id) && $this->items[0]->getModel()->product_id == Product::PKEY_OSECHI)) {
                $this->postage = 0;
        }
        else
        {
            // 送料計算をした後に、手数料計算をする
            $this->postage  = $this->computePostage();
        }

        // 2017/12/22：豊受自然農の無添加おせち　送料を0円とする
        if(($this->branch_id == Branch::PKEY_ROPPONMATSU && $this->items[0]->getModel()->product_id == Product::PKEY_OSECHI) || ($this->branch_id == Branch::PKEY_FRONT && count($this->items) > 0 && isset($this->items[0]->getModel()->product_id) && $this->items[0]->getModel()->product_id == Product::PKEY_OSECHI)) {
            $this->handling =  0;
        } else {
            $this->handling = $this->computeHandling();
        }
*/
        // 送料計算
        if ($this->include_frozen) {
            // 冷凍便対象商品込と判明したため、専用の処理で再計算
            $this->computeIncludeFrozenPostage();
            //
        } else {
            // 従来の計算
            $this->postage  = $this->computePostage();
        }

        // カート内に商品ID=4840のみの場合、送料をゼロにする
        if(count($this->items) == 1) {
            foreach($this->items as $item) {
                $item = $item->getModel();
                if(isset($item->product_id) && $item->product_id === \common\models\Product::PKEY_NOTO_SUPPORT) {
                    $this->postage = 0;
                }
            }
        }


        $this->handling = $this->computeHandling();

        /*
        // 2020/10/02 : カートの中が学校資料（product_id=2095、2096、2097）のときは送料をゼロにする
        $this->school_product = true;
        foreach($this->items as $index => $item) {

            if($item instanceof \common\models\PurchaseItem || $item instanceof \common\components\cart\ProductItem) {
                $item = $item->model;
                if(!($item instanceof \common\models\Product) || !in_array($item->product_id, [2095,2096,2097])) {
                    $this->school_product = false;
                    break;
                }
            }
        }

        if($this->school_product === true) {
            $this->postage = 0;
            $this->handling = 0;
        }
        //   
*/
        // 上で計算した消費税合計値を$this->taxにセット
        $this->tax = $tax;
        // 商品税込み合計（お会計）を計算
        $this->setTaxedSubtotal($this->subtotal + $this->tax);


        // 税（標準）の金額をセット
        $price = $this->point_consume > 0 ? $tax10_discount_price : $tax10_price;
        $this->tax10_price = $price;

        /*
        // ポイント使用額の計算
        $keys = array_keys($this->items);
        $remain_rate = 100;

        foreach ($this->items as $index => $item) {

            if(0 < $this->point_consume) {
                // ポイント使用割合をアイテムごとに求めて、それを元に使用額・付与額を決定する。割合の合計が１００になるようにする
                if($index != end($keys)){
                    $item->point_consume_rate = floor(($item->getUnitPrice() + $item->getUnitTax()) * $item->qty / ($this->subtotal + $this->tax)*100);
                    $remain_rate -= $item->point_consume_rate;
                } else {
                    $item->point_consume_rate = $remain_rate;
                }
                 // 求めたrateから割り当てるポイント使用額を算出して格納する
                $item->point_consume = round($this->point_consume * $item->point_consume_rate / 100);
                    
            } else {
                $item->point_consume = 0;
            }
            $item_point_consumes += $item->point_consume;
            // ポイント付与額（１個あたり）　＝　（　販売価格（１個あたり）－　ポイント使用額（１個あたり））×　ポイント率　（切り捨て）　※但し、マイナスの場合はゼロ
            if($item->setPointAmount(floor(($item->getUnitPrice() - ($item->getPointConsume()/$item->qty)) * $item->getPointRate() / 100)) < 0)
                $item->setPointAmount(0);

            $point_given += $item->pointTotal;
        }

        // モール負担額
        $this->mall_expense = $this->point_consume - $item_point_consumes;
*/
        $this->point_given = $point_given;

        // point_given >= 0
        if ($this->point_given < 0) {
            $this->point_given = 0;
        }

        //$this->total_charge = $this->subtotal
        $this->total_charge = $this->tax10_price + $this->tax8_price + $this->taxHP_price
            //+ $this->tax
            + $this->postage
            + $this->handling
            //- $this->point_consume // ポイント値引きを反映させるようにするのでここで加算は不要となる
            - $this->discount;

        //var_dump("計算結果：", $this->total_charge, $this->tax10_price, $this->tax8_price, $this->tax, $this->postage, $this->handling, $tax_total, $reduced_tax_total);

        if (Payment::PKEY_CREDIT_CARD == $this->payment_id) {
            $this->point_given = 0;

            // クレジットカード使用時はすべてのアイテムのポイントが０となる
            foreach ($this->items as $index => $item) {
                $item->setPointAmount(0);
                $item->setPointRate(0);
                if ($item->campaign_id && $campaign_type == Campaign::POINT) {
                    // 6/9京都イベント対応 判定式追加
                    if ($item->campaign_id != 175) {
                        $item->campaign_id = null;
                    }
                }
            }
        }


        $this->change = $this->receive - $this->total_charge;
        return;
    }

    /**
     * PurchaseItemから値引・ポイント率等のパラメータを元に付与ポイントを計算する
     * @param PurchaseItem $item 購入アイテム
     * @return int point_given 付与ポイント
     */
    protected function computePoints($item)
    {
        $point_given = 0;
        // アイテム単位の値引きがある場合に、ポイントは付与しない（要確認）
        if (0 < $item->discount_amount)
            $item->point_amount = 0;

        // キャンペーン対象となっていない、かつ、値引きをしていないアイテムについてのみポイント付与を行なう
        if (!($item->campaign_id) && !(0 < $item->discount_amount)) {

            // ポイント使用がある場合に、按分を行なう
            if (0 < $this->point_consume) {
                $distribution = ($item->price - $item->discount_amount) / $this->subtotal;
                $item->point_amount = floor((($item->price - $item->discount_amount) - ($this->point_consume * $distribution)) * $item->point_rate / 100);
                if ($item->point_amount < 0)
                    $item->point_amount = 0;
            } else {
                $item->setPointAmount($item->price * $item->point_rate / 100);
            }
            $point_given += $item->pointTotal;
        }

        return $point_given;
    }

    protected function computeHandling()
    {
        if (!isset($this->delivery) || !isset($this->delivery->pref_id))
            return 0;

        $model = new Handling([
            'charge'     => ($this->subtotal + $this->tax - $this->point_consume) + $this->postage,
            'company_id' => $this->company_id,
            'payment_id' => $this->payment_id,
        ]);

        // トミーローズの場合のみ、小計だけで計算する
        if ($this->company_id == Company::PKEY_TROSE) {
            $model->charge = $this->subtotal;
        }
        return $model->value;
    }

    protected function computePostage()
    {
        if (!isset($this->delivery) || !isset($this->delivery->pref_id) || $this->scenario == self::SCENARIO_CREATE)
            return 0;

        $model = new Postage([
            // 送料の計算から、消費税を除外
            //            'taxable'    => ($this->subtotal + $this->tax),
            'taxable'    => ($this->subtotal),
            'company_id' => $this->company_id,
            'payment_id' => $this->payment_id,
            'pref_id'    => $this->delivery ? $this->delivery->pref_id : null,
            'purchase_date' => $this->create_date ? $this->create_date : date('Y-m-d'),
        ]);

        return $model->value;
    }

    /**
     * 冷凍便込みならこちらで計算
     */
    protected function computeIncludeFrozenPostage()
    {
        $model = new Postage([
            'taxable'    => ($this->subtotal),
            'company_id' => $this->company_id,
            'payment_id' => $this->payment_id,
            'pref_id'    => $this->delivery ? $this->delivery->pref_id : null,
            'purchase_date' => $this->create_date ? $this->create_date : date('Y-m-d'),
            'frozen_total'     => $this->frozen_total
        ]);

        $this->postage_frozen = $model->getFrozenValue();
        $chilled_value = $model->getChilledValue();
        if (count($this->items) == $this->frozen_items_count) {
            $this->postage = $this->postage_frozen;
        } else {
            $this->postage = $chilled_value + $this->postage_frozen;
        }
    }

    public function computeTax($asArray = false)
    {
        $tax = [];

        if ($com = $this->company) {
            $key       = $com->company_id;
            $tax[$key] = $this->tax;
        } else {
            foreach ($this->companies as $com) {
                $key     = $com->company_id;
                $items   = $this->getItemsOfCompany($key);

                if ($this->payment_id == Payment::PKEY_DROP_SHIPPING)
                    $taxable = array_sum(ArrayHelper::getColumn($items, 'basePrice'));
                else
                    $taxable = array_sum(ArrayHelper::getColumn($items, 'charge'));

                $tax[$key] = Yii::$app->tax->compute($taxable);
            }
        }

        if (!$asArray)
            return array_sum($tax);

        return $tax;
    }

    public function getCompanyTaxes($asArray = false)
    {
        $tax = [];

        if ($com = $this->company) {
            $key       = $com->company_id;
            $tax[$key] = $this->tax;
        } else {
            foreach ($this->companies as $com) {
                $key     = $com->company_id;
                $items   = $this->getItemsOfCompany($key);


                $tax[$key] = $this->getTaxes($key);
            }
        }
        if (!$asArray) {
            return array_sum($this->getTaxes());
        }

        if ($this->create_date && strtotime($this->create_date) < \common\models\Tax::newDate()) {

            foreach ($tax as $key => $values) {
                $tax[$key] = is_array($values) ? array_sum(array_values($values)) : $values;
            }
        }
        return $tax;
    }

    public function getTaxes($company_id = null)
    {
        $tax = ['normal' => 0, 'reduced' => 0];
        foreach ($this->items as $item) {
            if ($company_id && $item->company_id != $company_id)
                continue;

            if ($this->create_date && strtotime($this->create_date) <= \common\models\Tax::newDate()) {
                $tax['normal'] += $item->getUnitTax(true) * $item->quantity;
            } else {
                $item->isReducedTax() ? $tax['reduced'] += $item->model->tax : $tax['normal'] += $item->model->tax;
            }
        }

        return $tax;
    }

    // 納品書では定価＊数量での合計を出す 2019/08/15 kawai
    public function getTaxedSubTotals()
    {
        $taxedSubTotal = ['normal' => 0, 'reduced' => 0, 'normal_tax' => 0, 'reduced_tax' => 0];
        foreach ($this->items as $item) {
            //            $item->isReducedTax() ? $taxedSubTotal['reduced'] += ($item->unit_price + $item->getUnitTax(true)) * $item->quantity : $taxedSubTotal['normal'] += ($item->unit_price + $item->getUnitTax(false)) * $item->quantity;
            if ($item->isReducedTax()) {
                $taxedSubTotal['reduced'] += $item->getUnitPrice() * $item->quantity;
                $taxedSubTotal['reduced_tax'] += $item->getUnitTax() * $item->quantity;
            } else {
                $taxedSubTotal['normal'] += $item->getUnitPrice() * $item->quantity;
                $taxedSubTotal['normal_tax'] += $item->getUnitTax() * $item->quantity;
            }
        }
        return $taxedSubTotal;
    }

    /**
     * RXT対象のアイテムがあるか判定する
     */
    public function getIncludeRxt()
    {
        $include_rxt = 0;

        $items = $this->items;
        if(!$items || count($items) == 0)
            return $include_rxt;

        foreach($items as $item) {
            if(Company::PKEY_HJ == $item->company->company_id && (!$item->pickcode || $item->pickcode == '')) {
                $include_rxt = 1;
                break;
            }
        }
        return $include_rxt;
    }



    public static function createCommissionModels($purchase)
    {
        if (!$purchase->validate() || ($purchase->payment->payment_id != Payment::PKEY_DROP_SHIPPING))
            return [];

        $search   = new \common\models\SearchProductFavor([
            'branch'   => $purchase->branch,
            'customer' => $purchase->customer,
            'timestamp' => $purchase->isNewRecord ? time() : strtotime($purchase->create_date),
        ]);
        $customer = $purchase->customer;
        $models   = [];

        foreach ($purchase->companies as $company) {
            if ($customer->isAgencyOf($company)) {
                $fee = 0;
                if ($items = $purchase->getItemsOfCompany($company->company_id))
                    foreach ($items as $item) {
                        $search->item = $item;
                        $discount = $search->discount;
                        if ($discount->rate)
                            $fee += $item->quantity * floor($item->price * $discount->rate / 100);
                        if ($discount->amount)
                            $fee += ($item->quantity * $discount->amount);
                    }
                $model = Commission::findOne([
                    'purchase_id' => $purchase->purchase_id,
                    'company_id'  => $company->company_id
                ]);
                if (!$model)
                    $model = new Commission([
                        'purchase_id' => $purchase->purchase_id,
                        'company_id'  => $company->company_id,
                        'create_date' => $purchase->create_date,
                    ]);

                $model->customer_id = $purchase->customer_id;
                $model->fee         = $fee;

                $models[] = $model;
            } elseif ($model = Commission::findOne([
                'purchase_id' => $purchase->purchase_id,
                'company_id'  => $company->company_id
            ])) {
                $model->customer_id = $purchase->customer_id;
                $model->fee         = 0;

                $models[] = $model;
            }
        }

        return $models;
    }

    public function checkForGift()
    {
        return (!in_array($this->company_id, [\common\models\Company::PKEY_TROSE,]) && $this->customer
            && ($this->customer->isAgency() ||
                ((\common\models\CustomerGrade::PKEY_KA <= $this->customer->currentGrade($this->customer_id)) && isset($this->customer->ysdAccount) && $this->customer->ysdAccount->isValid()))
            && in_array($this->payment_id, [
                Payment::PKEY_BANK_TRANSFER,
                Payment::PKEY_DIRECT_DEBIT,
                Payment::PKEY_DROP_SHIPPING,
            ]));
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new PurchaseQuery(get_called_class());
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
    public function getCampaign()
    {
        return $this->hasOne(Campaign::className(), ['campaign_id' => 'campaign_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgent()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'agent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCommissions()
    {
        return $this->hasMany(Commission::className(), ['purchase_id' => 'purchase_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies()
    {
        if (isset($this->_companies))
            return $this->_companies;

        $categories = \yii\helpers\ArrayHelper::getColumn($this->items, 'model.category.category_id');
        $companies  = \common\models\Category::find()
            ->select('seller_id')
            ->distinct()
            ->where(['in', 'category_id', $categories])
            ->column();

        $this->_companies = Company::find()->where(['in', 'company_id', $companies])->all();

        return $this->_companies;
    }

    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    public function IsAgency()
    {
        return $this->customer ? $this->customer->isAgency() : false;
    }

    public function getAgencies()
    {
        $agencies = 99;

        if (!$this->customer)
            return $agencies;

        $hj = false;
        $he = false;
        $hp = false;


        if (!$this->customer)
            return $agencies;

        if ($this->customer->isMemberOf([
            Membership::PKEY_AGENCY_HJ_A,
            Membership::PKEY_AGENCY_HJ_B
        ]))
            $hj = true;

        if ($this->customer->isMemberOf(Membership::PKEY_AGENCY_HE))

            $he = true;

        if ($this->customer->isMemberOf(Membership::PKEY_AGENCY_HP))

            $hp = true;

        if ($hj) {
            if ($he) {
                if ($hp) {
                    return 6;
                }
                return 3;
            }

            if ($hp) {
                return 4;
            }
            return  0;
        }
        if ($he) {
            if ($hp) {
                return 5;
            }
            return 1;
        }
        if ($hp) {
            return 2;
        }


        return $agencies;
    }

    public function getmtb_membership()
    {
        return $this->hasMany(\common\models\Membership::className(), ['customer_id' => 'customer_id']);
    }

    public function getCustomerMembership()
    {
        return $this->hasMany(\common\models\CustomerMembership::className(), ['customer_id' => 'customer_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDelivery()
    {
        return $this->hasOne(PurchaseDelivery::className(), ['purchase_id' => 'purchase_id']);
    }

    public function getItemCount()
    {
        return array_sum(\yii\helpers\ArrayHelper::getColumn(
            $this->getItems()
                ->andWhere(['parent' => null])
                ->all(),
            'quantity'
        ));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(PurchaseItem::className(), ['purchase_id' => 'purchase_id']);
    }

    /*
     * @brief get array of ComplexRemedy to prepare 滴下 Remedies on shipping
     */
    public function getItemsToDrop()
    {
        $items = $this->getItems()
            ->andWhere(['parent' => null, 'product_id' => null])
            ->all();

        return self::convertItemsToComplexRemedy($items);
    }

    public function getItemsToMachine()
    {
        $items = $this->getItems()
            ->andWhere(['product_id' => [
                Product::PKEY_MACHINE_REMEDY_30C,
                Product::PKEY_MACHINE_REMEDY_200C,
                Product::PKEY_MACHINE_REMEDY_1M,
                Product::PKEY_MACHINE_REMEDY_COMB,
            ]])
            ->all();

        return self::convertItemsToMachineRemedy($items);
    }

    /**
     * @return array of items
     */
    public function getItemsOfCompany($id)
    {
        if (!in_array($id, \yii\helpers\ArrayHelper::getColumn($this->companies, 'company_id')))
            return [];

        if (isset($this->_itemsOfCompany[$id]))
            return $this->_itemsOfCompany[$id];

        $cid = \yii\helpers\ArrayHelper::getColumn(Category::find()->where(['seller_id' => $id])->all(), 'category_id');

        $pid = \yii\helpers\ArrayHelper::getColumn($this->items, 'product_id');
        $products = Product::find()->where([
            'product_id' => $pid,
            'category_id' => $cid
        ])
            ->all();
        $pid = \yii\helpers\ArrayHelper::getColumn($products, 'product_id');

        $items = $this->getItems()
            ->andWhere(['product_id' => $pid])
            ->all();

        if (Company::PKEY_HJ == $id) {
            $remedies = [];
            foreach ($this->items as $item) {
                if ($item->remedy_id)
                    $remedies[] = $item;
                if (!$item->remedy_id && !$item->product_id)
                    $remedies[] = $item; // must be 滴下母体
            }
            $items = array_merge($items, $remedies);
        }

        $this->_itemsOfCompany[$id] = $items;

        return $items;
    }

    /**
     * 冷凍品のみをピックアップ
     * @return array of items
     */
    public function getItemsOfFrozen($id)
    {
        if (!in_array($id, \yii\helpers\ArrayHelper::getColumn($this->companies, 'company_id')))
            return [];

        if (isset($this->_itemsOfFrozen[$id]))
            return $this->_itemsOfFrozen[$id];

        $cid = \yii\helpers\ArrayHelper::getColumn(Category::find()->where(['seller_id' => $id])->all(), 'category_id');

        $pid = \yii\helpers\ArrayHelper::getColumn($this->items, 'product_id');
        $products = Product::find()->where([
            'product_id' => $pid,
            'cool_id'    => Product::COOL_FROZEN,
            'category_id' => $cid
        ])
            ->all();
        $pid = \yii\helpers\ArrayHelper::getColumn($products, 'product_id');

        $items = $this->getItems()
            ->andWhere(['product_id' => $pid])
            ->all();
        $this->_itemsOfFrozen[$id] = $items;

        return $items;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMails()
    {
        return $this->hasMany(MailLog::className(), ['pkey' => 'purchase_id'])->where(['tbl' => self::tableName()])->andWhere(['to' => $this->email]);
    }

    public function getNext()
    {
        return static::find()
            ->andWhere(['>', 'purchase_id', $this->purchase_id])
            ->orderBy('purchase_id ASC')
            ->one();
    }

    public function getPrev()
    {
        return static::find()
            ->andWhere(['<', 'purchase_id', $this->purchase_id])
            ->orderBy('purchase_id DESC')
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayment()
    {
        return $this->hasOne(Payment::className(), ['payment_id' => 'payment_id']);
    }

    public function getPurchaseStatus()
    {
        return $this->hasOne(PurchaseStatus::className(), ['status_id' => 'status']);
    }

    public function getSender()
    {
        return Company::findOne(\common\models\Company::PKEY_TY);
    }

    public function getPurchaseRecipe()
    {
        return $this->hasMany(\common\models\LtbPurchaseRecipe::className(), ['purchase_id' => 'purchase_id']);
    }

    public function getRecipe()
    {
        return $this->hasOne(\common\models\Recipe::className(), ['recipe_id' => 'recipe_id'])
            ->via('purchaseRecipe');
    }

    public function getStatusName()
    {
        //        if($this->status <= PurchaseStatus::PKEY_DONE)
        //        {
        //            if($this->shipped)
        //                return "出荷済み";
        //
        //            if($this->paid)
        //                return "お支払い済み";
        //        }

        return $this->purchaseStatus->name;
    }

    public function getStaff()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'staff_id']);
    }

    public function getSeller()
    {
        if ($this->branch)
            return $this->branch->company;

        return Company::findOne(Company::PKEY_TY);
    }

    public function getTaxTotal()
    {
        return $this->tax_total;
    }

    public function setTaxTotal($val)
    {
        $this->tax_total = $val;
    }

    public function getReducedTaxTotal()
    {
        return $this->reduced_tax_total;
    }

    public function setReducedTaxTotal($val)
    {
        $this->reduced_tax_total = $val;
    }

    public function getTaxedSubtotal()
    {
        return $this->taxed_subtotal = $this->subtotal + $this->tax;
    }

    public function setTaxedSubtotal($val)
    {
        $this->taxed_subtotal = $val;
    }



    /* @return bool */
    public function isExpired()
    {
        return (PurchaseStatus::PKEY_CANCEL <= $this->status);
    }

    /* @return bool */
    public function isPreparing()
    {
        return (PurchaseStatus::PKEY_PAYING <= $this->status);
    }

    /* @return bool */
    public function isGift()
    {
        return ($this->delivery && $this->delivery->gift);
    }

    public function isPaymentDeferred()
    {
        return ($this->payment_id == Payment::PKEY_YAMATO_COD
            || $this->payment_id == Payment::PKEY_BANK_TRANSFER
            || $this->payment_id == Payment::PKEY_DIRECT_DEBIT);
    }

    public function revertAmount($attribute, $value)
    {
        $this->setAttributes([$attribute => $value]);
    }

    public function afterValidate()
    {
        $this->updateStatus();

        return parent::afterValidate();
    }

    private function updateStatus()
    {
        if ($this->status < PurchaseStatus::PKEY_DONE) {
            if ($this->shipped) {
                if ($this->paid) {
                    $this->status = PurchaseStatus::PKEY_DONE;
                } else {
                    // 送り状番号（冷凍便）が追加されたので、伝票内の冷凍便有無で処理を分岐
                    if (!$this->include_frozen) {
                        if (!$this->shipping_id) {
                            $this->status = PurchaseStatus::PKEY_PAYING;
                        }
                    } else {
                        if (!$this->shipping_id && !$this->shipping_frozen_id) {
                            $this->status = PurchaseStatus::PKEY_PAYING;
                        }
                    }
                }
            } else {
                if ($this->include_pre_order) {
                    /* $order_dateの有無で判定
                    　有・・既存伝票　受注予定日（購入日の翌月15日）以降にステータス変更可能
                    　無・・予約に固定
                    */
                    $order_date = strtotime(date('Y-m', strtotime($this->create_date)));
                    $pre_order_1 = strtotime(date("Y-m-15", $order_date) . " next month");

                    if ($order_date) { // 3/30  ,  2/21
                        if (time() >= $pre_order_1) { // now >= 4/15, now >= 3/15
                            // free change status
                        } else {
                            $this->status = PurchaseStatus::PKEY_PREORDER;
                        }
                    } else {
                        $this->status = PurchaseStatus::PKEY_PREORDER;
                    }
                } else {
                    $this->status = PurchaseStatus::PKEY_INIT;
                }
            }
         } else if($this->status == PurchaseStatus::PKEY_DONE && !$this->paid){
                $this->status = PurchaseStatus::PKEY_PAYING;
        
        }
    }

    public function beforeValidate()
    {
        if (!$this->isNewRecord && $this->customer) {
            $time  = strtotime($this->create_date);
            $time -= 1; // minus 1 sec
            $time  = date('Y-m-d H:i:s', $time);

            // 過去伝票のみ作成日時を基準に保有ポイントを出す
            if (time() > $time) {
                $this->customer->point = $this->customer->currentPoint($time);
            } else {
                $this->customer->point = $this->customer->currentPoint();
            }
        }
        return parent::beforeValidate();
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {

        // Campaign_idがNULLだと、rulesによりデフォルト値をセットされてしまうため、0に変換
        if (!$this->campaign_id)
            $this->campaign_id = 0;

        if (!parent::validate($attributeNames, $clearErrors))
            return false;

        return $this->hasErrors() ? false : true;
    }

    public function beforeSave($insert)
    {
        // Campaign_idが０なら、NULLとして登録する
        if (0 === $this->campaign_id)
            $this->campaign_id = null;

        return parent::beforeSave($insert);
    }

    /**
     * 口座振替だった場合、利用の可否を徹底的に検査する
     * 能登地震災害支援対応 支援金のみの場合、代引きはエラーとして弾く 2024/02/07
     */
    public function validatePayment($attr, $param)
    {
        if(count($this->items) == 1) {
            foreach($this->items as $item) {
                $item = $item->getModel();
                if(isset($item->product_id) && $item->product_id === \common\models\Product::PKEY_NOTO_SUPPORT) {
                    if($this->payment_id == Payment::PKEY_YAMATO_COD) {
                        $this->addError($attr, "代引き支払いの場合、【義捐金のみ】のお申込みはお受けできません。");
                    }
                }
            }
        }

        if (!$this->isNewRecord)
            return true;

        if (Payment::PKEY_DIRECT_DEBIT != $this->payment_id)
            return true;

        if (!$c = $this->customer)
            $this->addError($attr, "お客様が匿名の場合、口座振替をご利用いただけません");

        $a = null;
        if ($c && (!$a = $c->ysdAccount))
            $this->addError($attr, "お客様の口座振替が登録されていません");

        if ($a && !$a->isValid())
            $this->addError($attr, "お客様の口座振替は登録が完了していません、または利用に制限があります");

        // no customer, or no account
        if ($this->hasErrors($attr))
            return false;

        if ($a->credit_limit <= 0)
            $this->addError($attr, "お客様の口座振替は現在ご利用いただけません");

        elseif ($a->credit_limit < $this->total_charge)
            $this->addError($attr, sprintf(
                "今回の注文は口座振替のご利用限度額(￥%s)を超えました",
                number_format($a->credit_limit)
            ));

        // credit_limit over
        if ($this->hasErrors($attr))
            return false;

        $invoice = new Invoice([
            'customer_id' => $this->customer_id,
            'target_date' => date('Y-m'),
        ]);
        $invoice->compute();
        $total = $invoice->due_total + $this->total_charge;

        if ($a->credit_limit < $total)
            $this->addError($attr, sprintf(
                "今回の注文を含む請求予定額(￥%s)は口座振替のご利用限度額(￥%s)を超えました",
                number_format($total),
                number_format($a->credit_limit)
            ));

        return $this->hasErrors($attr);
    }

    private static function convertItemsToMachineRemedy($items)
    {
        if (!$items)
            return null;

        $models = [];
        foreach ($items as $k => $item) {
            $model = new MachineRemedyForm();
            $model->feed($item->attributes);

            for ($i = 1; $i <= $item->quantity; $i++)
                $models[] = $model;
        }

        return $models;
    }

    /**
     * 受注レコードの情報から、ComplexRemedy（コンビネーションレメディー）オブジェクトを作成して返す
     * @param type $items
     * @return \common\models\ComplexRemedy
     */
    private static function convertItemsToComplexRemedy($items)
    {
        if (!$items)
            return null;

        $complex = [];
        foreach ($items as $item) {
            $stock = RemedyStock::getOneByBarcode($item->code, false);
            // RemedyStockに在庫がある（in_stock）のは既製品。母体が既製品かオリジナルでないものはスキップする
            if ($stock->in_stock && !$item->children || (!$stock->in_stock && !$stock->isNewRecord))
                continue;

            $drops = [];
            if ($item->children)
                foreach ($item->children as $child)
                    $drops[] = RemedyStock::findByCode($child->code);

            $complex[$item->seq] = new ComplexRemedy(['vial' => $stock, 'drops' => $drops, 'qty' => $item->quantity]);
        }

        return $complex;
    }
}

class PurchaseQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        $param = [
            'dtb_purchase.status' => [
                PurchaseStatus::PKEY_CANCEL,
                PurchaseStatus::PKEY_VOID,
                PurchaseStatus::PKEY_RETURN
            ]
        ];

        if ($state)
            return $this->andWhere(['not', $param]);
        else
            return $this->andWhere($param);
    }
}


class FixCommission extends \yii\base\Behavior
{
    public function events()
    {
        return [
            Purchase::EVENT_AFTER_INSERT => 'initCommision',
            Purchase::EVENT_AFTER_UPDATE => 'updateCommision',
            Purchase::EVENT_AFTER_DELETE => 'deleteCommision',
        ];
    }

    /* @return void */
    public function initCommision($event)
    {
        $models = Purchase::createCommissionModels($event->sender);

        if ($models)
            self::saveModels($models);
    }

    public function updateCommision($event)
    {
        $purchase = $event->sender;

        if (in_array($purchase->branch_id, Branch::find()->center()->select('branch_id')->column()))
            // 健康相談ならCommissionを更新しない
            return;

        $models = Purchase::createCommissionModels($purchase);
        $query  = Commission::find()->where(['purchase_id' => $purchase->purchase_id]);

        if ($models)
            self::saveModels($models);
        else
            foreach ($query->all() as $model) {
                $model->fee = 0;
                $model->save();
            }
    }

    public function deleteCommision($event)
    {
        $purchase = $event->sender;

        Commission::deleteAll('purchase_id = :id', [':id' => $purchase->purchase_id]);
    }

    private function saveModels($models)
    {
        foreach ($models as $model)
            if (!$model->save())
                Yii::error([
                    'event'      => 'failed Commision::save()',
                    'attributes' => $model->attributes,
                    'errors'     => $model->errors,
                ], self::className() . '::' . __FUNCTION__);
    }
}
