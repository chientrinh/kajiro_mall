<?php

namespace common\models\ecorange;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ecorange\OrderDetail;

/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/SearchOrderDetail.php $
 * @version $Id: SearchOrderDetail.php 1986 2016-01-16 04:35:18Z mori $
 *
 */
class SearchOrderDetail extends OrderDetail
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(),[
        ]);
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
        $query = OrderDetail::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        $query->from('dtb_order_detail d')
            ->orderBy('d.order_id DESC')
            ->andFilterWhere([
            'd.shop_id'       => $this->shop_id,
            'd.product_id'    => $this->product_id,
            'd.price'         => $this->price,
            'd.quantity'      => $this->quantity,
            'd.discount_rate' => $this->discount_rate,
            'd.discount_price'=> $this->discount_price,
            'd.point_rate'    => $this->point_rate,
            ]);
        $query->andFilterWhere(['like', 'd.product_code', $this->product_code]);
        $query->andFilterWhere(['like', 'd.product_name', $this->product_name]);

        if($this->create_date)
            $query->innerJoinWith(['order'=>function ($q) {
                $q->where('dtb_order.create_date LIKE :date',[':date'=>$this->create_date.'%']);
            }]);

        return $dataProvider;
    }
}
