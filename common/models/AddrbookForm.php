<?php
namespace common\models;

use Yii;

/**
 * Signup form
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/AddrbookForm.php $
 * $Id: AddrbookForm.php 1782 2015-11-11 15:15:56Z mori $
 */

class AddrbookForm extends \yii\base\Model
{
    const SCENARIO_ZIP2ADDR = 'zip2addr';

    public $name01;
    public $name02;
    public $zip01;
    public $zip02;
    public $pref_id;
    public $addr01;
    public $addr02;
    public $tel01;
    public $tel02;
    public $tel03;
    public $addrCandidate = null;

    /* @inheritdoc */
    public function rules()
    {
        return [
            [['name01','name02','zip01','zip02','pref_id','addr01','addr02','tel01','tel02','tel03'], 'required'],
            [['name01','name02','addr01','addr02'], 'string', 'max'=> 255],
            [['zip01','zip02','pref_id','tel01','tel02','tel03'], 'integer'],
            ['pref_id', 'exist', 'targetClass' => '\common\models\Pref', 'targetAttribute' => 'pref_id'],
            ['zip01', 'string', 'length' => 3],
            ['zip02', 'string', 'length' => 4],
            [['tel01','tel02','tel03'], 'string', 'length' => [2, 5]],
        ];
    }

    /* @inheritdoc */
    public function scenarios()
    {
        return array_merge(parent::scenarios(),[
            self::SCENARIO_ZIP2ADDR => ['zip01','zip02'],
        ]);
    }

    /* @inheritdoc */
    public function attributeLabels()
    {
        return [
            'name01'  => "姓",
            'name02'  => "名",
            'name'    => "お名前",
            'zip'     => "郵便番号",
            'pref_id' => "都道府県",
            'addr'    => "住所",
            'addr01'  => "住所1",
            'addr02'  => "住所2",
            'fulladdress' => "住所",
            'tel'     => "電話",
        ];
    }

    /**
     * @return string
     */
    public function getAddr()
    {
        return sprintf('%s %s %s', $this->pref->name, $this->addr01, $this->addr02);
    }

    public function getFullAddress()
    {
        return sprintf('〒%s %s', $this->zip, $this->addr);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return sprintf('%s %s', $this->name01, $this->name02);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        return Pref::findOne($this->pref_id);
    }

    /**
     * @return string
     */
    public function getTel()
    {
        if(! $this->tel01 && ! $this->tel02 && ! $this->tel03)
            return null;

        return sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);
    }

    /**
     * @return string
     */
    public function getZip()
    {
        if(! $this->zip01 && ! $this->zip02)
            return null;

        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    /* @return bool */
    public function load($data, $formName = null)
    {
        if(! parent::load($data, $formName))
            return false;

        if(is_array($data) && array_key_exists('scenario', $data))
            $this->scenario = $data['scenario'];

        if(self::SCENARIO_ZIP2ADDR == $this->scenario)
            return $this->zip2addr();

        return true;
    }

    /**
     * search address from zip code
     * @return bool
     */
    public function zip2addr()
    {
        if(! $this->validate(['zip01','zip02']))
            return false;

        if(! $ret = \common\models\Zip::zip2addr($this->zip01, $this->zip02))
        {
            $this->addError('zip02', "郵便番号に一致する住所が検索できませんでした");
            return false;
        }

        $this->pref_id = $ret->pref_id;
        $this->addr01  = $ret->addr01[0];
        $this->addrCandidate = $ret->addr01;

        return true;
    }

}
