<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Streaming;

/**
 * StreamingSearch represents the model behind the search form about `common\models\Streaming`.
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/StreamingSearch.php $
 * $Id: StreamingSearch.php 792 2015-03-14 00:23:21Z mori $
 */

class StreamingSearch extends Streaming
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['streaming_id', 'product_id'], 'integer'],
            [['name'], 'string', 'max' => 255]
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
        $query = Streaming::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);



        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'product_id' => $this->product_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        $query->andFilterWhere(['like', 'streaming_url', $this->streaming_url]);

        $query->andFilterWhere(['like', 'post_url', $this->post_url]);

        return $dataProvider;
    }
}
