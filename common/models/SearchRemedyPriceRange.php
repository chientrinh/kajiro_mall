<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedyPriceRange.php $
 * $Id: SearchRemedyPriceRange.php 804 2015-03-19 07:31:58Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyPriceRange;

/**
 * SearchRemedyPriceRange represents the model behind the search form about `common\models\RemedyPriceRange`.
 */
class SearchRemedyPriceRange extends RemedyPriceRange
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['prange_id'], 'integer'],
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
        $query = RemedyPriceRange::find();

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
            'prange_id' => $this->prange_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
