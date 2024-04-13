<?php

namespace common\models\ysd;

use Yii;

/**
 * This is the model class for table "register_response".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/RegisterResponseHeader.php $
 * $Id: RegisterResponseHeader.php 2254 2016-03-17 04:22:28Z mori $
 *
 */
class RegisterResponseHeader extends \yii\base\Model
{
    public $div1;
    public $cdate;
    public $corp;
    public $div2;
    public $div3;
    public $div4;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['div1','cdate','corp','div2','div3','div4'], 'required'],
            [['div1','cdate','corp','div2','div3','div4'], 'integer'],

            [['div1'], 'in', 'range'=>  ['1']     ],
            [['corp'], 'in', 'range'=>  ['801255']], // 801255: みずほファクターが指定した当社コード

            [['div2','div3','div4'], 'string', 'length' => 2 ],
            [['div2'], 'integer', 'min' => 0, 'max' => 99],
            [['div3'], 'integer', 'min' => 1, 'max' =>  3],
            [['div4'], 'integer', 'min' => 1, 'max' => 99],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'div1'  => 'レコード区分',
            'cdate' => 'ファイル出力日',
            'corp'  => '委託者コード',
            'div2'  => '区分',
            'div3'  => 'データ区分',
            'div4'  => '帳票区分',
        ];
    }

    public function feed($line)
    {
        $buf = explode(',', rtrim($line));

        if(count($buf) !== count($this->attributeLabels()))
            return false;

        $this->div1  = $buf[0];
        $this->cdate = $buf[1];
        $this->corp  = $buf[2];
        $this->div2  = $buf[3];
        $this->div3  = $buf[4];
        $this->div4  = $buf[5];

        return $this->validate();
    }

}
