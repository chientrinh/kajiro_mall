<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Jancode;

/**
 * SearchJancode represents the model behind the search form about `\common\models\Jancode`.
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchJancode.php $
 * $Id: SearchJancode.php 1305 2015-08-15 15:45:51Z mori $
 */
class SearchJancode extends Jancode
{
    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params=[])
    {
        $query = Jancode::find()
              ->with('product')
              ->with('stock.remedy','stock.potency','stock.vial','product')
              ->orderBy(['jan'=>SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        $query->andFilterWhere(['like', 'jan',    $this->jan])
              ->andFilterWhere(['like', 'sku_id', $this->sku_id]);

        return $dataProvider;
    }
}
