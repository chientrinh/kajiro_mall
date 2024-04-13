<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Recipe;

/**
 * SearchRecipe represents the model behind the search form about `\common\models\Recipe`.
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRecipe.php $
 * $Id: SearchRecipe.php 3678 2017-10-18 05:37:44Z kawai $
 */
class SearchRecipe extends Recipe
{

    public $keywords;
    public $homoeopath_name;
    public $client_name;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['recipe_id', 'homoeopath_id', 'client_id', 'staff_id', 'status'], 'integer'],
            [['homoeopath_name', 'client_name', 'create_date', 'update_date', 'note'], 'safe'],
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
    public function search($params=[])
    {
        $query = Recipe::find();
//                  ->andWhere("DATE_FORMAT(now(), '%Y-%m-%d %H:%i:%s') <= DATE_FORMAT(DATE(dtb_recipe.create_date) + INTERVAL + 13 DAY, '%Y-%m-%d 23:59:59')") // 発行日から
//                  ->active();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'attributes' => [
                    'create_date',
                    'recipe_id',
                    'status',
                ],
                'defaultOrder' => ['recipe_id' => SORT_DESC]],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'recipe_id'     => $this->recipe_id,
            'homoeopath_id' => $this->homoeopath_id,
            'client_id'     => $this->client_id,
            'staff_id'      => $this->staff_id,
            'update_date'   => $this->update_date,
            'status'        => $this->status,
        ]);
        
        $query->andFilterWhere(['like', 'note',        $this->note])
              ->andFilterWhere(['like', 'create_date', $this->create_date]);

        // ホメオパシ名検索
        if ($this->homoeopath_name)
            $query->leftJoin(
                                ['h_c' => 'dtb_customer'], 
                                'h_c.customer_id = dtb_recipe.homoeopath_id'
            )
                  ->andFilterWhere(['OR',
                                   ['like', 'h_c.name01', $this->homoeopath_name],
                                   ['like', 'h_c.name02', $this->homoeopath_name],
                                   ['like', 'h_c.kana01', $this->homoeopath_name],
                                   ['like', 'h_c.kana02', $this->homoeopath_name],
            ]);    

        // クライアント名検索
        if ($this->client_name)
            $query->leftJoin(
                                ['c_c' => 'dtb_customer'], 
                                'c_c.customer_id = dtb_recipe.client_id'
            )->andFilterWhere(['OR',
                                   ['like', 'c_c.name01', $this->client_name],
                                   ['like', 'c_c.name02', $this->client_name],
                                   ['like', 'c_c.kana01', $this->client_name],
                                   ['like', 'c_c.kana02', $this->client_name],
                                   ['like', 'manual_client_name', $this->client_name],
            ]);

        return $dataProvider;
    }
}
