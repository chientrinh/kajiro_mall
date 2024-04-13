<?php

namespace common\models\ecorange;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ecorange\OrderDetail;

/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/SearchCustomer.php $
 * @version $Id: SearchCustomer.php 1231 2015-08-05 07:47:41Z mori $
 *
 */
class SearchCustomer extends Customer
{
    /**
     * Creates data provider instance with search query applied
     *
     * @param $email
     * @param $password
     *
     * @return Customer
     */
    public function findFromEmailAndPassword($email, $password)
    {
        $models = Customer::find()->where([
            'email'   => $email,
            'del_flg' => 0,
            'shop_id' => 1,
        ])->all();

        if(! $models)
            return null;

        $security = new Security();

        foreach($models as $model)
        {
            if($model->password === $security->generatePasswordHash($password))
                return $model;
            Yii::warning($model->password, $security->generatePasswordHash($password));
        }

        return null;
    }
}
