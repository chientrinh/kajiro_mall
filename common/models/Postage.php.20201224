<?php

namespace common\models;
use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Postage.php $
 * $Id: Postage.php 4185 2019-09-30 16:12:44Z mori $
 */

class Postage extends \yii\base\Model
{
    const DEFAULT_VALUE    = 600;
    const POSTAL_COD_VALUE = 700; // トミーローズ専用の設定価格

    public $taxable;
    public $pref_id;
    public $payment_id;
    public $company_id;
    public $purchase_date; // 伝票がある場合に、新旧送料判定で使用

    private $threshold_old = [
                           /* default */ 0 => 13000,
        \common\models\Company::PKEY_TROSE => 10000,
    ];

    private $threshold = [
                           /* default */ 0 => 10000,
        \common\models\Company::PKEY_TROSE => 10000,
    ];

    /**
     * TROSE・山口県からのゆうパック料金
     * 信越 - 長野・新潟
     * 北陸 - 石川・富山・福井
     * 中部 - 愛知・岐阜・三重・静岡・長野
     */
    private $postal_matrix = [
         1 => 1150, //北海道
         2 =>  750, //青森県 - 東北: 750
         3 =>  750, //岩手県 - 東北
         4 =>  750, //宮城県 - 東北
         5 =>  750, //秋田県 - 東北
         6 =>  750, //山形県 - 東北
         7 =>  750, //福島県 - 東北
         8 =>  650, //茨城県 - 関東: 650
         9 =>  650, //栃木県
        10 =>  650, //群馬県
        11 =>  650, //埼玉県
        12 =>  650, //千葉県
        13 =>  650, //東京都
        14 =>  650, //神奈川県
        15 =>  650, //新潟県 - 信越: 650
        16 =>  650, //富山県 - 北陸: 650
        17 =>  650, //石川県 - 北陸: 650
        18 =>  650, //福井県 - 北陸: 650
        19 =>  600, //山梨県 - 中部: 600
        20 =>  650, //長野県 - 信越: 650
        21 =>  600, //岐阜県 - 中部: 600
        22 =>  600, //静岡県 - 中部
        23 =>  600, //愛知県 - 中部
        24 =>  600, //三重県 - 中部
        25 =>  510, //滋賀県 - 関西・九州: 510
        26 =>  510, //京都府 - 関西・九州
        27 =>  510, //大阪府 - 関西・九州
        28 =>  510, //兵庫県 - 関西・九州
        29 =>  510, //奈良県 - 関西・九州
        30 =>  510, //和歌山県 - 関西・九州
        31 =>  510, //鳥取県 - 関西・九州
        32 =>  510, //島根県 - 関西・九州
        33 =>  510, //岡山県 - 関西・九州
        34 =>  510, //広島県 - 関西・九州
        35 =>  510, //山口県 - 関西・九州
        36 =>  510, //徳島県 - 関西・九州
        37 =>  510, //香川県 - 関西・九州
        38 =>  510, //愛媛県 - 関西・九州
        39 =>  510, //高知県 - 関西・九州
        40 =>  510, //福岡県 - 関西・九州
        41 =>  510, //佐賀県 - 関西・九州
        42 =>  510, //長崎県 - 関西・九州
        43 =>  510, //熊本県 - 関西・九州
        44 =>  510, //大分県 - 関西・九州
        45 =>  510, //宮崎県 - 関西・九州
        46 =>  510, //鹿児島県 - 関西・九州
        47 => 1100, //沖縄県
        ];

    /**
     * 熱海・六本松（静岡県）からのヤマト便料金（〜2017-11-14）
     */
    private $yamato_matrix_old = [
         1 =>  900, //北海道   
         2 =>  600, //青森県   
         3 =>  600, //岩手県   
         4 =>  500, //宮城県   
         5 =>  600, //秋田県   
         6 =>  500, //山形県   
         7 =>  500, //福島県   
         8 =>  400, //茨城県   
         9 =>  400, //栃木県   
        10 =>  400, //群馬県   
        11 =>  400, //埼玉県   
        12 =>  400, //千葉県   
        13 =>  400, //東京都   
        14 =>  400, //神奈川県 
        15 =>  400, //新潟県   
        16 =>  400, //富山県   
        17 =>  400, //石川県   
        18 =>  400, //福井県   
        19 =>  400, //山梨県   
        20 =>  400, //長野県   
        21 =>  400, //岐阜県   
        22 =>  400, //静岡県   
        23 =>  400, //愛知県   
        24 =>  400, //三重県   
        25 =>  400, //滋賀県   
        26 =>  400, //京都府   
        27 =>  400, //大阪府   
        28 =>  400, //兵庫県   
        29 =>  400, //奈良県   
        30 =>  400, //和歌山県 
        31 =>  500, //鳥取県   
        32 =>  500, //島根県   
        33 =>  500, //岡山県   
        34 =>  500, //広島県   
        35 =>  600, //山口県   
        36 =>  600, //徳島県   
        37 =>  600, //香川県   
        38 =>  600, //愛媛県   
        39 =>  600, //高知県   
        40 =>  600, //福岡県   
        41 =>  600, //佐賀県   
        42 =>  600, //長崎県   
        43 =>  600, //熊本県   
        44 =>  600, //大分県   
        45 =>  600, //宮崎県   
        46 =>  600, //鹿児島県
        47 => 1200, //沖縄県
        ];

