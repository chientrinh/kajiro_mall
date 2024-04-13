<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/controllers/RecipeController.php $
 * $Id: RecipeController.php 4177 2019-08-26 04:38:43Z mori $
 */

namespace frontend\modules\cart\controllers;

use Yii;
use \common\models\Company;
use \common\models\Membership;
use \common\models\Recipe;

class RecipeController extends \yii\web\Controller implements \yii\base\ViewContextInterface
{ 

    public $defaultAction = 'add';
    private $_backUrl;
    private $_itemFinder;


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
                        'actions'=> ['proxy','batch'],
                        'allow'  => false,
                        'roles'  => ['?'], // deny guest users
                    ],
                    [
                        'allow'  => true, // allow all others
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();

        $this->_backUrl = Yii::$app->request->referrer
                        ? Yii::$app->request->referrer
                        : \yii\helpers\Url::toRoute(sprintf('/%s', $this->module->id));
    }

    public function actionProxy($id)
    {
        $recipe = $this->findModel($id, null);

        if($recipe->homoeopath_id != Yii::$app->user->id)
            throw new \yii\web\HttpForbiddenException("自分が発行したNOのみ指定可能です");

        $cartManager = $this->module->cart;
        $cartManager->createRecipeCart($id);

        return $this->redirect(['/cart/index','cart_idx'=> $cartManager::DEFAULT_CART_IDX]);
    }

    /**
     * find recipe from own system, then add items to the cart
     */
    public function actionAdd($id, $pw)
    {
        $recipe = $this->findModel($id, $pw);

        $this->addItems($recipe);

        return $this->redirect(['/cart/index']);
    }

    /**
     * find recipe from own system, then add items to the cart
     */
    public function actionBatch()
    {
        $user = Yii::$app->user->identity;
        if(! $user->isMemberOf([Membership::PKEY_AGENCY_HJ_A ,
                                Membership::PKEY_AGENCY_HJ_B])
        )
            throw new \yii\web\ForbiddenHttpException('許可がありません');

        $model = new SimpleForm();

        if($model->load(Yii::$app->request->post()) && $model->validate())
        {
            $cartManager = $this->module->cart;
            $cart = $cartManager->getCart($cartManager::DEFAULT_CART_IDX);

            foreach($model->ids as $id)
            {
                if(in_array($id, $cart->recipes))
                    $model->addError('recipe_id', "適用書 $id は追加済みです");
            }

            if(! $model->hasErrors())
            {
                foreach($model->ids as $id)
                    $this->addItems(Recipe::findOne($id));

                return $this->redirect(['batch']);
            }
        }

        return $this->render('batch',['model'=>$model]);
    }

    private function addItems(\common\models\Recipe $recipe)
    {
        $cartManager = $this->module->cart;
        $cart = $cartManager->getCart($cartManager::DEFAULT_CART_IDX);
        if(in_array($recipe->recipe_id, $cart->recipes))
        {
            Yii::$app->session->addFlash('error', "適用書 {$recipe->recipe_id} は追加済みです");
            return false;
        }

        $ret = [];
        $items = $recipe->parentItems;
        foreach($items as $item)
        {
            $ret[] = $this->addItem($item);
        }
        $this->module->cart->setRecipe($recipe->recipe_id);

        return (false === array_search(false, $ret)); // return true when every attempt was success
    }
    
    private function addItem(\common\models\RecipeItem $m)
    {
        $recipe_id = $m->recipe_id;
        if(0 < $m->product_id)
            return $this->module->addProduct($m->product_id, ['qty' => $m->quantity, 'name'=>$m->name, 'recipe_id' => $recipe_id]);

        if(! $m->children) {
            $prange_id = null;
            $products = $m->remedy->getProducts();
            if($products) {
                foreach($products as $item) {
                    if($item->remedy_id == $m->remedy_id AND $item->potency_id == $m->potency_id AND $item->vial_id == $m->vial_id) {
                        $prange_id = $item->prange_id;
                        break;
                    }
                }
            }
            return $this->module->addRemedy($m->remedy_id, $m->potency_id, $m->vial_id, $m->quantity, $prange_id, $recipe_id);
        }

        $model = \common\components\cart\ComplexRemedyForm::convertFromRecipeItem($m);
        if(is_array($model)) {
            foreach($model as $m) {
                $m->recipe_id = $recipe_id;
                $this->module->appendCartItem($m);
            }
        }
        elseif($model) {
            $model->recipe_id = $recipe_id;
            $this->module->appendCartItem($model);
        }
        
        if(! $model)
        {
            Yii::error(['failed conversion',
                        'recipe_id'  => $m->recipe_id,
                        'seq'        => $m->seq,
                        'name'       => $m->name,
            ],self::className().'::'.__FUNCTION__);
            return false;
        }
        return true;
    }

    /**
     * get recipe from own system
     */
    private static function findModel($id, $pw)
    {
        $model = Recipe::find()->andFilterWhere([
            'recipe_id' => $id,
            'pw'        => $pw,
        ])->one();

        if(! $model)
            throw new \yii\web\NotFoundHttpException("当該のNOまたはパスワードが一致しません");

        if((null === $pw) && ($model->homoeopath_id != Yii::$app->user->id))
            throw new \yii\web\ForbiddenHttpException("自分が発行したNOのみ指定可能です");

        if($model->isSold())
            throw new \yii\web\NotFoundHttpException("当該の適用書は購入済みのため、もう一度購入することはできません");

        if($model->isExpired())
            throw new \yii\web\NotFoundHttpException("当該の適用書は有効期限が過ぎています");

        /*
        if($model->client_id && ($model->client_id != Yii::$app->user->id))
            throw new \yii\web\NotFoundHttpException("当該の適用書はご本人のみ注文できます");
        */

        return $model;
    }

}
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/controllers/RecipeController.php $
 * $Id: RecipeController.php 4177 2019-08-26 04:38:43Z mori $
 *
 * TextAreaにて適用書NOを複数入力するためのフォーム、ActionBatch() でのみ使用する
 */
