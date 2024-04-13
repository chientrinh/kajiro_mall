<?php

namespace common\models\ysd;

use Yii;
use \yii\data\ActiveDataProvider;
use \common\models\Company;
use \common\models\Purchase;

/**
 * This is the widget class to output csv from "transfer_request".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/TransferRequestPackager.php $
 * $Id: TransferRequestPackager.php 3548 2017-08-16 02:17:07Z kawai $
 *
 */
class TransferRequestPackager extends \yii\base\Widget
{
    const  COMPANY_CODE = '801255'; // 委託者コード（みずほファクターが指定した当社コード）
    const  CHARSET      = 'SJIS-WIN';
    const  DELIMITER    = ',';
    const  EOL          = "\r\n";

    public $dataProvider;
    public $output;

    public function init()
    {
        parent::init();

        if(! $this->dataProvider)
            throw new \yii\base\InvalidConfigException('dataProvider is not defined');

        if(! $this->output)
             $this->output = tempnam(Yii::getAlias('@runtime'), "trq") . '.csv';
    }

    public function run()
    {
        $this->renderContent();
    }

    private function renderContent()
    {
        $fp = fopen($this->output, 'w');

        $csv = $this->renderHeader();
        fwrite($fp, $csv);

        $provider = clone($this->dataProvider);
        foreach($provider->getModels() as $model)
        {
            $csv = $this->renderItem($model);
            fwrite($fp, $csv);
        }

        $csv = $this->renderFooter();
        fwrite($fp, $csv);

        fclose($fp);
    }
    
    public function renderCsv($user_data, $record_count)
    {
        $fp = fopen($this->output, 'w');

        $csv = $this->renderHeader();
        fwrite($fp, $csv);

        fwrite($fp, $user_data);

        $csv = $this->renderFooterLine($record_count);
        fwrite($fp, $csv);

        fclose($fp);
    }
    
    private function renderHeader()
    {
        return implode(self::DELIMITER, [
            '1',        // レコード区分 (1:へッダ)
            date('Ymd'),// データ作成日
            self::COMPANY_CODE,
            '00',       // 区分      (00:固定値)
            '02',       // データ区分 (01:口座振替のみ 02:口座振替および帳票 03:帳票のみ)
            '01',       // 帳票区分   (01:固定値)
        ])
        . self::EOL;
    }
    
    public function renderPurchaseItem(TransferRequest $model, Purchase $purchase)
    {
        $cdate  = date('Ymd', strtotime($model->cdate)); // 請求締め日
        $udate  = date('Y-m', strtotime($model->cdate)); // ご利用年月
        
        $purchase_date = date('Ymd', strtotime($purchase->create_date));

        $c     = $model->customer;
        $o     = $c->office; // $c must not be null

//        $name  = $o ? $o->person_name : $c->name;
//        $kana  = $o ? null            : $c->kana;
//        $com   = $o ? $o->company_name: null    ;
//        $addr  = $o ? $o->addr        : $c->addr;
//        $tel   = $o ? $o->tel         : $c->tel ;
//        $zip   = $o ? $o->zip         : $c->zip ;
        $name  = $c->name;
        $kana  = $c->kana;
        $com   = null    ;
        $addr  = $c->addr;
        $tel   = $c->tel ;
        $zip   = $c->zip ;

        $kana  = mb_convert_kana($kana, 'hk'); // 半角カナに
        $addr  = mb_convert_kana($addr, 'AS'); // [A-Za-z0-9 ]を全角に
        $zip   = preg_replace('/-/', '', $zip);

        $line  = implode(self::DELIMITER, [
            '2',            // 01:レコード区分 (2:本文)
            self::COMPANY_CODE, // 02:委託者コード（みずほファクターが指定した当社コード）
            '00',           // 03:区分 (00:固定値)
            null,           // 04:予備1
            '0',            // 05:帳票納品区分 (0:個人へ送付, 1:事業所へ一括納品)
            str_pad($model->custno, 10, 0, STR_PAD_LEFT), // 06:顧客番号 (当社 dtb_customer.customer_id)
            null,           // 07:銀行コード
            null,           // 08:銀行名
            null,           // 09:支店コード
            null,           // 10:支店名
            null,           // 11:予備2
            null,           // 12:預金種別
            null,           // 13:口座番号
            null,           // 14:預金者カナ
            null,           // 15:預金者漢字
            $model->charge, // 16:振替金額
            $model->pre,    // 17:初回引き渡し
            null,           // 18:結果コード
            null,           // 19:予備3
            null,           // 20:予備4
            $com,           // 21:顧客社名
            null,           // 22:顧客部署名
            $kana,          // 23:顧客氏名カナ（請求先）
            $name,          // 24:顧客氏名漢字（請求先）
            $zip,           // 25:顧客郵便番号（請求先）
            $addr,          // 26:顧客住所（請求先）
            $tel,           // 27:顧客TEL（請求先）
            $model->charge, // 28:請求金額
            0,              // 29:うち消費税
            $cdate,         // 30:請求締め日
            null,           // 31:通信欄1 （未使用）
            null,           // 32:通信欄2 （未使用）
            null,           // 33:通信欄3 （未使用）
            null,           // 34:通信欄4 （未使用）
            $purchase_date." ", // 35:明細1 ご利用年月日（文字列）(未落ちの場合、はがきへ印字される)
            $purchase->purchase_id." ", // 36:明細2 伝票ID（数値）(未落ちの場合、はがきへ印字される)
            $purchase->total_charge." ", // 37:明細3 ご利用金額（数値）(未落ちの場合、はがきへ印字される)
            null,           // 38:明細4 （未使用）
            null,           // 39:明細5 （未使用）
            null,           // 40:明細6 （未使用）
        ])
        . self::EOL;

        return mb_convert_encoding($line, self::CHARSET, Yii::$app->charset);
    }

