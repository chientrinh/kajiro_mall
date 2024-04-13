<?php

namespace common\models;

use Yii;
use \common\components\ean13\CheckDigit;
use common\models\Branch;
use backend\models\Staff;
use common\components\cart\MixedCart;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Stock.php $
 * $Id: $
 *
 * This is the model class for table "dtb_stock".
 *
 * @property integer $veg_id
 * @property string $name
 * @property string $kana
 * @property string $update_date
 */
class Stock extends \yii\db\ActiveRecord
{
    const ALERT_QTY      = 10;  // 画面上に数量を表示する条件(未満)
    const VEGETABLE_SETM = 234; // 野菜セットM
    const INIT_VERSION   = 0;

    public $maximum_qty = 999;
    public $name; // 商品名

//     public $actual_qty = 0;

    /**
     * テーブル名指定
     *
     * @return string テーブル名
     */
    public static function tableName()
    {
        return 'dtb_stock';
    }

    /**
     * テーブル更新時の振る舞い
     *
     * @return array 設定内容
     */
    public function behaviors()
    {
        return [
            'update'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    public function optimisticLock(){
        return 'version';
    }

    /**
     * 入力規則
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['actual_qty', 'ean13', 'branch_id'], 'required'],
            ['ean13', 'exist', 'targetClass'=>\common\models\ProductMaster::className(), 'targetAttribute'=>'ean13'],
            ['ean13', 'unique', 'targetAttribute'=>'ean13', 'message'=>'対象の商品は既に登録されています。'],
            [['actual_qty', 'threshold'], 'integer', 'min' => 0, 'max' => 999],
            ['threshold', 'default', 'value' => 0 ],
            [['updated_by'], 'exist', 'targetClass'=>\backend\models\Staff::className(),'targetAttribute'=>'staff_id'],
        ];
    }

    /**
     *
     * @param string $attr 項目名
     * @param string $param バリデートの検証条件
     * @return boolean
     */
    public function validateQty($attr, $param)
    {
        $condtion = ['stock_id' => $this->stock_id];

        $stock = self::findOne($condition);

        if ( (int) $this->actual_qty > $stock->maximum_qty) {
             $msg = $stock->maximum_qty. "以下の数値を入力してください。";
             $this->addError($attr, $msg);
        }

        return $this->hasErrors($attr);
    }

    /**
     * 項目ラベル表示設定
     */
    public function attributeLabels()
    {
        return [
            'branch_id'     => '拠点',
            'product_id'    => '商品',
            'name'          => '商品名',
            'ean13'         => 'バーコード',
            // 'remedy_name'   => 'レメディー',
            'actual_qty'    => '在庫数',
            'threshold'     => '閾値',
            'update_date'   => '最終更新日時',
            'staffs.name01' => '最終更新者'
        ];
    }

    public function attributeHints()
    {
        return [
            'threshold'  => '在庫数が閾値を下回ると担当者にメールが行きます。',
        ];
    }

    /**
     * リレーション    拠点
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * リレーション    更新者
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * リレーション    商品(複数)
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['product_id' => 'product_id']);
    }

    /**
     * リレーション    商品(単数)
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13']);
    }

    /**
     * 商品の現在の在庫数を取得する
     *
     * @param int $product_id 商品ID
     * @param int $branch_id 拠点ID
     *
     * @return int 商品在庫数（在庫情報がない場合、在庫が1未満の場合は0を返す）
     */
    public function getActualQty($product_id, $branch_id = Branch::PKEY_ROPPONMATSU)
    {
        $stock = Stock::getStock($product_id, $branch_id);

        // 在庫管理していない商品はfalseで返す
        if (! $stock) return false;

        if ($stock->actual_qty < 1) return 0;

        return $stock->actual_qty;
    }

    /**
     * １商品情報を取得（条件：商品IDと拠点ID）
     *
     * @param int $product_id 商品ID
     * @param int $branch_id  拠点ID
     * @return mixed <\yii\db\static, NULL, multitype:, boolean, \yii\db\ActiveRecord>
     */
    public function getStock($product_id, $branch_id = Branch::PKEY_ROPPONMATSU)
    {
        $condition = [
            'branch_id'  => $branch_id,
            'product_id' => $product_id,
        ];

        return Stock::findOne($condition);
    }

    public function checkThreshold()
    {
        Stock::checkThresholdAll();
        // 現在の在庫数が閾値以下になった場合に僅少通知を送る。
        if ($this->actual_qty <= $this->threshold) {
            $mailer = new \common\components\sendmail\ThresholdMail(['model' => $this]);
            $mailer->threshold();
        }

    }

    public function checkThresholdAll()
    {
        $target = Stock::find()
                    ->andWhere(['not', ['threshold' => 0]])
                    ->andWhere('actual_qty <= threshold')->all();

        var_dump($target);exit;

        if (! $target)
            return true;

        $mailer = new \common\components\sendmail\ThresholdMail(['model' => $this]);
        $mailer->thresholdAll($target);
    }

}
