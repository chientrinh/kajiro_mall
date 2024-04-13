<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/remedy/compose.php $
 * $Id: compose.php 3435 2017-06-21 08:40:07Z naito $
 *
 * $carts array of Cart
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

// View config
$this->params['body_id'] = 'Cart';
$this->params['breadcrumbs'][] = ['label'=>'マイページ','url'=>['/profile']];
$this->params['breadcrumbs'][] = ['label'=>'オリジナルレメディーの購入','url'=>['compose']];
$this->params['breadcrumbs'][] = ['label'=>$model->name];

?>

<div class="cart-default-index">

      <?php $form = ActiveForm::begin([
          'id'     => 'cart-remedy-compose',
          'action' => ['compose'],
          'method' => 'get',
          'fieldConfig' => [
              'template' => "{input}\n{error}",
              'horizontalCssClasses' => [
                  'offset' => 'col-sm-offset-4',
                  'error'  => '',
                  'hint'   => '',
              ],
          ],
          'validateOnBlur'  => true,
          'validateOnChange'=> true,
          'validateOnSubmit'=> true,
    ])?>

<?= \common\widgets\ComplexRemedyView::widget([
    'user'  => Yii::$app->user->identity,
    'model' => $model,
    'showPrice' => true,
]) ?>

<?php $form->end() ?>

</div>
