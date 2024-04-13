<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/eccube/SearchCustomer.php $
 * $Id: SearchCustomer.php 1117 2015-06-30 16:31:16Z mori $
 */

namespace common\models\eccube;

use Yii;

/**
 * 
 */
class SearchCustomer extends \yii\base\Model
{
    /**
     * @return a CustomerForm or false
     */
    public static function findOne($id)
    {
        return Customer::findOne($id);
    }

    /* @return a CustomerForm or false */
    public static function findFromEmailAndPassword($email,$password)
    {
        $model = Customer::find()->where([
            'email'   => $email,
            'del_flg' => 0,
        ])->one();

        if(! $model)
        {
            Yii::warning('Customer::find() did not return model');
            return null;
        }
        if(! $model->validatePassword($password))
        {
            Yii::warning('customer->validatePassword() failed');
            return null;
        }

        return $model;
    }

}

