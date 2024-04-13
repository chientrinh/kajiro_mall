<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Remedy;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedy.php $
 * $Id: SearchRemedy.php 1464 2015-09-04 07:18:18Z mori $
 *
 * SearchRemedy represents the model behind the search form about `\common\models\Remedy`.
 */
class SearchRemedy extends Remedy
{
    public $price;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['remedy_id','price','restrict_id'], 'integer'],
            [['abbr', 'latin', 'ja', 'concept', 'on_sale', 'advertise'], 'safe'],
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

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Remedy::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'remedy_id'  => $this->remedy_id,
            'restrict_id'=> $this->restrict_id,
        ]);

        $query->andFilterWhere(['like', 'abbr',      $this->abbr])
              ->andFilterWhere(['like', 'latin',     $this->latin])
              ->andFilterWhere(['like', 'ja',        $this->ja])
              ->andFilterWhere(['like', 'concept',   $this->concept])
              ->andFilterWhere(['like', 'advertise', $this->advertise])
              ->andFilterWhere([        'on_sale' => $this->on_sale]);

        return $dataProvider;
    }
}
