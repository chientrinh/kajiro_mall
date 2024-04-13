<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/thankyou.php $
 * $Id: thankyou.php 2625 2016-06-26 01:39:06Z mori $
 *
 * $cart_idx integer
 * $model    Cart model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$title = "ご注文ありがとうございます";
$this->params['breadcrumbs'][] = $title;
$this->params['body_id']       = 'Cart';
$this->title = implode(' | ',array_merge(array_reverse($this->params['breadcrumbs']),[Yii::$app->name]));


$formatter = new \yii\i18n\Formatter();
?>

<div class="cart-default-thankyou">
  <h1 class="mainTitle">ご注文ありがとうございます</h1>
  <p class="mainLead">
  <?php if(Yii::$app->user->isGuest): ?>
  「注文番号<?= $purchase_id ?>」にて承りました。
  <?php else: ?>
    「<?= sprintf("注文番号 %s", $purchase_id) ?>」にて承りました。
  <?php endif ?>
  <br>
  またのご利用を心よりお待ちしております。
  <?= Html::a("買いものを続ける",['index'],['class'=>'btn btn-success pull-right']) ?>
</p>

<div class="col-md-12">
    <h4><span><?= $company_name ?></span></h4>

    <p>合計 <?= $model->itemCount ?> 点</p>

    <p><!-- error message -->
<?php if($model->errors) echo json_encode($model->errors); ?>
    </p>
</div><!-- col-md-12 -->

<div class="col-md-8">

<?= $this->render('_items', ['cart_idx' => $cart_idx, 'model'=>$model, 'editable'=>false]) ?>

</div><!-- col-md-8 -->

<div class="col-md-4">
  <div class="Detail-Total">
    <div class="inner">

<?= \yii\widgets\DetailView::widget([
    'model' => $model->purchase,
        'template' => '<tr><th>{label}</th><td class="text-right">{value}</td></tr>',
    'attributes' => [
        'taxedSubtotal:currency',
        'postage:currency',
        'handling:currency',
        [
            'attribute'=> 'total_charge',
            'format'   => 'raw',
            'value'    => Html::tag('span', $formatter->asCurrency($model->purchase->total_charge),['class'=>'Total']),
        ],
    ],
]);?>
    </div>
  </div>
    <hr>
    <h5>お支払方法</h5>
    <p><?= $model->payment->name ?></p>

</div><!-- col-md-4 -->

</div>
