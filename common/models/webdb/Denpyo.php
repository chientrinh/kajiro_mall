<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tbldenpyo".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/Denpyo.php $
 * $Id: Denpyo.php 1819 2015-11-16 20:33:44Z mori $
 *
 * @property integer $denpyoid
 * @property integer $denpyo_num
 * @property string $denpyo_num_division
 * @property integer $customerid
 * @property integer $sell_limitid
 * @property integer $denpyo_companyid
 * @property integer $denpyo_shinamonoid
 * @property integer $denpyo_haitatsuid
 * @property integer $nyukinid
 * @property integer $denpyo_send_timeid
 * @property integer $denpyo_orderid
 * @property integer $denpyo_loginid
 * @property string $denpyo_login
 * @property integer $denpyo_centerid
 * @property integer $denpyo_print_flag
 * @property string $denpyo_coment
 * @property string $denpyo_date
 * @property integer $denpyo_sagawa_num
 * @property integer $denpyo_sell_ttl
 * @property integer $denpyo_tax
 * @property integer $denpyo_send_price
 * @property integer $denpyo_daibiki_tesuryo
 * @property integer $denpyo_all_ttl
 * @property integer $print_nohin_f
 * @property integer $print_sagawa_f
 * @property integer $print_label_f
 * @property integer $denpyo_nyukin_amount
 * @property string $denpyo_nyukin_status
 * @property string $denpyo_nyukin_date
 * @property integer $stock_order_f
 * @property integer $customer_2_2_divisionid
 * @property integer $stock_revorder_f
 * @property string $sodan_homeopath
 * @property integer $denpyo_reserv_amount
 * @property integer $denpyo_back_amount
 * @property string $denpyo_time
 * @property string $denpyo_num_sub
 * @property integer $print_seal_f
 * @property integer $ttl50000ge
 * @property string $denpyo_coment2
 * @property string $double_reg
 * @property integer $commission_ttl
 * @property string $trans_code
 * @property string $send_addr_tbl
 * @property integer $send_addrid
 * @property string $send_name
 * @property string $send_kana
 * @property string $send_postnum
 * @property string $send_address1
 * @property string $send_address2
 * @property string $send_address3
 * @property string $send_tel
 * @property string $send_fax
 * @property string $send_mobile
 * @property string $denpyo_send_date
 * @property integer $amount_dsp_f
 * @property integer $sodan_homeopathid
 * @property integer $tokuten_ttl
 * @property integer $tokuten_amount
 * @property integer $tokuten_amount_per
 *
 * @property TbldItemLog[] $tbldItemLogs
 * @property Tblcustomer $customer
 * @property TmdenpyoCompany $denpyoCompany
 * @property TmdenpyoOrder $denpyoOrder
 * @property TmdenpyoShinamono $denpyoShinamono
 */
