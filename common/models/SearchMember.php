<?php

namespace common\models;

use Yii;
use yii\db\Query;
use yii\data\ActiveDataProvider;

/**
 * Search Model for Client used in /common/modules/{member,recipe,sodan}
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchMember.php $
 * $Id: SearchMember.php 3851 2018-04-24 09:07:27Z mori $
 */
class SearchMember extends Customer
{
    public $code;
    public $kana;
    public $name;
    public $homoeopath_id;
    public $membership_id;
    public $tel;
    public $branch_id;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code','homoeopath_id','tel','kana', 'branch_id'], 'filter', 'filter'=>'trim'],
            [['code'],'filter',
             'filter' => function($value)
             {
                $ptn = sprintf("/^%s|[0-9]$/", self::EAN13_PREFIX);
                return preg_replace($ptn, '', $value);
             },
             'when'   => function($model){ return (13 == strlen($model->code)); }
            ],
            [['tel'], 'filter', 'filter'=>function($value){ return preg_replace('/-/', '', $value); },],
            [['code','homoeopath_id','tel','pref_id'], 'integer'],
            [['code'],'string', 'min' => 8, 'max'=> 13 ],
            [['tel' ],'string', 'min' => 4, 'max'=>100 ],
            [['name'],'string', 'min' => 1, 'max'=>100 ],
            [['kana','name'], 'filter', 'filter'=>function($value){ return mb_convert_kana($value, 's'); } ],
            [['kana'       ], 'filter', 'filter'=>function($value){ return \common\components\Romaji2Kana::translate($value,'hiragana'); }],
            ['membership_id', 'exist', 'targetClass' => Membership::className() ],
            ['pref_id'      , 'exist', 'targetClass' => Pref::className() ],
            ['code','mustHasAnyAttribute','skipOnEmpty'=>false],
        ];
    }

    public function mustHasAnyAttribute($attr, $value)
    {
        $values = array_unique(array_values([
            $this->code,
            $this->name,
            $this->kana,
            $this->homoeopath_id,
            $this->membership_id,
            $this->tel,
            $this->pref_id,
        ]));
        if(1 < count($values))
            return true;

        $this->addError($attr,"すべての検索欄が未入力です。いずれか１カ所以上に入力してください");
        return false;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @return ActiveDataProvider
     */
    public function loadProvider($query=null)
    {
        if(null === $query)
            $query = self::find()
               ->active()
               ->from('dtb_customer');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => [
                    'kana' => [
                        'asc'  => ['kana01' => SORT_ASC, 'kana02'=> SORT_ASC],
                        'desc' => ['kana01' => SORT_DESC,'kana02'=> SORT_DESC],
                    ],
                ]
            ]
        ]);

        if(! $this->validate())
        {
            $query->andWhere('0 = 1');
            return $provider;
        }

        if($this->code)
        {
            $sub1 = new Query();
            $sub1->from(Membercode::tableName())
                 ->andWhere(['like', 'code', $this->code])
                 ->andWhere(['not',['customer_id' => null ]])
                 ->select('customer_id');

            $sub2 = new Query();
            $sub2->from(CustomerFamily::tableName())
                 ->andWhere(['parent_id' => $sub1->column()])
                 ->select(['child_id']);

            $query->andWhere(['or',
                              ['dtb_customer.customer_id' => $sub1->column() ],
                              ['dtb_customer.customer_id' => $sub2->column() ],
            ]);
        }

        if($this->pref_id)
        {
            $sub3 = new Query();
            $sub3->from(Customer::tableName())
                 ->andWhere(['pref_id' => $this->pref_id])
                 ->select('customer_id');

            $sub4 = new Query();
            $sub4->from(CustomerFamily::tableName())
                 ->andWhere(['parent_id' => $sub3->column()])
                 ->select(['child_id']);

            $query->andFilterWhere(['or',
                                    ['dtb_customer.pref_id' => $this->pref_id],
                                    ['dtb_customer.customer_id' => $sub4->column() ]
            ]);
        }

//        if($this->migrate_id)
//        {
//            $sub5 = new Query();
//
//            $query->andWhere(['dtb_customer.customer_id' =>
//                $sub5->from(Membercode::tableName())
//                     ->andWhere(['like','migrate_id',$this->migrate_id])
//                     ->andWhere(['not',['customer_id' => null ]])
//                     ->select(['customer_id'])
//            ]);
//        }

        if($this->tel)
        {
            $sub6 = new Query();
            $sub6->from(Customer::tableName())
                 ->andWhere(['like','CONCAT(dtb_customer.tel01,dtb_customer.tel02,dtb_customer.tel03)',$this->tel])
                 ->select('customer_id');

            $sub7 = new Query();
            $sub7->from(CustomerFamily::tableName())
                 ->andWhere(['parent_id' => $sub6])
                 ->select(['child_id']);

            $query->andFilterWhere(['or',
                                    ['dtb_customer.customer_id' => $sub6->column() ],
                                    ['dtb_customer.customer_id' => $sub7->column() ]
            ]);
        }

        if($this->membership_id)
        {
            $sub8 = CustomerMembership::find();
            $sub8->andWhere(['membership_id' => $this->membership_id])
                 ->active();

            $query->andWhere([
                'dtb_customer.customer_id' => $sub8->select('customer_id')
            ]);
        }

        if($this->kana)
            $query->andWhere(['like', 'CONCAT(dtb_customer.kana01,dtb_customer.kana02)', explode(' ', $this->kana)]);

        if($this->name)
            $query->andWhere(['like', 'CONCAT(dtb_customer.name01,dtb_customer.name02)', explode(' ', $this->name)]);

        return $provider;
    }
}
