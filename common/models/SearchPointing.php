<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchPointing.php $
 * $Id: SearchPointing.php 1272 2015-08-09 05:33:50Z mori $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Purchase;

/**
 * SearchCustomerAddrbook represents the model behind the search form about `common\models\CustomerAddrbook`.
 */
class SearchPointing extends Pointing
{
    public $company; // integer

    public function init()
    {
        $this->unsetAttributes();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['company', 'validateCompanyModel'];

        return $rules;
    }

    public function validateCompanyModel($attr, $params)
    {
        if($this->company instanceof Company)
            return true;

        return false;
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
    public function search($params = [])
    {
        $query = Pointing::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if($this->company)
            $query->andWhere(['company_id' => $this->company->company_id]);

        $query->andFilterWhere([
            'pointing_id' => $this->pointing_id,
            'seller_id'   => $this->seller_id,
            'customer_id' => $this->customer_id,
            'create_date' => $this->create_date,
            'update_date' => $this->update_date,
            'status'      => $this->status,
            'note'        => $this->note,
            'total_charge'=> $this->total_charge,
            'subtotal'    => $this->subtotal,
            'tax'         => $this->tax,
        ]);

        return $dataProvider;
    }

    private function unsetAttributes($names=null)
    {
        if($names===null)
            $names=$this->attributes();

        foreach($names as $name)
            $this->$name=null;
    }
}
