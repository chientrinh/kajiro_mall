<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyStock;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedyStock.php $
 * $Id: SearchRemedyStock.php 3497 2017-07-20 10:07:25Z kawai $
 *
 * SearchRemedyStock represents the model behind the search form about `common\models\RemedyStock`.
 */
class SearchRemedyStock extends RemedyStock
{
    public $keywords;
    public $remedy;
    public $remedy_name;
    public $potencies;
    public $vials;
    public $on_sale;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['remedy_id', 'potency_id', 'prange_id', 'vial_id', 'restrict_id', 'in_stock','on_sale'], 'integer'],
            ['potencies', 'exist', 'targetClass'=>RemedyPotency::className(),'targetAttribute'=>'potency_id','allowArray'=>true],
            ['vials', 'exist', 'targetClass'=>RemedyVial::className(),'targetAttribute'=>'vial_id','allowArray'=>true],
            ['keywords','string'],
            ['keywords','filter', 'filter'=> function($value) { return \common\components\Romaji2Kana::translate($value,'katakana'); }, 'skipOnEmpty'=>true ],
            [['remedy','keywords', 'remedy_name'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function getProductMaster() {
        return $this->hasOne(ProductMaster::className(),[
            'remedy_id' => 'remedy_id',
            'potency_id'=> 'potency_id',
            'vial_id'   => 'vial_id',
        ]);
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = RemedyStock::find()->with('remedy');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            //uncomment the following line if you do not want to any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'remedy_id'  => $this->remedy_id,
            'potency_id' => $this->potency_id,
            'prange_id'  => $this->prange_id,
            'vial_id'    => $this->vial_id,
            'in_stock'   => $this->in_stock,
            'mtb_remedy_stock.restrict_id'=> $this->restrict_id,
        ])
        ->joinWith(['remedy' => function(\yii\db\ActiveQuery $query){
            $query->orderBy('abbr');
        }]);

        if($this->remedy)
            $query->andFilterWhere(['like', 'mtb_remedy.abbr', $this->remedy]);

        // latin , ja どちらでも検索できるように
        if($this->remedy_name)
            $query->andFilterWhere(['like', 'mtb_remedy.ja', $this->remedy_name]);
        
        if($this->potencies)
            $query->andWhere(['potency_id'=>$this->potencies]);

        if($this->vials)
            $query->andWhere(['vial_id'=>$this->vials]);

        if(isset($this->on_sale))
            $query->andWhere(['like','mtb_remedy.on_sale',$this->on_sale]);

        if($this->keywords)
            foreach(explode(' ', $this->keywords) as $item)
            {
                $query->andFilterWhere([
                    'or',
                    ['like', 'mtb_remedy.abbr',     $item],
                    ['like', 'mtb_remedy.latin',    $item],
                    ['like', 'mtb_remedy.ja',       $item],
                    ['like', 'mtb_remedy.advertise',$item],
                ]);
            }

        return $dataProvider;
    }
}
