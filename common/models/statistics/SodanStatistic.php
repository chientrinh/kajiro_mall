<?php
namespace common\models\statistics;

use \common\models\sodan\Interview;
use \common\models\sodan\InterviewStatus;

class SodanStatistic extends \yii\base\Model
{
    public $year;
    public $month;

    public function init()
    {
        parent::init();

        if(! $this->year)
             $this->year = date('Y');

        if(! $this->month)
             $this->month = date('m');
    }

    public function rules()
    {
        return [
            [['year'],  'integer', 'min' => 1900, 'max'=> date('Y')],
            [['month'], 'integer', 'min' =>    1, 'max'=> 12 ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'branch_id'      => '拠点',
            'homoeopath_id'  => 'ホメオパス',
            'interview'      => '相談会 (件)',
            'purchase'       => '支払い (件)',
            'sales'          => '売上',
            'commission'     => '報酬',
        ];
    }

    public function find()
    {
        $query = new \yii\db\Query();

        $query->from(['i' => Interview::tableName()])
              ->leftJoin(['b' => 'mtb_branch'    ],"b.branch_id   = i.branch_id")
              ->leftJoin(['h' => 'dtb_customer'  ],"h.customer_id = i.homoeopath_id")
              ->innerJoin(['l' => 'dtb_customer' ],"l.customer_id = i.client_id")
              ->leftJoin(['p' => 'dtb_purchase'  ],"p.purchase_id = i.purchase_id")
              ->leftJoin(['c' => 'dtb_commission'],'c.purchase_id = p.purchase_id')
              ->leftJoin(['s' => 'mtb_sodan_status'],'s.status_id = i.status_id')
              ->andWhere(['or', ['i.status_id' => InterviewStatus::PKEY_DONE],
                                ['i.status_id' => InterviewStatus::PKEY_KARUTE_DONE],
                                ['i.status_id' =>   InterviewStatus::PKEY_CANCEL]
              ])
              ->andWhere(['EXTRACT(YEAR FROM i.itv_date)'  => $this->year])
              ->andWhere(['EXTRACT(MONTH FROM i.itv_date)' => $this->month]);

        return $query;
    }

    public function getRows($keyColumn)
    {
        if('homoeopath_id' == $keyColumn)
            return $this->getRowsByHomoeopath();

        elseif('branch_id' == $keyColumn)
            return $this->getRowsByBranch();

        throw new \yii\User\Exception('not implemented: '.$keyColumn);
    }

    private function getRowsByBranch()
    {
        return $this->find()
                    ->groupBy('i.branch_id')
                    ->select([
                        'i.branch_id',
                        'b.name',
                        'count(i.itv_id) as interview',
                        'count(p.purchase_id) as purchase',
                        'sum(p.subtotal) as sales',
                        'sum(c.fee) as commission',
                    ])
                    ->all();
    }

    private function getRowsByHomoeopath()
    {
        return $this->find()
                    ->groupBy('i.homoeopath_id')
                    ->select([
                        'i.homoeopath_id',
                        'CASE WHEN h.homoeopath_name = "" OR h.homoeopath_name IS NULL THEN concat(h.name01," ",h.name02) ELSE h.homoeopath_name END as name',
                        'count(i.itv_id) as interview',
                        'count(p.purchase_id) as purchase',
                        'sum(p.subtotal) as sales',
                        'sum(c.fee) as commission',
                    ])
                    ->all();
    }

}
