<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_cart.php $
 * $Id: _cart.php 4246 2020-03-23 05:39:59Z mori $
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\models\Company;
use common\models\Payment;
use common\models\CustomerGrade;
use common\models\Product;

$cart_idx = $key;

$formatter = new \yii\i18n\Formatter();

$company = Company::findOne($key);
if($company)
    $company_name = $company->name;
else
    $company_name = implode('<br>',  Company::find()->where(['company_id' => [Company::PKEY_HE, Company::PKEY_HJ]])->select('name')->column());

$out = [];

// TODO: 能登地震災害支援対応
$showSupportMessage = false;

foreach($model->items as $item)
{
    // TODO: 能登地震災害支援対応
    if(($m = $item->model) && $m instanceof \common\models\Product) {
        if($m->product_id == Product::PKEY_NOTO_SUPPORT) {
            $showSupportMessage = true;
        }
    }

    if(($m = $item->model) && $m instanceof \yii\db\ActiveRecord)
        if($m->hasAttribute('in_stock') && ! $m->in_stock)
            $out[] = $item->name;
}

$model->purchase->direct_code = $model->delivery->code;

$direct_customers = [];

if(Yii::$app->user->identity) {
    $direct_customers = \common\models\CustomerAddrbook::find()
            ->andWhere(['customer_id' => Yii::$app->user->identity->customer_id])
            ->andWhere(['not', ['code' => null]])
            ->andWhere(['>', 'LENGTH(code)', 0])
            ->select(['id','code','name01','name02'])
            ->all();

    $direct_customers = \yii\helpers\ArrayHelper::map($direct_customers, 'code', function($element) {
        return $element['code'].":".$element['name01'].$element['name02'];
    });
}


// 現在日から予約締切日（月末）、発送予定日（翌月第３水曜）を求める
$now_date = date('Y-m-d');

$pre_order_deliv = date("Y-m-d",strtotime(date("Y-m-d",strtotime($now_date))." third wednesday of next month"));
$pre_order_deliv_date = Yii::$app->formatter->asDate($pre_order_deliv, 'php:Y年m月d日(D)');

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
  if($('#purchaseform-campaign_code').length && 0 < $('#purchaseform-campaign_code').val().length && $('button[name=submit_campaign]').text() == '登録') {
      alert('キャンペーンコードを入力されていますが、登録ボタンを押し忘れると適用されません。登録ボタンを押してください');
      return false;
  }
  $(this).attr('disabled',true);
});

$('#purchaseform-campaign_code').change(function()
{
    if(0 == $(this).val().length && $('button[name=submit_campaign]').text() == '登録') {
        $('#cart-finish').removeAttr('disabled');
    }
});


$('#input-point-toggle').click(function(){

    $(this).hide();
    $('#input-point-still').hide();
    $('#input-point-dynamic').show();
    $('#input-point-cancel-toggle').show();

    return false;
});

$('#input-point-cancel-toggle').click(function(){

    $(this).hide();
    $('#input-point-still').show();
    $('#input-point-dynamic').hide();
    $('#input-point-toggle').show();

    return false;
});


$('#input-campaign-toggle').click(function(){

    $(this).hide();
    $('#input-campaign-still').hide();
    $('#input-campaign-dynamic').show();
    $('#input-campaign-cancel-toggle').show();

    return false;
});

$('#input-campaign-cancel-toggle').click(function(){

    $(this).hide();
    $('#input-campaign-still').show();
    $('#input-campaign-dynamic').hide();
    $('#input-campaign-toggle').show();

    return false;
});

$('#input-agent-toggle').click(function(){

    $(this).hide();
    $('#input-agent-still').hide();
    $('#input-agent-dynamic').show();
    $('#input-agent-cancel-toggle').show();

    return false;
});

$('#input-agent-cancel-toggle').click(function(){

    $(this).hide();
    $('#input-agent-still').show();
    $('#input-agent-dynamic').hide();
    $('#input-agent-toggle').show();

    return false;
});


";
$this->registerJs($jscode);

?>

