<?php
namespace frontend\modules\cart\controllers;
use Yii;

/**
 * Retail controller
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/controllers/GuestController.php $
 * $Id: GuestController.php 1927 2015-12-27 03:07:00Z mori $
 */
class GuestController extends \yii\web\Controller
{
    public $defaultAction = 'signup';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'actions'=> ['signup'],
                        'allow'  => true,
                        'roles'  => ['?'], // guest only
                    ],
                    [
                        'allow'  => false, // everything else is denied
                    ],
                ],
            ],
        ];
    }

    // public function beforeAction($action)
    // {
    //     if($email = Yii::$app->session->get('email'))
    //     {
            
    //     }
    // }

    public function actionSignup()
    {
        if($email = \yii\helpers\ArrayHelper::getValue(Yii::$app->request->bodyParams, 'email'))
            $this->module->customer->email = $email;

        $cart_idx = 0;
        $model    = new \common\models\AddrbookForm();
        $model->load($this->module->cart->getCart($cart_idx)->delivery->attributes, '');
        $model->load(Yii::$app->request->bodyParams);

        foreach($this->module->cart->carts as $k => $v)
            $this->module->updateAddr($k, $model);

        if( Yii::$app->request->isPost &&
            ('zip2addr' != $model->scenario) &&
            $model->validate() &&
            $this->validateCustomer()
        )
            return $this->redirect(['/cart/default/index']);

        return $this->render('signup', [
            'customer' => $this->module->customer,
            'model'    => $model,
            'agreed'   => Yii::$app->request->isPost,
        ]);
    }

    private function validateCustomer()
    {
        $c = $this->module->customer;
        $c->clearErrors('email');
        $c->validate('email', false);

        // equivalent of `detach ExistValidator from email`
        if($c->getErrors('email') == [ 0 => '入力されたメールアドレスはすでに登録されています。' ])
           $c->clearErrors('email');

        Yii::$app->session->set('email', $c->email);

        if($this->module->customer->hasErrors('email'))
           return false;

        return true;
    }
}
