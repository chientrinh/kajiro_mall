<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\StreamingBuy;

/**
 * StreamingBuySearch represents the model behind the search form about `common\models\StreamingBuy`.
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/StreamingBuySearch.php $
 * $Id: StreamingBuySearch.php 792 2015-03-14 00:23:21Z mori $
 */

class StreamingBuySearch extends StreamingBuy
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['streaming_buy_id', 'streaming_id', 'customer_id'], 'integer'],
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
        $query = StreamingBuy::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if($this->streaming_id)
            $query->andFilterWhere([
                'streaming_id' => $this->streaming_id,
            ]);

        if($this->customer_id)
            $query->andFilterWhere([
                'customer_id' => $this->customer_id,
            ]);

        return $dataProvider;
    }
}
