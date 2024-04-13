<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Subcategory;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchSubcategory.php $
 * $Id: SearchSubcategory.php 2113 2016-02-19 06:53:50Z mori $
 *
 * SearchSubcategory represents the model behind the search form about `\common\models\Subcategory`.
 */
class SearchSubcategory extends Subcategory
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['subcategory_id', 'company_id', 'parent_id', 'weight', 'restrict_id'], 'integer'],
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
        $query = Subcategory::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => ['defaultOrder'=>['company_id'    =>SORT_ASC,
                                         'parent_id'     =>SORT_ASC,
                                         'weight'        =>SORT_DESC,
                                         'subcategory_id'=>SORT_ASC  ]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'subcategory_id' => $this->subcategory_id,
            'company_id'     => $this->company_id,
            'parent_id'      => $this->parent_id,
            'weight'         => $this->weight,
            'restrict_id'    => $this->restrict_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
