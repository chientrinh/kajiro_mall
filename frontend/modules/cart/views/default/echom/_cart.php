<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_cart.php $
 * $Id: _cart.php 4184 2019-09-18 06:09:01Z mori $
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\models\Company;
use common\models\Payment;
use common\models\CustomerGrade;

$cart_idx = $key;

$formatter = new \yii\i18n\Formatter();

$out = [];
foreach($model->items as $item)
{
    if(($m = $item->model) && $m instanceof \yii\db\ActiveRecord)
        if($m->hasAttribute('in_stock') && ! $m->in_stock)
            $out[] = $item->name;
}

$jscode = "
chk1 = $('#out-of-stock-accept');
chk2 = $('#i-am-adult');
if(
  ((0 < chk1.length) && ! chk1.is(':checked')) ||
  ((0 < chk2.length) && !(20 <= chk2.val()) )
)
    $('#cart-finish').attr('disabled',true);

$('#out-of-stock-accept').click(function()
{
  chk2 = $('#i-am-adult');

  if(! $(this).is(':checked'))
    $('#cart-finish').attr('disabled',true);

  else if(0 == chk2.length || (20 <= chk2.val()))
      $('#cart-finish').removeAttr('disabled');
});

$('#i-am-adult').change(function()
{
  chk1 = $('#out-of-stock-accept');

  if(! (20 <= $(this).val()))
    $('#cart-finish').attr('disabled',true);

  else if(0 == chk1.length || chk1.is(':checked'))
    $('#cart-finish').removeAttr('disabled');
});

$('#cart-finish').click(function()
{
  $(this).attr('disabled',true);
});


";
$this->registerJs($jscode);

?>

<div class="cart-<?= $key ?>">

    <div class="col-md-8">
        <div class="row">

            <?php if($out): ?>
            <div class="col-md-12" style="margin-bottom:10px">
                <div class="well-sm alert-danger" id="out-of-stock-accept">
                申し訳ありませんが以下の商品はただいま在庫がありません。<br>
                カートから削除してください。<br><br>
                <?= Html::ul($out,['style'=>'list-style-type: square','class'=>'strong']) ?>
            </div>
            </div>
            <?php endif ?>



            <div class="col-md-12">
                <?php echo $this->render('_items', ['cart_idx' => $key, 'model' => $model, 'editable'=>true]) ?>
            </div>

            <p class="text-left">
                <?= Html::a('お買い物を続ける','/',['class'=>'btn btn-warning']) ?>
            </p>


        </div>
    </div>

    <div class="col-md-4">
        <div class="Detail-Total">
            <div class="inner">

                <?= \yii\widgets\DetailView::widget([
                    'model' => $model->purchase,
                    'template' => '<tr><th>{label}</th><td class="text-right">{value}</td></tr>',
                    'attributes' => [
                        [
                            'attribute' => 'taxedSubtotal',
                            'format'    => 'currency',
                            'label'     => '商品計（税込）',
                        ],
                        [
                            'attribute'=> 'total_charge',
                            'format'   => 'raw',
                            'value'    => Html::tag('span', $formatter->asCurrency($model->purchase->total_charge),['class'=>'Total']),
                        ],
                    ],
                ]);?>

                <?php if($model->hasErrors()):
                    if(isset($model->purchase->errors["items"]) && in_array("商品がありません",$model->purchase->errors["items"])) {
//                        echo '<p class="alert alert-info">商品なし</p>';
                        for ($i=0; $i < count($model->purchase->errors["items"]); $i++) {
                            if($model->purchase->errors["items"][$i]!="商品がありません"){
                                echo '<p class="alert alert-danger">'.$model->purchase->errors["items"][$i].'</p>';   
                            }
                        }

                    } else {
                        echo '<small>'.
                            Html::errorSummary($model,['class'=>'error-summary']);
                        echo '</small>';
                    }
                ?>

                <?php else: 
                    // カートにエラーが検出されず、
                    // カート内に商品が1種類以上入っていればボタン表示
                    if(0 < count($model->items)) {
                        echo '<p class="text-center">
                            <span class="detail-view-btn">';
                    ?>
                     <?=           Html::a("注文を確定する", ['finish', 'cart_idx' => $cart_idx], [
                                    'id'    => 'cart-finish',
                                    'class' => 'btn btn-danger',
                                    'data' => [
                                        'method' => 'post',
                                    ],
                                ]); ?>
                    <?php
                        echo'    </span>
                        </p>';
                    }
                    
                endif ?>

                <hr>
                <h5>連絡先<?= Yii::$app->user->isGuest == true ? Html::a("変更",['update','target'=>'guest-signup','cart_idx'=>$cart_idx],['id'=>'btn-update-addr','class'=>$model->delivery->hasErrors()?'btn btn-danger':'btn btn-default','disabled' => $model->purchase->agent_id ? true : false]) : '' ?></h5>
                <?php (Payment::PKEY_DROP_SHIPPING === $model->purchase->payment_id) ? $class='alert-info' : $class = '' ?>
                <p class="<?= $class ?>">
                    <?= $model->customer->email ?><br>
                    <?= $model->delivery->name ?> 様
                </p>

                <?php (Payment::PKEY_DROP_SHIPPING === $model->purchase->payment_id) ? $class='alert-info' : $class = '' ?>
                <h5>ご購入方法
                    <?php if(1 < count($model->payments)): ?>
                    <?= Html::a("変更", ['update', 'target' => 'payment', 'cart_idx' => $cart_idx], ['class' => 'btn btn-default', 'id' => 'btn-update-payment']) ?>
                    <?php endif ?>
                </h5>
                <p id="purchase-paynent-name" class="<?= $class ?>"><?= $model->purchase->payment->name ?></p>

                    <hr>
                    <?php $form = \yii\bootstrap\ActiveForm::begin([
                        'action' => ['update','cart_idx'=>$cart_idx,'target'=>'msg'],
                        'method' => 'post',
                        'fieldConfig' => [
                            'template' => '{input}{error}',
                        ],
                    ]) ?>
                    <h5>
                        <?= $model->purchase->getAttributeLabel('customer_msg') ?>　<span style="color:red">（入力後、必ず「保存」ボタンをクリックしてください）</span>
                        <?= Html::submitButton('保存',['name' => 'submit_msg','class'=>'btn btn-default']) ?>
                    </h5>
                    <p>
                        <?= $form->field($model->purchase,'customer_msg')->textArea(['id'=>'customer_msg','name'=>'customer_msg','placeholder'=>$model->purchase->getAttributeHint('customer_msg'),'style'=>'outline:none;']) ?>
                        <?php $form->end() ?>
                    </p>

            </div>
        </div>

    </div><!-- col-md-4 -->

</div>
