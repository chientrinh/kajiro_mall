<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Zip;

/**
 * ZipSearch represents the model behind the search form about `common\models\Zip`.
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ZipSearch.php $
 * $Id: ZipSearch.php 2671 2016-07-08 02:19:54Z mori $
 */

class ZipSearch extends Zip
{
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
        $query = Zip::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        $query->andFilterWhere([
            'region' => $this->region,
            'pref_id' => $this->pref_id,
        ]);

        $query->andFilterWhere(['like', 'zipcode', $this->zipcode])
              ->andFilterWhere(['like', 'city', $this->city])
              ->andFilterWhere(['like', 'town', $this->town])
              ->andFilterWhere(['sagawa_22' => $this->sagawa_22])
              ->andFilterWhere(['yamato_22' => $this->yamato_22])
              ->andFilterWhere(['spat'      => $this->spat     ]);

        return $dataProvider;
    }
}
