<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchPotency.php $
 * $Id: SearchPotency.php 804 2015-03-19 07:31:58Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyPotency;

/**
 * SearchPotency represents the model behind the search form about `common\models\RemedyPotency`.
 */
class SearchPotency extends RemedyPotency
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['potency_id', 'weight'], 'integer'],
            [['name'], 'safe'],
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
        $query = RemedyPotency::find();

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
            'potency_id' => $this->potency_id,
            'weight' => $this->weight,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