<div class="cart-<?= $key ?>">

    <div class="col-md-8">
        <div class="row">
            <?php
            if(0 < count($model->items)) {
                echo '
                <div class="col-md-12">
                    <h4><span>'. $company_name .'</span></h4>
                    <p><!-- error message -->
                    </p>
                </div>';
            }?>

            <?php if($out): ?>
            <div class="col-md-12" style="margin-bottom:10px">
                <div class="well-sm alert-danger" id="out-of-stock-accept">
                申し訳ありませんが以下の商品はただいま在庫がありません。<br>
                カートから削除してください。<br><br>
                <?= Html::ul($out,['style'=>'list-style-type: square','class'=>'strong']) ?>
            </div>
            </div>
            <?php endif ?>

            <?php
                if($model->purchase->campaign) {
                    if($cart_idx != Company::PKEY_TROSE) {

                    echo '<div class="col-md-12" style="margin-bottom:10px">
                <div class="alert alert-warning">
                    <font color="black">
                    <h5><i class="glyphicon glyphicon-info-sign"></i><strong>キャンペーンご利用中のお客様へ</strong></h5>
                    <p>ただいま、お届け日時の指定を承ることができませんが、お届けの時間帯の指定は承ります。<br />
ご注文の商品は、すべての商品の準備が整い次第、順次発送いたします。<br />
注文が集中するため、通常より、1～2週間ほど発送に時間がかかります。<br />
商品発送時には、出荷案内メールを送付いたします。<br />
あらかじめご了承ください。<br /><br />
                    </font>
                </div>
            </div>';
                   }
                }

                if($showSupportMessage) {
                    echo '<div class="col-md-12" style="margin-bottom:10px">
                <div class="alert alert-success" role="alert">
                    <font color="black">
                    <h5><i class="glyphicon glyphicon-info-sign"></i><strong>能登半島地震の義捐金にご協力していただけるお客様へ</strong></h5>
                    <p>ライブカートに一口1100円の義捐金を掲載させていただいています。<br />
                    ご購入されるとご注文明細では<br />
                    1000円＋消費税100円と表記されていますが<br />
                    一口1100円の義捐金として被災者の方々の支援と復興に役立ててまいります。<br />
                    なお、【代引き支払いでの義捐金のみ】のお申込みは承っておりません。<br />
                    ご了承をお願いいたします。<br /><br />
                    他の商品もあわせてご購入されておられる場合は、義損金のお申込みは承ることが可能となっております。<br /><br />
                    </font>
                </div>
            </div>';
                }

                $now = time();

                if($cart_idx == Company::PKEY_TY) {
                    $sample = new \common\models\DeliveryDateTimeForm(['company_id'=>$cart_idx]);
                    if(strtotime('2022-12-16 00:00:00') <= $now && strtotime('2023-01-04 00:00:00') > $now) {
                       echo $sample->getHolidayMessage();
                    }
// 一括発送カートはcart_idx : 0
                } else if($cart_idx == 0) {
                    $sample = new \common\models\DeliveryDateTimeForm(['company_id'=>$cart_idx]);
                    if(strtotime('2022-12-16 00:00:00') <= $now && strtotime('2023-01-05 00:00:00') > $now) {
                       echo $sample->getHolidayMessage();
                    }
                }
            ?>


            <?php if(! $customer->isAdult() && $model->hasLiquor()): ?>
            <div class="col-md-12" style="margin-bottom:10px">
                <div class="alert alert-warning">
                    <h5>お酒を購入するお客様へ</h5>
                    <p>カートの中にはお酒が含まれています。未成年ではないことを確認するために以下の入力をお願いいたします。</p>
                    <strong>私は
                        <?= Html::textInput('customer-age', null, [
                            'id'      => 'i-am-adult',
                            'required'=> 'required',
                            'class'   => 'form-control',
                            'style'   => 'width:60px; display:inline',
                        ]) ?>
                        歳です</strong>
                <?php if(! Yii::$app->user->isGuest && ! $customer->birth): ?>
                <p>
                    なお、生年月日の登録が完了するとこのメッセージは表示されなくなります。
                </p>
                <p class="text-right">
                    <?= Html::a('登録ページへ行く',['/profile/default/update'],['class'=>'btn btn-warning']) ?>
                </p>
                <?php endif ?>
            </div>
            </div>
            <?php endif ?>

            <div class="col-md-12">
                <?php echo $this->render('_items', ['cart_idx' => $key, 'model' => $model, 'editable'=>true]) ?>
            </div>

            <p class="text-left">
                <?= Html::a('お買い物を続ける','/',['class'=>'btn btn-warning']) ?>
            </p>

            <p class="hint-block text-center" style="vertical-align: middle;">
                <img alt="STOP未成年者飲酒" src="/img/stop.png" style="opacity:0.8;max-height:32px">
                当モールでは20歳以上の年齢であることを確認できない場合には酒類を販売いたしません。
            </p>

            <?php if(Payment::PKEY_DROP_SHIPPING === $model->purchase->payment_id):
                    $commissions = \common\models\Purchase::createCommissionModels($model->purchase) ?>
            <div class="col-md-12 alert alert-info">
                <p><strong>代行発送について</strong></p>
                お支払方法が「代行発送（着払い・代引き）」に設定されました。
                お届け先には通常価格でお支払いいただき、代理店のみなさまには後日、代理店手数料金を還元します。
            </div>
                <?php endif ?>
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
                        'postage:currency',
                        [
                            'attribute'=> 'postage_frozen',
                            'format'   => 'currency',
                            'label'    => '（内　冷凍便送料）',
                            'visible'  => ($cart_idx == Company::PKEY_TY && $model->purchase->include_frozen),
                        ],
                        'handling:currency',
                        [
                            'attribute'=> 'point_consume',
                            'format'   => 'currency',
                            'value'    => (0 - $model->purchase->point_consume),
                            'visible'  => (0 < $model->purchase->point_consume),
                        ],
                        [
                            'attribute'=> 'total_charge',
                            'format'   => 'raw',
                            'value'    => Html::tag('span', $formatter->asCurrency($model->purchase->total_charge),['class'=>'Total']),
                        ],

                        [
                            'attribute'=> 'point_given',
                            'format'   => 'html',
                            'value'    => 'pt ' . $formatter->asInteger(max(0, $model->pointGiven) ),
                            'visible'  => ! Yii::$app->user->isGuest,
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
                     <?=           Html::a("注文を確定する", ['finish', 'cart_idx' => $cart_idx, 'campaign_code' => $model->purchase->campaign ? $model->purchase->campaign->campaign_code : ""], [
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



                ?>


                    <?php
                    /**
                     * 注文者と受取人が異なり、かつ酒類を含み、かつ受取人払いの場合、警告を表示する
                     */
                    $showMessage = 0;

                    if( $model->items &&
                       ($model->customer->name != $model->delivery->name) &&
                        in_array($model->payment->payment_id, [
                            Payment::PKEY_YAMATO_COD,
                            Payment::PKEY_POSTAL_COD,
                            Payment::PKEY_DROP_SHIPPING])
                    )
                    foreach($model->items as $item)
                    {
                        $showMessage += $item->isLiquor();
                    }
                    ?>
                    <?php if($showMessage): ?>
                    <p class="alert alert-danger">
                        酒税法上、酒類の商品が含まれている場合、受け取り人ご本人様に注文していただく必要があります（確定ボタンを受け取り人ご本人様以外押すことはできません）。
                    </p>
                    <?php endif ?>

                <?php endif /* model->hasErrors() */ ?>

                <?php if(!$model->purchase->agent_id && Yii::$app->user->id != $model->customer->id): ?>
                    <hr>
                    <div class="text-danger">
                    <h5>ご注文者</h5>
                    <p id="purchase-customer-name"><?= $model->customer->name ?></p>
                    </div>
                <?php endif ?>

		<?php if(!$model->purchase->include_pre_order): ?>
                <?php if((0 < Yii::$app->user->id) && ((Yii::$app->user->id == $model->customer->id) || ($model->purchase->agent_id && Yii::$app->user->id == $model->purchase->agent_id))): ?>
                    <hr>
                    <?php if((0 < count(\common\models\Campaign::getCampaignWithBranch(\common\models\Branch::PKEY_FRONT)))): ?>
                    <h5>
                        キャンペーンコード
                        <?php if($model->purchase->campaign): ?>
                            <?= Html::a("変更", ['update', 'target'=>'campaign', 'cart_idx'=> $cart_idx],['class'=>'btn btn-default','id'=>'input-campaign-toggle']) ?>
                        <?php endif ?>
                    </h5>
                    <p>
                        <span id="input-campaign-still">
                        <?= $model->purchase->campaign ? $model->purchase->campaign->campaign_name : "登録ボタンをクリックするとキャンペーンが適用されます" ?>
                        </span>

                       <?php if($model->purchase->campaign): ?>
                        <div id="input-campaign-dynamic" style="display:none">
                       <?php else: ?>
                        <div id="input-campaign-dynamic">
                       <?php endif ?>
                        <?php $form = \yii\bootstrap\ActiveForm::begin([
                            'id'     => 'form-update-campaign',
                            'method' => 'post',
                            'action' => ['update','target'=>'campaign','cart_idx'=>$cart_idx],
                            'fieldConfig'=> ['template'=>'{input}{error}'],
                            'enableClientValidation' => false,
                        ]) ?>
                        <?= $form->field($model->purchase, 'campaign_code')->textInput(['placeholder' => "キャンペーンコードを入力", 'value' => $model->purchase->campaign ? $model->purchase->campaign->campaign_code : "",
                            ],[
                            'options'      => [
                                'name' => 'campaign_code',
                            ],
//                          'clientOptions'=> [
//                                'min' => 0,
//                                'max' => abs(min(Yii::$app->user->identity->point, $model->purchase->subtotal)),
//                            ]
                            ]) ?>
                       <?php if(!$model->purchase->campaign): ?>
                        <?= Html::submitButton('登録',['name'=>'submit_campaign','class'=>'btn btn-sm btn-success']) ?>
                       <?php else: ?>

                        <?= Html::submitButton('更新',['name'=>'submit_campaign','class'=>'btn btn-xs btn-primary']) ?>
                        <?= $model->purchase->campaign ? Html::a("削除", ['update', 'target'=>'campaign-del', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-danger']) : "" ?>
                        <?= Html::a("閉じる", ['update', 'target'=>'campaign', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-default','id'=>'input-campaign-cancel-toggle']) ?>
                       <?php endif ?>

                        <?php $form::end() ?>
                        </div>

                        <br>
                    </p>
                     <?php endif ?>
                    <?php if($customer->grade_id >= CustomerGrade::PKEY_TA): ?>
                    <h5>
                        サポート注文
                        <?php if($model->purchase->agent_id): ?>
                            <?= Html::a("変更", ['update', 'target'=>'agent', 'cart_idx'=> $cart_idx],['class'=>'btn btn-default','id'=>'input-agent-toggle']) ?>
                        <?php endif ?>
                    </h5>
                    <p>
                        <span id="input-agent-still">
                        <?= $model->purchase->agent_id ? $model->delivery->name : "設定ボタンをクリックするとサポート注文が適用されます" ?>
                        </span>

                       <?php if($model->purchase->agent_id): ?>
                        <div id="input-agent-dynamic" style="display:none">
                       <?php else: ?>
                        <div id="input-agent-dynamic">
                       <?php endif ?>
                        <?php $form = \yii\bootstrap\ActiveForm::begin([
                            'id'     => 'form-update-agent',
                            'method' => 'post',
                            'action' => ['update','target'=>'agent','cart_idx'=>$cart_idx],
                            'fieldConfig'=> ['template'=>'{input}{error}'],
                            'enableClientValidation' => false,
                        ]) ?>
                        <?= $form->field($model->purchase, 'direct_code')->dropDownList($direct_customers,
                                ['prompt' => '直送先を選択してください'],
                                ['options' => [$model->purchase->direct_code => ['Selected'=> true]]]
                            ) ?>
                       <?php if(!$model->purchase->agent_id): ?>
                        <?= Html::submitButton('設定',['name'=>'submit_agent','class'=>'btn btn-sm btn-success']) ?>
                       <?php else: ?>

                        <?= Html::submitButton('更新',['name'=>'submit_agent','class'=>'btn btn-xs btn-primary']) ?>
                        <?= $model->purchase->agent_id ? Html::a("削除", ['update', 'target'=>'agent-del', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-danger']) : "" ?>
                        <?= Html::a("閉じる", ['update', 'target'=>'agent', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-default','id'=>'input-agent-cancel-toggle']) ?>
                       <?php endif ?>

                        <?php $form::end() ?>
                        </div>

                        <br>
                    </p>
                     <?php endif ?>
                       <?php if($model->purchase->agent_id): ?>
                    <div class='bg-info'><strong>※青色枠内の項目はサポート注文では変更できません</strong>
                        <?php endif ?>
                    <h5>
                        ポイント値引き
                        <?php // if(Yii::$app->user->identity->point || $model->purchase->point_consume):
                        if($model->customer->point || $model->purchase->point_consume):?>
                            <?= Html::a("変更", ['update', 'target'=>'point', 'cart_idx'=> $cart_idx],['class'=>'btn btn-default','id'=>'input-point-toggle','disabled' => $model->purchase->agent_id ? true : false]) ?>
                        <?php endif ?>
                    </h5>
                    <p>
                        <span id="input-point-still">
                        <?= number_format($model->purchase->point_consume) ?> pt
                        </span>

                        <div id="input-point-dynamic" style="display:none">
                        <?php $form = \yii\bootstrap\ActiveForm::begin([
                            'id'     => 'form-update-point',
                            'method' => 'post',
                            'action' => ['update','target'=>'point','cart_idx'=>$cart_idx],
                            'fieldConfig'=> ['template'=>'{input}{error}'],
                            'enableClientValidation' => false,
                        ]) ?>
                        <?= $form->field($model->purchase, 'point_consume')->widget(\yii\jui\Spinner::classname(),[
                            'options'      => [
                                'name' => 'point'
                            ],
                            'clientOptions'=> [
                                'min' => 0,
//                                'max' => abs(min(Yii::$app->user->identity->point, $model->purchase->subtotal)),
                                'max' => abs(min($model->customer->point, $model->purchase->subtotal)),
                            ]])
                            ?>
                        <?= Html::submitButton('更新',['name'=>'submit_point','class'=>'btn btn-xs btn-primary']) ?>
                        <?= Html::a("閉じる", ['update', 'target'=>'point', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-default','id'=>'input-point-cancel-toggle']) ?>

                        <?php $form::end() ?>
                        </div>

                        <br>
                        <?php if($model->purchase->agent_id): ?>
                        <?php // if(0 < Yii::$app->user->identity->point):
                        elseif(0 < $model->customer->point):?>
                            （現在 <?= //abs(min(Yii::$app->user->identity->point, $model->purchase->subtotal))
                            abs(min($model->customer->point, $model->purchase->subtotal))?> pt まで使えます）
                        <?php else: ?>
                            <small>（使えるポイントはありません）</small>
                        <?php endif ?>
                    </p>
                <?php endif ?>
                <?php else : ?>
                    <?php if((0 < count(\common\models\Campaign::getCampaignWithBranch(\common\models\Branch::PKEY_FRONT)))): ?>
                    <h5>
                        キャンペーンコード
                        <?php if($model->purchase->campaign): ?>
                            <?= Html::a("変更", ['update', 'target'=>'campaign', 'cart_idx'=> $cart_idx],['class'=>'btn btn-default','id'=>'input-campaign-toggle']) ?>
                        <?php endif ?>
                    </h5>
                    <p>
                        <span id="input-campaign-still">
                        <?= $model->purchase->campaign ? $model->purchase->campaign->campaign_name : "登録ボタンをクリックするとキャンペーンが適用されます" ?>
                        </span>

                       <?php if($model->purchase->campaign): ?>
                        <div id="input-campaign-dynamic" style="display:none">
                       <?php else: ?>
                        <div id="input-campaign-dynamic">
                       <?php endif ?>
                        <?php $form = \yii\bootstrap\ActiveForm::begin([
                            'id'     => 'form-update-campaign',
                            'method' => 'post',
                            'action' => ['update','target'=>'campaign','cart_idx'=>$cart_idx],
                            'fieldConfig'=> ['template'=>'{input}{error}'],
                            'enableClientValidation' => false,
                        ]) ?>
                        <?= $form->field($model->purchase, 'campaign_code')->textInput(['placeholder' => "キャンペーンコードを入力", 'value' => $model->purchase->campaign ? $model->purchase->campaign->campaign_code : "",
                            ],[
                            'options'      => [
                                'name' => 'campaign_code',
                            ],
//                          'clientOptions'=> [
//                                'min' => 0,
//                                'max' => abs(min(Yii::$app->user->identity->point, $model->purchase->subtotal)),
//                            ]
                            ]) ?>
                       <?php if(!$model->purchase->campaign): ?>
                        <?= Html::submitButton('登録',['name'=>'submit_campaign','class'=>'btn btn-sm btn-success']) ?>
                       <?php else: ?>

                        <?= Html::submitButton('更新',['name'=>'submit_campaign','class'=>'btn btn-xs btn-primary']) ?>
                        <?= $model->purchase->campaign ? Html::a("削除", ['update', 'target'=>'campaign-del', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-danger']) : "" ?>
                        <?= Html::a("閉じる", ['update', 'target'=>'campaign', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-default','id'=>'input-campaign-cancel-toggle']) ?>
                       <?php endif ?>

                        <?php $form::end() ?>
                        </div>

                        <br>
                    </p>
                     <?php endif ?>

                    <h5>
                        ポイント値引き
                        <?php // if(Yii::$app->user->identity->point || $model->purchase->point_consume):
                        if($model->customer->point || $model->purchase->point_consume):?>
                            <?= Html::a("変更", ['update', 'target'=>'point', 'cart_idx'=> $cart_idx],['class'=>'btn btn-default','id'=>'input-point-toggle','disabled' => $model->purchase->agent_id ? true : false]) ?>
                        <?php endif ?>
                    </h5>
                    <p>
                        <span id="input-point-still">
                        <?= number_format($model->purchase->point_consume) ?> pt
                        </span>

                        <div id="input-point-dynamic" style="display:none">
                        <?php $form = \yii\bootstrap\ActiveForm::begin([
                            'id'     => 'form-update-point',
                            'method' => 'post',
                            'action' => ['update','target'=>'point','cart_idx'=>$cart_idx],
                            'fieldConfig'=> ['template'=>'{input}{error}'],
                            'enableClientValidation' => false,
                        ]) ?>
                        <?= $form->field($model->purchase, 'point_consume')->widget(\yii\jui\Spinner::classname(),[
                            'options'      => [
                                'name' => 'point'
                            ],
                            'clientOptions'=> [
                                'min' => 0,
//                                'max' => abs(min(Yii::$app->user->identity->point, $model->purchase->subtotal)),
                                'max' => abs(min($model->customer->point, $model->purchase->subtotal)),
                            ]]) ?>
                        <?= Html::submitButton('更新',['name'=>'submit_point','class'=>'btn btn-xs btn-primary']) ?>
                        <?= Html::a("閉じる", ['update', 'target'=>'point', 'cart_idx'=> $cart_idx],['class'=>'btn btn-xs btn-default','id'=>'input-point-cancel-toggle']) ?>

                        <?php $form::end() ?>
                        </div>

                        <br>
                        <?php if(0 < $model->customer->point):?>
                            （現在 <?= //abs(min(Yii::$app->user->identity->point, $model->purchase->subtotal))
                            abs(min($model->customer->point, $model->purchase->subtotal))?> pt まで使えます）
                        <?php else: ?>
                            <small>（使えるポイントはありません）</small>
                        <?php endif ?>
                    </p>
                <?php endif ?>
                <hr>
                <h5>お届け先<?= Html::a("変更",['update','target'=>'addr','cart_idx'=>$cart_idx],['id'=>'btn-update-addr','class'=>$model->delivery->hasErrors()?'btn btn-danger':'btn btn-default','disabled' => $model->purchase->agent_id ? true : false]) ?></h5>
                <?php (Payment::PKEY_DROP_SHIPPING === $model->purchase->payment_id) ? $class='alert-info' : $class = '' ?>
                <p class="<?= $class ?>">〒<?= $model->delivery->zip ?><br>
                    <?= $model->delivery->addr ?><br>
                    <?= $model->delivery->name ?> 様
                </p>
                <hr>
                <?php if ($model->checkForGift($cart_idx)): ?>
                <h5>納品書金額表示<?= Html::a("変更",['update','target'=>'gift','cart_idx'=>$cart_idx],['class'=>'btn btn-default','id'=>'btn-update-gift','disabled' => $model->purchase->agent_id ? true : false]) ?></h5>

                    <p id="" class="">
                    <?= ($model->delivery->gift) ? "非表示" : "表示" ?>
                    <p>

                <?php endif ?>
                <?php if($model->purchase->agent_id): ?>
                  </div>
                <?php endif ?>
                <?php (Payment::PKEY_DROP_SHIPPING === $model->purchase->payment_id) ? $class='alert-info' : $class = '' ?>
                <h5>ご購入方法
                    <?php if(1 < count($model->payments)): ?>
                    <?= Html::a("変更", ['update', 'target' => 'payment', 'cart_idx' => $cart_idx], ['class' => 'btn btn-default', 'id' => 'btn-update-payment']) ?>
                    <?php endif ?>
                </h5>
                <p id="purchase-paynent-name" class="<?= $class ?>"><?php if ($model->purchase->company_id == Company::PKEY_TROSE){ echo " 【Tommy Roseではポイントはご使用になれません】<br />
セール品以外の商品にはポイントは付きます。<br /><br />";}?><?= $model->purchase->payment->name ?></p>

                <?php if ($model->purchase->company_id == Company::PKEY_TROSE) : ?>
                    <small><p>全国一律700円（送料＋代引き料）<br/>時間指定はできません。<br/>税込11000円以上ご購入で送料無料<p></small>
                <?php endif ?>
                <hr>
		<?php if(!$model->purchase->include_pre_order): ?>
                <h5>お届け日時
                <?php if(($model->company->company_id == \common\models\Company::PKEY_TY && isset($model->purchase->items[0]) && $model->purchase->items[0]->getModel()->product_id == \common\models\Product::PKEY_OSECHI)): ?>

                <?php elseif($model->purchase->payment->delivery && $model->purchase->delivery->validate(['zip01','zip02']) && ($model->purchase->company_id != Company::PKEY_TROSE)): ?>
                    <?= Html::a("変更",['update','target'=>'date','cart_idx'=>$cart_idx],['id'=>'btn-update-date','class'=>$model->hasErrors('delivery')?'btn btn-danger':'btn btn-default']) ?>
                <?php endif ?>
                </h5>
                <p>
                    <?= $model->delivery->datetimeString ?>
                </p>
                <p>
                    <?php if($cart_idx == Company::PKEY_TY && !$model->purchase->campaign): ?>
                        <?php $sample = new \common\models\DeliveryDateTimeForm(['company_id'=>$cart_idx]); ?>
                        <?php if($model->purchase->items[0]->getModel()->product_id == \common\models\Product::PKEY_OSECHI) {
                                  $sample->now = strtotime("2017-12-28 18:00:00"); ?>
                        <?php } ?>
                            <?php if(!$model->purchase->campaign): ?>
                                <?php if(date('Y-m-d',$sample->now) == "2022-12-28" || date('Y-m-d',$sample->now) == "2023-01-05") {
                                    echo "加工品：".Yii::$app->formatter->asDate($sample->now, 'php:Y年m月d日(D)')."、野菜セット：2023年1月12日 (木)";
                                } else {
                                    echo Yii::$app->formatter->asDate($sample->now, 'php:Y年m月d日(D)') ."に発送予定";
                                }
                                ?>

                            <?php endif ?>
                    <?php endif ?>
                </p>
                 <?php else: ?>
                        <h5>発送予定日</h5>
                        <?= $pre_order_deliv_date ?>
                <?php endif ?>

                <?php if(Yii::$app->user->isGuest): ?>
                    <hr>
                    <h5>メール配信<?= Html::a("変更",['/cart/guest/signup'],['class'=>!$customer->email || $customer->hasErrors('email')?'btn btn-danger':'btn btn-default']) ?>
                    </h5>
                    <p>
                        <?= $customer->email ?>
                        <br>
                        <p class="help-block"><?= $model->purchase->getAttributeHint('email') ?></p>
                    </p>
                <?php endif ?>

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
