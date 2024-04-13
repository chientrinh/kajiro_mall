<?php

namespace common\models\ysd;

use Yii;

/**
 * This is the model class for handling `YSD 口座振替請求結果ファイル`
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/TransferParser.php $
 * $Id: TransferParser.php 3843 2018-03-14 09:14:15Z mori $
 *
 */

class TransferParser extends \yii\base\Model
{
    const LINE_END = "\r\n";

    const PREFIX_HEADER  = '1';
    const PREFIX_BODY    = '2';
    const PREFIX_FOOTER  = '9';

    public $file = null;
    public $models = [];
    public $request_date = "";
    
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

        // 末尾から余計な空白や改行を取り除く
        $line = rtrim($line);
        
        // 同じ顧客IDの明細レコードが複数ある場合を考慮
        $custno = 0;

        if(! $this->isLineHeader($line))
        {
            $this->addError('file',"wrong format in header: [$line]");
            return false;
        }

        
        while($line = self::readline($fp))
        {
            // 末尾から余計な空白や改行を取り除く
            $line = rtrim($line);
//            var_dump($csv);exit;
            if(! $this->isLineBody($line))
                break;
//            if($this->isLineFooter($line))
//                break; // end of process
            else
                $csv = explode(',', $line);
                $customer = $csv[5];
                if($custno != $customer){
                    $this->populateModel($line);
                    $custno = $customer;
                }
        }

        if(! $this->isLineFooter($line))
             $this->addError('file',"parse error, found wrong footer ($line)");

//        if($line = self::readLine($fp))
//        {
//            $this->addError('file',"something found after footer: [$line]");
//            return false;
//        }

        return $this->hasErrors();
    }

    /* @return bool */
    private function isLineHeader($line)
    {
        $csv = $this->parseline($line);
        if($this->hasErrors())
            return false;

        $byte = [1, 8, 6, 2, 2, 2];

        if(count($csv) !== count($byte))
            return false;

        foreach($byte as $k => $v)
        {
            if(strlen($csv[$k]) !== $v)
                return false;

            if(! is_numeric($csv[$k]))
                return false;
        }

        if($csv[0] !== self::PREFIX_HEADER)
            return false;

        if($csv[1])
            $this->request_date = date('Y-m-d',strtotime($csv[1]));;
        return true;
    }
    
    /* @return bool */
    private function isLineBody($line)
    {
        $div = substr($line, 0, 1);

        if($div === self::PREFIX_BODY)
            return true;
    }

    /* @return bool */
    private function isLineFooter($line)
    {
        $csv = $this->parseline($line);

        if($this->hasErrors())
            return false;

        $byte = [1, 6];
        if(count($csv) !== count($byte))
            return false;

        if($csv[0] !== self::PREFIX_FOOTER)
            return false;

        return true;
    }

    /* @return void */
    private function populateModel($line)
    {
        $model = new TransferResponse();
        $model->feed($line);
        $model->rdate = $this->request_date;
        if(! $model->validate())
             $this->addError('models',implode(';',$model->firstErrors).sprintf('[%s]',$line));

        $this->models[] = $model;
    }

    private function parseline($line)
    {
        $csv = explode(',', $line);

        if(count($csv) < 2)
            $this->addError('file',"wrong format in line: [$line]");

        return $csv;
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

