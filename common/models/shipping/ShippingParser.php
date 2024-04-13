<?php

namespace common\models\shipping;

use Yii;

/**
 * This is the model class for handling `出荷実績ファイル`
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/shipping/ShippingParser.php $
 * $Id: ShippingParser.php 2254 2020-09-28 04:22:28Z mori $
 *
 */

class ShippingParser extends \yii\base\Model
{
    const LINE_WIDTH = 350;

    public $delivery_company_id = null;
    public $file   = null;
    public $header = null;
    public $add_purchase_ids = [];
    public $add_purchase_count = 0;
    public $models = [];

    public function rules()
    {
        return [
            ['file','required'],
            ['file','validateFile'],
        ];
    }

    /* @return bool */
    public function parse()
    {
        if(! $this->validate())
            return false;

        $fp = fopen($this->file, 'r');

        $ret = $this->parseLines($fp);

        fclose($fp);

        return $ret;
    }

    /* @return bool */
    private function parseLines($fp)
    {
        $this->models = [];
        $this->clearErrors();

        $header = null;
        $buf    = [];

        $line = self::readline($fp);
        if(! $this->populateHeader($line))
        {
            $this->addError('header',"parse error, header is invalid ($line)");
            return false;
        }

        while($line = self::readline($fp))
        {
            if(! $this->isLineBody($line))
                break;

            // 出荷実績csvはSJISなので、変換する
            if(Yii::$app->charset !== 'SJIS-WIN')
                $line = mb_convert_encoding($line, Yii::$app->charset, 'SJIS-WIN');

            // ヤマトからのデータは””が入ってるため外す
            $line = str_replace("\"","",$line);

            $this->populateModel($line);
        }
        // if(! $this->isLineFooter($line))
        //      $this->addError('file',"parse error, found wrong footer ($line)");

        return $this->hasErrors();
    }

    /* @return bool */
    private function populateHeader($line)
    {
        $model = new ShippingHeader();
        $model->feed($line);

        if($model->validate())
            $this->header = $model;

        return ! $model->hasErrors();
    }

    /* @return bool */
    private function isLineBody($line)
    {
        $div = substr($line, 0, 1);

        // if($div === self::PREFIX_BODY)
        if(count($line) > 0)
            return true;
    }

    /* @return bool */
    private function isLineFooter($line)
    {
        $buf = explode(',', rtrim($line));
        $div = array_shift($buf);
        $cnt = array_shift($buf);
        $cnt = (int) $cnt - 2; // cnt == count(models) + 1 /*header*/ + 1 /*footer*/

        return ($div === self::PREFIX_FOOTER) &&
               ($cnt === count($this->models));
    }

    /* @return void */
    private function populateModel($line)
    {
        // var_dump($line);
        // $model Purchase
        $model = new Shipping();
        // $model = new RegisterResponse(['cdate'=> $this->header->cdate]);
        $model->feed($line);
        
        $bigint = gmp_init($model->shipping_id);
        try {
            $bigint = gmp_init($model->shipping_id);
            if(!$bigint) {
                // throw new \yii\db\Exception("送り状番号：".$model->shipping_id." _  error :  送り状番号が不正です データをExcelではなくメモ帳などで開いて確認をお願いします<br/>".sprintf('[%s]<br />',$line));
                $this->addError('models',"送り状番号：".$model->shipping_id." _  error :  送り状番号が不正です データをExcelではなくメモ帳などで開いて確認をお願いします<br/>".sprintf('[%s]<br />',$line));
                return;
            }
        } catch(yii\base\ErrorException $e) {
             $this->addError('models',"送り状番号：".$model->shipping_id." _  error :  送り状番号が不正です データをExcelではなくメモ帳などで開いて確認をお願いします<br/>".sprintf('[%s]<br />',$line));
             return;
        }

        // 冷凍便の場合は、[お客様管理番号]Z ex) 331563Z  であるため、Zを除外してから判定する
        $purchase_id = $model->purchase_id;
        $frozen_flag = false;

        if('Z' == substr($model->purchase_id, -1))
            $frozen_flag = true;
    
        if($frozen_flag) {
            $purchase_id = substr($model->purchase_id, 0, 6);
        }

        // 出版伝票（P・・・・）を考慮した実装。処理から排除する
        if(strpos($purchase_id, 'P') === 0){
            return;
        }

        $purchase = \common\models\Purchase::findOne(['purchase_id' => $purchase_id]);

        if(!$purchase) {
            return;
        }

        $shipping_id = $frozen_flag ? $purchase->shipping_frozen_id : $purchase->shipping_id;

        // 登録処理に追加済みの伝票はスルーする
        if(in_array((int)$purchase_id, $this->add_purchase_ids) && (count($purchase->items) == $purchase->frozen_items_count))
            return; 

        // 伝票が存在し、データに送り状番号が未登録のものについて処理する。なお、CSVで出荷状況が「””」「配達完了」「配達完了（宅配BOX）」でないものについてが対象。       
        if($purchase && (!$purchase->shipping_id || $purchase->shipping_id == "") && $model->status && !in_array($model->status,['','配達完了','配達完了（宅配BOX）'])) {
            if($frozen_flag) {
                $purchase->shipping_frozen_id = $model->shipping_id;
            } else {
                $purchase->shipping_id = $model->shipping_id;
            }

            $purchase->delivery_company_id = $this->delivery_company_id;
            if(isset($model->arrangement_date) && $model->arrangement_date != "")
                $purchase->arrangement_date = $model->arrangement_date;

            if(! $purchase->validate()) {
                $this->addError('models',"送り状番号：".$model->shipping_id." _ ".implode(';',$purchase->firstErrors)." : error :  ".sprintf('[%s]<br />',$line));
            }

            $this->models[] = $purchase;
       }

    }

    private static function readline($fp)
    {
        return fgets($fp);
    }

    public function validateFile($attr, $params)
    {
        $file = $this->file;

        if(! $file)
            return false;

        elseif(! is_file($file))
            $this->addError($attr, 'is not file');

        elseif(! is_readable($file))
            $this->addError($attr, 'is not readable');

        return $this->hasErrors($attr);
    }

}

