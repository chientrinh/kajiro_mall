<?php
/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/guest/signup.php $
 * @version $Id: signup.php 2650 2016-06-30 09:02:37Z mori $
 *
 * $model AddrbookForm
 */

use \yii\helpers\Html;
use \yii\widgets\ActiveForm;
use \common\models\Customer;

$title = "登録しないでご購入";
$this->params['breadcrumbs'][] = ['label'=>'カート','url'=>['/cart']];
$this->params['breadcrumbs'][] = ['label'=>$title];
$this->params['body_id']       = 'Signup';
$this->title = implode(' | ',array_merge(array_reverse(\yii\helpers\ArrayHelper::getColumn($this->params['breadcrumbs'],'label')),[Yii::$app->name]));

$prefs = \yii\helpers\ArrayHelper::map(\common\models\Pref::findAllDomestic(), 'pref_id', 'name');
array_unshift($prefs, "選択してください");

if(! $agreed)
    $this->registerCss('.sub-menu{display:none}');

$jscode = "
    $('.agreed').click(function(){
        if(this.checked)
            $('.sub-menu').show();
        else
            $('.sub-menu').hide();
    	return true;
    });
";
$this->registerJs($jscode);
?>

<h1 class="mainTitle"><?= $title ?></h1>
<p class="mainLead">
    <strong><?= Html::a("利用規約",['/site/usage'],['target'=>'_blank']) ?></strong> をご確認・同意の上お進みください。<br>
    <label>
    <input type="checkbox" class="agreed " name="agreed" value="1" <?= $agreed ? 'checked' : '' ?>>
      &nbsp;利用規約に同意する
    </label>
<br>
<br>

ご注文の商品をお届けする住所を指定します。電話番号やお名前もご記入ください。
</p>

<div class="sub-menu row column01">
<div class="col-md-12">

<?php $form = ActiveForm::begin([
  'id' => 'cart-guest-signup',
  'validateOnBlur'  => false,
  'validateOnChange'=> false,
  'validateOnSubmit'=> false,
  'fieldConfig'     => ['template'=>'{input}{error}'],
]);?>

<table summary="会員登録" id="FormTable" class="table table-bordered">
<tbody>

    <tr>
    <th><div class="required"><label>メールアドレス</label></div></th>
    <td>
    <?= $form->field($customer, 'email')->textInput(['name'=>'email']) ?>
        <?php if(Customer::findOne(['email' => $customer->email])): ?>
        <p class="help-block">登録済みのお客様は <?= Html::a('ログイン',['/site/login']) ?> からお進みください</p>
        <?php endif ?>
    </td>
    </tr>

    <tr>
    <th><div class="required"><label>お名前</label></div></th>
    <td>
    <span class="float-box2">姓</span>
    <?= $form->field($model, 'name01',['options'=>['class'=>'']]) ?>
    <span class="float-box2">名</span>
    <?= $form->field($model, 'name02',['options'=>['class'=>'']]) ?>
    </td>
    </tr>

    <tr>
    <th><div class="required">
    <label>郵便番号</label>
    </div></th>
    <td>
<span class="float-box2">〒</span>
    <?= $form->field($model, 'zip01',['options'=>['class'=>'Tel']])->textInput(['class'=>'form-control js-zenkaku-to-hankaku']) ?>
    <span class="float-box">-</span>
    <?= $form->field($model, 'zip02',['options'=>['class'=>'Tel']])->textInput(['class'=>'form-control js-zenkaku-to-hankaku']) ?>
    &nbsp;
<button id="btn-zip2addr" type="submit" class="btn btn-primary" name="scenario" value="zip2addr">住所を検索</button>
    &nbsp;
<a href="http://www.post.japanpost.jp/zipcode/" class="btn btn-default pull-right" target="_blank"><span class="fs12">郵便番号検索へ</span></a>
    <p class="help-block help-block-error"></p>
    &nbsp;

    </div></td>
    </tr>

    <tr>
    <th><div class="required">
    <label>住所</label>
    </div></th>
    <td>
    <?= $form->field($model, 'pref_id')->dropDownList($prefs) ?>
    <label class="control-label" for="signupform-addr01">市区町村名（例：千代田区神田神保町）</label>
<?php if($model->addrCandidate):

$candidate = [];
foreach($model->addrCandidate as $value)
{
    $candidate[$value] = $value;
}
echo $form->field($model, 'addr01')->dropDownList($candidate)->render();
?>

<?php else: ?>
    <?= $form->field($model, 'addr01') ?>
<?php endif ?>

    <label class="control-label" for="signupform-addr02">番地・ビル名（例：1-3-5）</label>
    <?= $form->field($model, 'addr02') ?>
    </td>
    </tr>

    <tr>
    <th><div class="required">
    <label>電話番号</label>
    </div></th>
    <td>
    <?= $form->field($model, 'tel01',['options'=>['class'=>'Tel']])->textInput(['class'=>'form-control js-zenkaku-to-hankaku']) ?>
    <span class="float-box">-</span>
    <?= $form->field($model, 'tel02',['options'=>['class'=>'Tel']])->textInput(['class'=>'form-control js-zenkaku-to-hankaku']) ?>
    <span class="float-box">-</span>
    <?= $form->field($model, 'tel03',['options'=>['class'=>'Tel']])->textInput(['class'=>'form-control js-zenkaku-to-hankaku']) ?>
    </td>
    </tr>

</tbody>
</table>

    <div class="form-group">

    <?= Html::submitButton("保存してカートに戻る", [
        'id'    => 'btn-signup',
        'class' => 'btn btn-success',
        'name'  => 'scenario',
        'value' => 'default',
    ]) ?>

    </div><!--form-group-->

    <?php ActiveForm::end(); ?>

  </div><!--col-md-12-->
  </div><!--row column01-->

