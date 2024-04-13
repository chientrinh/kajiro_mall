<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyPriceRangeItem;

/**
 * SearchRemedyPriceRangeItem represents the model behind the search form about `common\models\RemedyPriceRangeItem`.
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedyPriceRangeItem.php $
 * $Id: SearchRemedyPriceRangeItem.php 804 2015-03-19 07:31:58Z mori $
 */
class SearchRemedyPriceRangeItem extends RemedyPriceRangeItem
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['prange_id', 'vial_id', 'price'], 'integer'],
            [['start_date', 'expire_date'], 'safe'],
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
        $query = RemedyPriceRangeItem::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1'); // do not return any records when validation fails
            return $dataProvider;
        }

        $query->andFilterWhere([
            'prange_id'   => $this->prange_id,
            'vial_id'     => $this->vial_id,
            'price'       => $this->price,
            'start_date'  => $this->start_date,
            'expire_date' => $this->expire_date,
        ]);

        return $dataProvider;
    }
}
