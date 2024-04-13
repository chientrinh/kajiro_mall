<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedyCategoryDescription.php $
 * $Id: SearchRemedyPotency.php 804 2015-03-19 07:31:58Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyPotency;
use common\models\RemedyCategoryDescription;

/**
 * SearchRemedyCategoryDescription represents the model behind the search form about `common\models\RemedyCategoryDescription`.
 */
class SearchRemedyCategoryDescription extends RemedyCategoryDescription
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

            [
                [
                 'remedy_category_id',
                 'title', 'body',
                 'desc_division',
                 'is_display'
                ],
                'safe'],
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
        $query = RemedyCategoryDescription::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        $query->andFilterWhere(['=', 'remedy_category_id', $this->remedy_category_id]);
        $query->andFilterWhere(['like', 'title', $this->title]);
        $query->andFilterWhere(['like', 'body', $this->body]);
        $query->andFilterWhere(['=', 'is_display', $this->is_display]);
        $query->andFilterWhere(['=', 'desc_division', $this->desc_division]);

        return $dataProvider;
    }
}
