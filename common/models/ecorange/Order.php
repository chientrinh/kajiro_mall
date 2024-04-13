<?php

namespace common\models\ecorange;

use Yii;

/**
 * This is the model class for table "{{%dtb_order}}".
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/Order.php $
 * @version $Id: Order.php 1023 2015-05-20 10:16:53Z mori $
 *
 * @property integer $order_id
 * @property string $order_temp_id
 * @property integer $customer_id
 * @property string $message
 * @property string $order_name01
 * @property string $order_name02
 * @property string $order_kana01
 * @property string $order_kana02
 * @property string $order_email
 * @property string $order_tel01
 * @property string $order_tel02
 * @property string $order_tel03
 * @property string $order_fax01
 * @property string $order_fax02
 * @property string $order_fax03
 * @property string $order_zip01
 * @property string $order_zip02
 * @property string $order_pref
 * @property string $order_addr01
 * @property string $order_addr02
 * @property integer $order_sex
 * @property string $order_birth
 * @property integer $order_job
 * @property string $deliv_name01
 * @property string $deliv_name02
 * @property string $deliv_kana01
 * @property string $deliv_kana02
 * @property string $deliv_tel01
 * @property string $deliv_tel02
 * @property string $deliv_tel03
 * @property string $deliv_fax01
 * @property string $deliv_fax02
 * @property string $deliv_fax03
 * @property string $deliv_zip01
 * @property string $deliv_zip02
 * @property string $deliv_pref
 * @property string $deliv_addr01
 * @property string $deliv_addr02
 * @property string $subtotal
 * @property string $discount
 * @property string $deliv_fee
 * @property string $charge
 * @property string $use_point
 * @property string $add_point
 * @property string $birth_point
 * @property string $last_order_total_point
 * @property string $order_total_point
 * @property integer $coupon_id
 * @property string $coupon_label
 * @property string $coupon_price
 * @property string $tax_rate
 * @property string $tax
 * @property string $total
 * @property string $deposit
 * @property string $reverse
 * @property string $payment_total
 * @property integer $payment_id
 * @property string $payment_method
 * @property integer $deliv_id
 * @property integer $deliv_time_id
 * @property string $deliv_time
 * @property string $deliv_no
 * @property string $note
 * @property integer $status
 * @property string $create_date
 * @property integer $create_member_id
 * @property string $loan_result
 * @property string $credit_result
 * @property string $credit_msg
 * @property string $update_date
 * @property integer $update_member_id
 * @property string $commit_date
 * @property integer $del_flg
 * @property string $deliv_date
 * @property string $conveni_data
 * @property string $cell01
 * @property string $cell02
 * @property string $cell03
 * @property string $memo01
 * @property string $memo02
 * @property string $memo03
 * @property string $memo04
 * @property string $memo05
 * @property string $memo06
 * @property string $memo07
 * @property string $memo08
 * @property string $memo09
 * @property string $memo10
 * @property integer $campaign_id
 * @property integer $credit_rate
 * @property integer $shop_id
 * @property string $shipping_no
 * @property integer $gift
 */
