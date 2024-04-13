<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchPurchase.php $
 * $Id: SearchPurchase.php 1157 2015-07-15 13:01:02Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Purchase;

/**
 * SearchCustomerAddrbook represents the model behind the search form about `common\models\CustomerAddrbook`.
 */
class SearchPurchase extends Purchase
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return parent::rules();
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
        $query = Purchase::find();

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
            'purchase_id' => $this->purchase_id,
            'customer_id' => $this->customer_id,
            'branch_id'   => $this->branch_id,
            'staff_id'    => $this->staff_id,
            'company_id'  => $this->company_id,
        ]);

        return $dataProvider;
    }
}
