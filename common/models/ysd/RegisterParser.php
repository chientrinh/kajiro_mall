<?php

namespace common\models\ysd;

use Yii;

/**
 * This is the model class for handling `YSD 口座振替依頼結果ファイル`
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/RegisterParser.php $
 * $Id: RegisterParser.php 2254 2016-03-17 04:22:28Z mori $
 *
 */

class RegisterParser extends \yii\base\Model
{
    const LINE_WIDTH = 350;

    const PREFIX_HEADER  = '1';
    const PREFIX_BODY    = '2';
    const PREFIX_FOOTER  = '9';

    public $file   = null;
    public $header = null;
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

            $this->populateModel($line);
        }
        if(! $this->isLineFooter($line))
             $this->addError('file',"parse error, found wrong footer ($line)");

        return $this->hasErrors();
    }

    /* @return bool */
    private function populateHeader($line)
    {
        $model = new RegisterResponseHeader();
        $model->feed($line);

        if($model->validate())
            $this->header = $model;

        return ! $model->hasErrors();
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
        $model = new RegisterResponse(['cdate'=> $this->header->cdate]);
        $model->feed($line);

        if(! $model->validate())
             $this->addError('models',implode(';',$model->firstErrors).sprintf('[%s]',$line));

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