abstract class Denpyo extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbldenpyo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['denpyo_num', 'customerid', 'sell_limitid', 'denpyo_companyid', 'denpyo_shinamonoid', 'denpyo_haitatsuid', 'nyukinid', 'denpyo_send_timeid', 'denpyo_orderid', 'denpyo_loginid', 'denpyo_centerid', 'denpyo_print_flag', 'denpyo_sagawa_num', 'denpyo_sell_ttl', 'denpyo_tax', 'denpyo_send_price', 'denpyo_daibiki_tesuryo', 'denpyo_all_ttl', 'print_nohin_f', 'print_sagawa_f', 'print_label_f', 'denpyo_nyukin_amount', 'stock_order_f', 'customer_2_2_divisionid', 'stock_revorder_f', 'denpyo_reserv_amount', 'denpyo_back_amount', 'print_seal_f', 'ttl50000ge', 'commission_ttl', 'send_addrid', 'amount_dsp_f', 'sodan_homeopathid', 'tokuten_ttl', 'tokuten_amount', 'tokuten_amount_per'], 'integer'],
            [['denpyo_num_division', 'denpyo_login', 'denpyo_coment', 'denpyo_date', 'denpyo_nyukin_status', 'denpyo_nyukin_date', 'sodan_homeopath', 'denpyo_time', 'denpyo_num_sub', 'denpyo_coment2', 'double_reg', 'trans_code', 'send_addr_tbl', 'send_name', 'send_kana', 'send_postnum', 'send_address1', 'send_address2', 'send_address3', 'send_tel', 'send_fax', 'send_mobile', 'denpyo_send_date'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'denpyoid' => 'Denpyoid',
            'denpyo_num' => 'Denpyo Num',
            'denpyo_num_division' => 'Denpyo Num Division',
            'customerid' => 'Customerid',
            'sell_limitid' => 'Sell Limitid',
            'denpyo_companyid' => 'Denpyo Companyid',
            'denpyo_shinamonoid' => 'Denpyo Shinamonoid',
            'denpyo_haitatsuid' => 'Denpyo Haitatsuid',
            'nyukinid' => 'Nyukinid',
            'denpyo_send_timeid' => 'Denpyo Send Timeid',
            'denpyo_orderid' => 'Denpyo Orderid',
            'denpyo_loginid' => 'Denpyo Loginid',
            'denpyo_login' => 'Denpyo Login',
            'denpyo_centerid' => 'Denpyo Centerid',
            'denpyo_print_flag' => 'Denpyo Print Flag',
            'denpyo_coment' => 'Denpyo Coment',
            'denpyo_date' => 'Denpyo Date',
            'denpyo_sagawa_num' => 'Denpyo Sagawa Num',
            'denpyo_sell_ttl' => 'Denpyo Sell Ttl',
            'denpyo_tax' => 'Denpyo Tax',
            'denpyo_send_price' => 'Denpyo Send Price',
            'denpyo_daibiki_tesuryo' => 'Denpyo Daibiki Tesuryo',
            'denpyo_all_ttl' => 'Denpyo All Ttl',
            'print_nohin_f' => 'Print Nohin F',
            'print_sagawa_f' => 'Print Sagawa F',
            'print_label_f' => 'Print Label F',
            'denpyo_nyukin_amount' => 'Denpyo Nyukin Amount',
            'denpyo_nyukin_status' => 'Denpyo Nyukin Status',
            'denpyo_nyukin_date' => 'Denpyo Nyukin Date',
            'stock_order_f' => 'Stock Order F',
            'customer_2_2_divisionid' => 'Customer 2 2 Divisionid',
            'stock_revorder_f' => 'Stock Revorder F',
            'sodan_homeopath' => 'Sodan Homeopath',
            'denpyo_reserv_amount' => 'Denpyo Reserv Amount',
            'denpyo_back_amount' => 'Denpyo Back Amount',
            'denpyo_time' => 'Denpyo Time',
            'denpyo_num_sub' => 'Denpyo Num Sub',
            'print_seal_f' => 'Print Seal F',
            'ttl50000ge' => 'Ttl50000ge',
            'denpyo_coment2' => 'Denpyo Coment2',
            'double_reg' => 'Double Reg',
            'commission_ttl' => 'Commission Ttl',
            'trans_code' => 'Trans Code',
            'send_addr_tbl' => 'Send Addr Tbl',
            'send_addrid' => 'Send Addrid',
            'send_name' => 'Send Name',
            'send_kana' => 'Send Kana',
            'send_postnum' => 'Send Postnum',
            'send_address1' => 'Send Address1',
            'send_address2' => 'Send Address2',
            'send_address3' => 'Send Address3',
            'send_tel' => 'Send Tel',
            'send_fax' => 'Send Fax',
            'send_mobile' => 'Send Mobile',
            'denpyo_send_date' => 'Denpyo Send Date',
            'amount_dsp_f' => 'Amount Dsp F',
            'sodan_homeopathid' => 'Sodan Homeopathid',
            'tokuten_ttl' => 'Tokuten Ttl',
            'tokuten_amount' => 'Tokuten Amount',
            'tokuten_amount_per' => 'Tokuten Amount Per',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTbldItemLogs()
    {
        return $this->hasMany(TbldItemLog::className(), ['denpyoid' => 'denpyoid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Tblcustomer::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDenpyoCompany()
    {
        return $this->hasOne(TmdenpyoCompany::className(), ['denpyo_companyid' => 'denpyo_companyid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDenpyoOrder()
    {
        return $this->hasOne(TmdenpyoOrder::className(), ['denpyo_orderid' => 'denpyo_orderid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDenpyoShinamono()
    {
        return $this->hasOne(TmdenpyoShinamono::className(), ['denpyo_shinamonoid' => 'denpyo_shinamonoid']);
    }
}
