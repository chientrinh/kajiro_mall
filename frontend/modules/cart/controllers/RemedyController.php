<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/controllers/RemedyController.php $
 * $Id: RemedyController.php 3753 2017-11-16 00:43:07Z naito $
 */

namespace frontend\modules\cart\controllers;

use Yii;
use \yii\helpers\ArrayHelper;
use \common\models\CustomerGrade;
use \common\models\Remedy;
use \common\models\RemedyPotency;
use \common\models\RemedyPriceRange;
use \common\models\RemedyStock;
use \common\models\RemedyVial;
use \common\components\cart\ComplexRemedyForm;

class RemedyController extends \yii\web\Controller implements \yii\base\ViewContextInterface
{ 

    public $defaultAction = 'index';
    private $_backUrl;

    public function init()
    {
        parent::init();

        $this->_backUrl = Yii::$app->request->referrer
                        ? Yii::$app->request->referrer
                        : \yii\helpers\Url::toRoute(sprintf('/%s', $this->module->id));
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'actions'=> ['add'],
                        'allow'  => true,
                        'verbs'  => ['GET','POST'],
                    ],
                    [
                        'actions'=> ['compose','machine'],
                        'allow'  => true,
                        'roles'  => ['@'],
                        'matchCallback' => function()
                        {
                            $user = Yii::$app->user->identity;

                            // ホメオパス、た)プレミアム会員以上、HJ代理店、CHhom本科学生のみ許可する
                            return ($user &&
                               ($user->isHomoeopath() ||
                                (CustomerGrade::PKEY_TA <= ArrayHelper::getValue($user, 'grade.grade_id')) ||
                                $user->isAgencyOf(\common\models\Company::PKEY_HJ) ||
                                $user->isMemberOf([\common\models\Membership::PKEY_STUDENT_INTEGRATE,
                                                   \common\models\Membership::PKEY_STUDENT_TECH_COMMUTE,
                                                   \common\models\Membership::PKEY_STUDENT_TECH_ELECTRIC])
                               ));
                        }
                    ],
                ],
            ],
        ];
    }

    public function actionAdd($rid, $pid, $vid, $qty=1)
    {
        $qty = (int) trim(mb_convert_kana($qty, 'ns'));
        if($qty <= 0)
            throw new \yii\base\UserException("追加数量($qty)が不正です");

        // ここで滴下からprange_id（価格帯）を取得しておく
        $drop = \common\models\RemedyStock::find()->andWhere(['remedy_id' => $rid,
                                                               'potency_id'=> $pid,
                                                               'vial_id'   => RemedyVial::DROP ])->one();
        
        // RemedyStockにない商品（白ラベル）であっても通るようにif文カット、addRemedyへ引き渡す
            $this->module->addRemedy($rid, $pid, $vid, $qty, $drop ? $drop->prange_id : null);

        if(Yii::$app->request->isAjax &&
           Yii::$app->session->removeFlash('success'))
               return $qty; // ok

// カートだけか？？
        $url = $this->_backUrl.'#'.\common\models\RemedyPotency::find()->where(['potency_id' => $pid])->one()->name;
        $this->redirect($url);
    }

    /**
     * @brief コンビネーションレメディー１点を組み立て、カートに追加することができる
     * @see /recipe/create/compose
     */
    public function actionCompose()
    {
        $model = new \common\components\cart\ComplexRemedyForm([
            'scenario'     => 'prescribe',
            'maxDropLimit' => 5,
        ]);

        if(Yii::$app->request->get())
        {

            $params = Yii::$app->request->get();

            $model->load($params);
            $model->validate();
        }

        if('extend' == Yii::$app->request->get('command', null))
            $model->extend();

        if('shrink' == Yii::$app->request->get('command', null))
            $model->shrink();

        if(('finish' == Yii::$app->request->get('command', null)) && ! $model->hasErrors())
            if($model->validate() && $this->module->appendCartItem($model))
            {
                return $this->redirect(['compose']); // trim off get params, renew the screen
            }

        return $this->render('compose', ['model'=> $model]);
    }

    /**
     * @brief 特別レメディー（レメディーマシンで作る）１点を組み立て、カートに追加することができる
     * @see /recipe/create/machine
     */
    public function actionMachine()
    {
        $model = new \common\models\MachineRemedyForm();

        if($model->load(Yii::$app->request->post()) &&
           $model->validate() &&
           $this->module->addProduct($model->product_id, ['name' => $model->name, 'qty'=> 1 ]))
        {
            return $this->redirect(['machine']);
        }

        return $this->render('machine', ['model'=>$model]);
    }

    public function actionDel($rid, $pid, $vid)
    {
        $this->module->delRemedy($rid, $pid, $vid);

        $this->redirect($this->_backUrl);
    }

    /**
     * @return ComplexRemedyForm
     */
    private function createModel($rid, $pid, $vid, $qty)
    {
        $remedy = Remedy::findOne($rid);
        $potency= RemedyPotency::findOne($pid);
        $vial   = RemedyVial::findOne($vid);

        if(! $remedy || ! $potency || ! $vial)
            throw new \yii\web\ForbiddenHttpException('申し訳ありませんが、ご指定の商品は見つかりませんでした');

        return new ComplexRemedyForm([
            'vial' => new RemedyStock(['remedy_id'  => 0,
                                       'potency_id' => null,
                                       'vial_id'    => $vid,
                                       'prange_id'  => RemedyPriceRange::PKEY_COMPOSE_BASE,
            ]),
            'drops' => [new RemedyStock(['remedy_id' => $rid,
                                         'potency_id'=> $pid,
                                         'vial_id'   => RemedyVial::DROP,
            ])],
            'qty'   => $qty,
        ]);
    }

}
