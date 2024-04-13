<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/CustomerSearchForm.php $
 * $Id: CustomerSearchForm.php 1099 2015-06-23 07:50:57Z mori $
 */

namespace common\models\webdb;

use Yii;

/**
 * CustomerSearchForm represents the model behind the search form about `common\models\webdb\Customer`.
 */
class CustomerSearchForm extends \yii\base\Model
{
    public $db;
    public $customerid;
    public $name01;
    public $name02;
    public $kana01;
    public $kana02;
    public $birth_y;
    public $birth_m;
    public $birth_d;
    public $tel01;
    public $tel02;
    public $tel03;
    public $agreed;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name01','name02','kana01','kana02','tel01','tel02','tel03','birth_y','birth_m','birth_d','agreed'], 'required'],
            [['name01','name02','kana01','kana02'], 'filter', 'filter'=>'trim'],
            [['tel01','tel02','tel03'], 'integer', 'min'=> 1, 'max'=> 99999 ],
            [['tel01','tel02','tel03'], 'string', 'length'=> [2,5] ],
            [['customerid'], 'integer', 'min'=>1],
            [['birth_y','birth_m','birth_d'], 'integer'],
            [['birth_y'], 'in', 'range'=> range(1900, date('Y')) ],
            [['birth_m'], 'in', 'range'=> range(1,12) ],
            [['birth_d'], 'in', 'range'=> range(1,31) ],
            ['agreed', 'in', 'range' => [1] ],
        ];
    }
    public function attributeLabels()
    {
        return [
            'agreed' => "プライバシーポリシーに同意する",
            'name01' => "姓",
            'name02' => "名",
            'kana01' => "せい",
            'kana02' => "めい",
            'tel'    => "電話番号",
            'tel01'  => "市外局番",
            'tel02'  => "市内局番",
            'tel03'  => "枝番",
        ];
    }

    public function afterValidate()
    {
        $min = 10;
        $max = 11;
        // if(! $this->hasErrors() &&
        //    ((strlen($this->getTel()) < $min) || (strlen($this->getTel()) < $max))
        // )
        //     $this->addError('tel01', "電話番号が国内の番号ではありません");
        // reference - http://www.soumu.go.jp/main_sosiki/joho_tsusin/top/tel_number/q_and_a.html
            
        return parent::afterValidate();
    }

    protected function getBirth()
    {
        if(! $this->validate('birth_y') || ! $this->validate('birth_m') || ! $this->validate('birth_d'))
            return null;
        
        return sprintf('%04d/%02d/%02d', $this->birth_y, $this->birth_m, $this->birth_d);
    }

    protected function getKana()
    {
        if(! isset($this->kana01) && ! isset($this->kana02))
            return null;

        return sprintf('%s　%s', $this->kana01, $this->kana02);
    }

    protected function getName()
    {
        if(! isset($this->name01) && ! isset($this->name02))
            return null;

        return sprintf('%s　%s', $this->name01, $this->name02);
    }

    public function getTel()
    {
        if((! $this->validate('tel01')) || (! $this->validate('tel02')) || (! $this->validate('tel03')) )
            return null;
        if(! $this->validate('tel02'))
            Yii::warning('tel02 is null');
        
        return sprintf('%s%s%s', $this->tel01, $this->tel02, $this->tel03);
    }

    /**
     * Get CustomerForm instances with self attributes
     *
     * @return array of CustomerForm
     */
    public function search()
    {
        if (! $this->validate() )
            return false;

        $hj = Yii::$app->get('webdb18');
        $he = Yii::$app->get('webdb20');

        $rows = array_merge(
            $this->searchFrom($hj),
            $this->searchFrom($he));

        return $rows;
    }

    /* @return array An empty array is returned if the query results in nothing. */
    public function searchFrom($db)
    {
        if(! $this->tel && ! $this->birth)
            return [];

        $columns = implode(',',[
            'c.customerid',
            'c.name',
            'c.kana',
            'c.sexid',
            'c.entrydate',
            'c.updatedate',
            'c.birth',
            'c.email',
            'c.sexid',
            'c.wireless',
            'a.postnum',
            'a.address1',
            'a.address2',
            'a.address3',
            'a.tel',
            'a.fax',
            'a.mobile',
            'a.email as email2',
        ]);

        $qstring = "SELECT "
                 . $columns
                 . " FROM tblcustomer c JOIN tbladdress a ON a.customerid = c.customerid WHERE "
                 . " c.birth = :birth AND "
                 . " (a.tel = :tel OR a.mobile = :mobile OR a.fax = :fax)";

        $cmd = $db->createCommand($qstring);

        $values = [];
        //if($this->birth)
            $values = array_merge($values, [':birth' => $this->birth]);

        //if($this->tel)
            $values = array_merge($values, [
                ':tel'    => $this->tel,
                ':mobile' => $this->tel,
                ':fax'    => $this->tel]);

        $cmd->bindValues($values);
        //var_dump($values);exit;                                  
        $rows = $cmd->queryAll();

        if('euc-jp' === $db->charset)
        foreach($rows as $i => $row)
        {
            foreach($row as $key => $column)
            {
                // convert EUC-WIN-JP to utf8
                $utf8 = mb_convert_encoding($column, 'UTF-8', 'CP51932');
                $rows[$i][$key] = $utf8;
            }
        }

        $models = [];
        foreach($rows as $row)
        {
            if($row['name'] != $this->name)
                continue;

            $kana = [
                \common\components\KanaHelper::toHiragana($row['kana']),
                \common\components\KanaHelper::toHiragana($this->kana),
            ];

            if($kana[0] != $kana[1])
                continue;

            $model = new CustomerForm();
            $model->load(['CustomerForm'=>$row]);
            $models[] = $model;
        }

        return $models;
    }
}