class Order extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ecOrange');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_temp_id', 'message', 'order_name01', 'order_name02', 'order_kana01', 'order_kana02', 'order_email', 'order_tel01', 'order_tel02', 'order_tel03', 'order_fax01', 'order_fax02', 'order_fax03', 'order_zip01', 'order_zip02', 'order_pref', 'order_addr01', 'order_addr02', 'deliv_name01', 'deliv_name02', 'deliv_kana01', 'deliv_kana02', 'deliv_tel01', 'deliv_tel02', 'deliv_tel03', 'deliv_fax01', 'deliv_fax02', 'deliv_fax03', 'deliv_zip01', 'deliv_zip02', 'deliv_pref', 'deliv_addr01', 'deliv_addr02', 'payment_method', 'deliv_time', 'deliv_no', 'note', 'loan_result', 'credit_result', 'credit_msg', 'deliv_date', 'conveni_data', 'cell01', 'cell02', 'cell03', 'memo01', 'memo02', 'memo03', 'memo04', 'memo05', 'memo06', 'memo07', 'memo08', 'memo09', 'memo10'], 'string'],
            [['customer_id', 'create_date', 'create_member_id', 'update_member_id', 'credit_rate', 'shop_id'], 'required'],
            [['customer_id', 'order_sex', 'order_job', 'coupon_id', 'payment_id', 'deliv_id', 'deliv_time_id', 'status', 'create_member_id', 'update_member_id', 'del_flg', 'campaign_id', 'credit_rate', 'shop_id', 'gift'], 'integer'],
            [['order_birth', 'create_date', 'update_date', 'commit_date'], 'safe'],
            [['subtotal', 'discount', 'deliv_fee', 'charge', 'use_point', 'add_point', 'birth_point', 'last_order_total_point', 'order_total_point', 'coupon_price', 'tax_rate', 'tax', 'total', 'deposit', 'reverse', 'payment_total'], 'number'],
            [['coupon_label'], 'string', 'max' => 200],
            [['shipping_no'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_temp_id' => 'インターネット受注の場合 dtb_order_temp のID',
            'customer_id' => '顧客ID',
            'message' => 'お客様の言葉',
            'order_name01' => '顧客',
            'order_name02' => '顧客',
            'order_kana01' => '顧客',
            'order_kana02' => '顧客',
            'order_email' => '顧客',
            'order_tel01' => '顧客',
            'order_tel02' => 'Order Tel02',
            'order_tel03' => '顧客',
            'order_fax01' => 'Order Fax01',
            'order_fax02' => 'Order Fax02',
            'order_fax03' => 'Order Fax03',
            'order_zip01' => '顧客',
            'order_zip02' => '顧客',
            'order_pref' => '顧客',
            'order_addr01' => '顧客',
            'order_addr02' => '顧客',
            'order_sex' => 'Order Sex',
            'order_birth' => 'Order Birth',
            'order_job' => 'Order Job',
            'deliv_name01' => '配送先',
            'deliv_name02' => '配送先',
            'deliv_kana01' => '配送先',
            'deliv_kana02' => '配送先',
            'deliv_tel01' => '配送先',
            'deliv_tel02' => '配送先',
            'deliv_tel03' => '配送先',
            'deliv_fax01' => 'Deliv Fax01',
            'deliv_fax02' => 'Deliv Fax02',
            'deliv_fax03' => 'Deliv Fax03',
            'deliv_zip01' => '配送先',
            'deliv_zip02' => 'Deliv Zip02',
            'deliv_pref' => '配送先',
            'deliv_addr01' => '配送先',
            'deliv_addr02' => '配送先',
            'subtotal' => '商品総額',
            'discount' => '特別値引',
            'deliv_fee' => '配送料',
            'charge' => '配送手数料',
            'use_point' => 'ポイント値引',
            'add_point' => 'ポイント付与',
            'birth_point' => 'Birth Point',
            'last_order_total_point' => 'Last Order Total Point',
            'order_total_point' => 'Order Total Point',
            'coupon_id' => 'Coupon ID',
            'coupon_label' => 'Coupon Label',
            'coupon_price' => 'Coupon Price',
            'tax_rate' => '税率',
            'tax' => '税額',
            'total' => '経理上の売上',
            'deposit' => 'お預り金額',
            'reverse' => 'お釣り',
            'payment_total' => '請求金額',
            'payment_id' => '支払方法',
            'payment_method' => '支払方法の文字列',
            'deliv_id' => 'Deliv ID',
            'deliv_time_id' => '配達時間指定',
            'deliv_time' => '配達時間の文字列',
            'deliv_no' => 'Deliv No',
            'note' => '社内メモ',
            'status' => '注文の状態',
            'create_date' => '受注日時',
            'create_member_id' => '作成者',
            'loan_result' => 'Loan Result',
            'credit_result' => 'Credit Result',
            'credit_msg' => 'Credit Msg',
            'update_date' => 'Update Date',
            'update_member_id' => '更新者',
            'commit_date' => '店頭：商品引渡日時／熱海：発送日',
            'del_flg' => '削除:1',
            'deliv_date' => 'Deliv Date',
            'conveni_data' => 'Conveni Data',
            'cell01' => 'Cell01',
            'cell02' => 'Cell02',
            'cell03' => 'Cell03',
            'memo01' => 'Memo01',
            'memo02' => 'Memo02',
            'memo03' => 'Memo03',
            'memo04' => 'Memo04',
            'memo05' => 'Memo05',
            'memo06' => 'Memo06',
            'memo07' => 'Memo07',
            'memo08' => 'Memo08',
            'memo09' => 'Memo09',
            'memo10' => 'Memo10',
            'campaign_id' => 'Campaign ID',
            'credit_rate' => '掛け率',
            'shop_id' => '店舗ID',
            'shipping_no' => '送り状番号',
            'gift' => '真なら納品書に金額を印字しない',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Order::className(), ['customer_id' => 'customer_id']);
    }

}