    /**
     * 熱海・六本松（静岡県）からのヤマト便料金（2017-11-15〜）
     */
    private $yamato_matrix = [
         1 =>  950, //北海道 // 北海道 950
         2 =>  720, //青森県 // 北東北 720
         3 =>  720, //岩手県 // 北東北
         4 =>  600, //宮城県 // 南東北 600
         5 =>  720, //秋田県 // 北東北
         6 =>  600, //山形県 // 南東北
         7 =>  600, //福島県 // 南東北
         8 =>  500, //茨城県 // 関東 500
         9 =>  500, //栃木県 // 関東
        10 =>  500, //群馬県 // 関東
        11 =>  500, //埼玉県 // 関東
        12 =>  500, //千葉県 // 関東
        13 =>  500, //東京都 // 関東
        14 =>  500, //神奈川県 // 関東
        15 =>  500, //新潟県 // 信越 500
        16 =>  500, //富山県 // 北陸 500
        17 =>  500, //石川県 // 北陸
        18 =>  500, //福井県 // 北陸
        19 =>  500, //山梨県 // 関東
        20 =>  500, //長野県 // 信越
        21 =>  500, //岐阜県 // 中部 500
        22 =>  500, //静岡県 // 中部
        23 =>  500, //愛知県 // 中部
        24 =>  500, //三重県 // 中部
        25 =>  500, //滋賀県 // 関西 500
        26 =>  500, //京都府 // 関西
        27 =>  500, //大阪府 // 関西
        28 =>  500, //兵庫県 // 関西
        29 =>  500, //奈良県 // 関西
        30 =>  500, //和歌山県 // 関西
        31 =>  600, //鳥取県 // 中国 600
        32 =>  600, //島根県 // 中国
        33 =>  600, //岡山県 // 中国
        34 =>  600, //広島県 // 中国
        35 =>  600, //山口県 // 中国
        36 =>  700, //徳島県 // 四国 700
        37 =>  700, //香川県 // 四国
        38 =>  700, //愛媛県 // 四国
        39 =>  700, //高知県 // 四国
        40 =>  700, //福岡県 // 九州 700
        41 =>  700, //佐賀県 // 九州
        42 =>  700, //長崎県 // 九州
        43 =>  700, //熊本県 // 九州
        44 =>  700, //大分県 // 九州
        45 =>  700, //宮崎県 // 九州
        46 =>  700, //鹿児島県 // 九州
        47 => 1400, //沖縄県 // 沖縄 1400
        ];

    public function rules()
    {
        return [
            ['pref_id','required','when'=>function($model){return (\common\models\Company::PKEY_TROSE != $this->company_id);}],
        ];
    }

    public function getThreshold()
    {
        if(strtotime($this->purchase_date) < strtotime("2017-11-15"))
            return $this->threshold_old[(int)$this->company_id];

        return $this->threshold[(int)$this->company_id];
    }

    public function getValue()
    {
        if(in_array($this->payment_id, [Payment::PKEY_CASH,
                                        Payment::PKEY_CREDIT_CARD,
                                        Payment::PKEY_NO_CHARGE])
        )
            return 0; // 送料は無料とする

        $idx       = (int)$this->company_id;

        if(strtotime($this->purchase_date) < strtotime("2017-11-15")) {
            $default   = $this->threshold_old[0];
            $threshold = \yii\helpers\ArrayHelper::getValue($this->threshold_old, $idx, $default);
        } else {
            $default   = $this->threshold[0];
            $threshold = \yii\helpers\ArrayHelper::getValue($this->threshold, $idx, $default);
        }

        if(Payment::PKEY_PARCEL_COD == $this->payment_id) // ゆうパック代引
            return $this->getDefaultValue();

        if($threshold <= $this->taxable)
            return 0;

        return $this->getDefaultValue();
    }

    public function getYamatoMatrix()
    {
        if(strtotime($this->purchase_date) < strtotime("2017-11-15"))
            return $this->yamato_matrix_old;

        return $this->yamato_matrix;
    }

    private function getDefaultValue()
    {
        if(Payment::PKEY_POSTAL_COD == $this->payment_id) // ゆうメール代引
            return self::POSTAL_COD_VALUE;
            
        if(Payment::PKEY_PARCEL_COD == $this->payment_id) // ゆうパック代引
            return \yii\helpers\ArrayHelper::getValue($this->parcel_matrix,
                                                      $this->pref_id,
                                                      max($this->parcel_matrix));
        $base = \yii\helpers\ArrayHelper::getValue($this->yamatoMatrix,
                                                   $this->pref_id,
                                                   max($this->yamatoMatrix));

        // 六本松カートの場合のみ、ベースを+200する　クール便の反映
        if($this->company_id == \common\models\Company::PKEY_TY) {
            $base += 200;
        }

	$tax = Yii::$app->tax;
        if(strtotime($this->purchase_date) >= $tax::newDate()) {
            $tax = \common\models\Tax::findOne(1);
        } else {
            $tax = \common\models\Tax::findOne(2);
        }

        return $base + $tax->compute($base);
   }

}
