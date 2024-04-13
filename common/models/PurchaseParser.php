<?php

namespace common\models;

use Yii;

/**
 * This is the model class for handling `売上集計ファイル`
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/RegisterParser.php $
 * $Id: RegisterParser.php 2254 2016-03-17 04:22:28Z mori $
 *
 */

class PurchaseParser extends \yii\base\Model
{
    public $file   = null;
    public $event_name = "";
    public $header = null;
    public $items = [];
    public $companies = ['HJ' => 2, 'HE' => 3, 'HP' => 4, 'TY' => 1, 'TR' => 6]; // TROSEも一応入れておく
    public $company_id = 0;
    public $customer_id = 0;
    public $total_price = 0;
    public $branch_id = 0;

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
        $split = split('_',$this->file);
// ファイル名が会社名（HE,HPなど）で始まる、HP_○○○_ブース売上.csv である前提
        $this->company_id = $this->companies[end(split('/', $split[0]))];
        $this->event_name = $split[1];
        $ret = $this->parseLines($fp);

        fclose($fp);
        return $ret;
    }

    /* @return bool */
    private function parseLines($fp)
    {
        $this->items = [];
        $this->clearErrors();

        $header = null;
        $company_id = 0;

        $buf    = [];
        $idx = 0;
        $line = self::readline($fp);
        $line = mb_convert_encoding($line,  "UTF-8", "SJIS");
        
        $model = new PurchaseHeader();
        if(!$model->feed($line))
        {
            $this->addError('header', "項目数に誤りがあります。". $line);
            return false;
        }

        $idx++; 
        
        // 中身をパースしていく
        while($line = self::readline($fp))
        {
            $line = mb_convert_encoding($line,  "UTF-8", "SJIS");
            $model = new PurchaseHeader();
            $model->feed($line);
            // 会社を含まない＝データ終了ならループを抜ける
            if(! $this->isLineBody($line))
                break;

            if($idx != 0 && (!in_array(substr($line, 0, 2), array_keys($this->companies)) ||! $this->validateColumns($model)))
            {
                $this->addError('header',"parse error,  ($line) line $idx");
                return false;
            }
            
            $this->company_id = $this->companies[$model->company];
            $this->customer_id = $model->customer_id;

            // 集計処理
            $this->total_price += $model->price;
            if($model->validate()) {
                // PurchaseItemを作成する
                $this->items[] = $this->createItem($model, $idx-1);
            } else {
                $item = $this->createItem($model, $idx-1);
                if($item->charge < 0 || $item->qty < 0) {
                    $this->items[] = $item;
                }
            }
            $idx++;
        }
        // Purchase 伝票を作成
        $this->header = new Purchase([
            'payment_id' => 1,
            'paid'       => 1,
            'shipped'    => 1,
            'company_id' => $this->company_id,
            'branch_id'  => $this->branch_id,
            'customer_id' => $this->customer_id,
            'tax'        => 0,
            'point_consume' => 0,
            'receive'    => $this->total_price,
            'subtotal'   => $this->total_price,
            'total_charge' => $this->total_price,
            'note'       => $this->event_name, 
        ]);
         
        return $this->hasErrors();
    }

    /* @return bool */
    private function validateColumns($model)
    {
        return ! $model->hasErrors();
    }
    /* @return bool */
    private function isLineBody($line)
    {
        $div = substr($line, 0, 2);
        return strlen($div) > 0;
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

    /**
     * CSVから取り出したデータ１行からPurchaseItemを作成する
     *
     **/
    private function createItem($model, $seq)
    {
        $purchaseItem = new PurchaseItem();
        $purchaseItem->company_id = $this->company_id;
        $purchaseItem->code = $model->jan;
        $purchaseItem->name = $model->name;
        $purchaseItem->price = $model->price / $model->qty;
        $purchaseItem->quantity = $model->qty;
        $purchaseItem->is_wholesale = 0;
        $purchaseItem->seq = $seq;
        return $purchaseItem;
        
    }

    /* @return void */
    private function populateModel($line)
    {
        // ここでPurchaseを作成、「save」（確定）してpurchase_idを確保し、PurchaseItemを一気に作成する
        $purchase = new Purchase();
        $purchase->feed($line);

	if(! $purchase->validate() || ! $purchase->save())
             $this->addError('models',implode(';',$purchase->firstErrors).sprintf('[%s]',$line));

        if($purchase->save())
             $model = new PurchaseItem();
             // Itemをひたすら作成する
        $this->models[] = $model;
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