    private function renderItem(TransferRequest $model)
    {
        $cdate  = date('Ymd', strtotime($model->cdate)); // 請求締め日
        $udate  = date('Y-m', strtotime($model->cdate)); // ご利用年月

        $c     = $model->customer;
        $o     = $c->office; // $c must not be null

        $name  = $o ? $o->person_name : $c->name;
        $kana  = $o ? null            : $c->kana;
        $com   = $o ? $o->company_name: null    ;
        $addr  = $o ? $o->addr        : $c->addr;
        $tel   = $o ? $o->tel         : $c->tel ;
        $zip   = $o ? $o->zip         : $c->zip ;

        $kana  = mb_convert_kana($kana, 'hk'); // 半角カナに
        $addr  = mb_convert_kana($addr, 'AS'); // [A-Za-z0-9 ]を全角に
        $zip   = preg_replace('/-/', '', $zip);

        $line  = implode(self::DELIMITER, [
            '2',            // 01:レコード区分 (2:本文)
            self::COMPANY_CODE, // 02:委託者コード（みずほファクターが指定した当社コード）
            '00',           // 03:区分 (00:固定値)
            null,           // 04:予備1
            '0',            // 05:帳票納品区分 (0:個人へ送付, 1:事業所へ一括納品)
            $model->custno, // 06:顧客番号 (当社 dtb_customer.customer_id)
            null,           // 07:銀行コード
            null,           // 08:銀行名
            null,           // 09:支店コード
            null,           // 10:支店名
            null,           // 11:予備2
            null,           // 12:預金種別
            null,           // 13:口座番号
            null,           // 14:預金者カナ
            null,           // 15:預金者漢字
            $model->charge, // 16:振替金額
            $model->pre,    // 17:初回引き渡し
            null,           // 18:結果コード
            null,           // 19:予備3
            null,           // 20:予備4
            $com,           // 21:顧客社名
            null,           // 22:顧客部署名
            $kana,          // 23:顧客氏名カナ（請求先）
            $name,          // 24:顧客氏名漢字（請求先）
            $zip,           // 25:顧客郵便番号（請求先）
            $addr,          // 26:顧客住所（請求先）
            $tel,           // 27:顧客TEL（請求先）
            $model->charge, // 28:請求金額
            0,              // 29:うち消費税
            $cdate,         // 30:請求締め日
            null,           // 31:通信欄1 （未使用）
            null,           // 32:通信欄2 （未使用）
            null,           // 33:通信欄3 （未使用）
            null,           // 34:通信欄4 （未使用）
            $udate,         // 35:明細1 ご利用年月（文字列）(未落ちの場合、はがきへ印字される)
            $model->charge, // 36:明細2 ご利用金額（数値）(未落ちの場合、はがきへ印字される)
            null,           // 37:明細3 （未使用）
            null,           // 38:明細4 （未使用）
            null,           // 39:明細5 （未使用）
            null,           // 40:明細6 （未使用）
        ])
        . self::EOL;

        return mb_convert_encoding($line, self::CHARSET, Yii::$app->charset);
    }

    private function renderFooter()
    {
        $wc = $this->dataProvider->totalCount
            + 1 /* header */
            + 1 /* footer */;

        return implode(self::DELIMITER, [
            '9',        // レコード区分 (9:フッタ)
            $wc,        // レコード総件数（ヘッダとフッタを含む行数）
        ])
        . self::EOL;
    }
    
    private function renderFooterLine($record_count)
    {
        $wc = $record_count
            + 1 /* header */
            + 1 /* footer */;

        return implode(self::DELIMITER, [
            '9',        // レコード区分 (9:フッタ)
            $wc,        // レコード総件数（ヘッダとフッタを含む行数）
        ])
        . self::EOL;
    }
}
