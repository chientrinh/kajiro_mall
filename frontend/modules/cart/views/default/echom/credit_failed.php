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

$title = "クレジット決済エラー";
$this->params['breadcrumbs'][] = $title;
$this->params['body_id']       = 'Cart';
$this->title = implode(' | ',array_merge(array_reverse($this->params['breadcrumbs']),[Yii::$app->name]));


$widget = new \frontend\widgets\CartItemColumn([
  'cart_idx'=> null,
  'purchase'=> $purchase,
  'items'   => $purchase->items,
]);
$formatter = new \yii\i18n\Formatter();
?>

<div class="cart-default-thankyou">

<div class="col-md-12">


<div class="Detail-Total">
    <div class="inner">
        <h4>クレジット決済エラー</h4>
    <p>
    ※クレジットカード決済時にエラーが発生しました。
    </p>

    <p>
    伝票番号　　：<?= $purchase->purchase_id ?> <br/>
    エラーコード：<?= $result['errcode'] ?> <br/>
    </p>
<p>
    お手数ですが、伝票番号とエラーコードを添えて ec-chhom@homoeopathy.ac までお問い合わせ下さいますようお願いいたします。
</p>
<p>
 <?= Html::a('トップに戻る','https://ec.homoeopathy.ac/') ?>
 </p>
</div>
</div>
</div>

