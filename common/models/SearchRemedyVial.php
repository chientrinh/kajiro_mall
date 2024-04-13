<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedyVial.php $
 * $Id: SearchRemedyVial.php 804 2015-03-19 07:31:58Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyVial;

/**
 * SearchRemedyVial represents the model behind the search form about `common\models\RemedyVial`.
 */
class SearchRemedyVial extends RemedyVial
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['vial_id', 'unit_id', 'volume'], 'integer'],
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
        $query = RemedyVial::find();

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
            'vial_id' => $this->vial_id,
            'unit_id' => $this->unit_id,
            'volume' => $this->volume,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
