<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedySku.php $
 * $Id: SearchRemedySku.php 804 2015-03-19 07:31:58Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedySku;

/**
 * SearchRemedySku represents the model behind the search form about `common\models\RemedySku`.
 */
class SearchRemedySku extends RemedySku
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sku_id', 'series_id', 'vial_id', 'price'], 'integer'],
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
        $query = RemedySku::find();

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
            'sku_id' => $this->sku_id,
            'series_id' => $this->series_id,
            'vial_id' => $this->vial_id,
            'price' => $this->price,
            'start_date' => $this->start_date,
            'expire_date' => $this->expire_date,
        ]);

        return $dataProvider;
    }
}