class SimpleForm extends \yii\base\Model
{
    public $recipe_id;

    public function rules()
    {
        return [
            ['recipe_id', 'required'],
            ['recipe_id', 'string', 'max' => 1024],
            ['recipe_id', 'filter', 'filter'=>function($value){ return self::sanitize($value); } ],
            ['recipe_id', 'isNumeric'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'recipe_id' => '適用書NO',
        ];
    }

    public function getIds()
    {
        $values = explode(' ', $this->recipe_id);
        $values = array_unique($values);

        return $values;
    }

    public function isNumeric($attr, $param)
    {
        $values = explode(' ', $this->recipe_id);

        foreach($values as $v)
        {
            if(! is_numeric($v))
                $this->addError($attr, "$v は整数ではありません");
        }

        foreach($values as $v)
        {
            if($this->hasErrors($attr))
                break;

            if(Recipe::find()->max('recipe_id') < $v)
                $this->addError($attr, "適用書NO $v はパスワードなし一括購入には対応していません");

            $q = Recipe::find()->where(['recipe_id' => $v]);

            $canSellStatus = [Recipe::STATUS_INIT, Recipe::STATUS_PREINIT];

            if(! $q->exists())
                $this->addError($attr, "適用書NO $v は存在しません");

            elseif(! $q->andWhere(['in', 'status', $canSellStatus])->exists())
                $this->addError($attr, "適用書NO $v は購入済みまたは無効です");
        }

        return $this->hasErrors($attr);
    }

    public function sanitize($value)
    {
        $value = mb_convert_kana($value, 'as');
        $value = strtr($value, ["\n" => ' ',"\r\n"=>' ',"\r"=>' ',"\t"=>' ']);
        $value = preg_replace('/ +/',' ',$value);
        $value = trim($value);

        return $value;
    }

}
