<?php
/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_addr.php $
 * @version $Id: _addr.php 2607 2016-06-23 04:56:08Z naito $
 *
 * $model AddrbookForm
 */

use \yii\helpers\Html;
use \yii\widgets\ActiveForm;

$this->params['body_id']       = 'Mypage';
$this->title = sprintf('%s | %s | %s', "お届け先の指定", "カート", Yii::$app->name);

$prefs = \yii\helpers\ArrayHelper::map(\common\models\Pref::findAllDomestic(), 'pref_id', 'name');
array_unshift($prefs, "選択してください");

//$model->validate();
?>

<h1 class="mainTitle">お届け先の指定</h1>
<p class="mainLead">
ご注文の商品をお届けする住所・連絡先を指定できます。
</p>

<div class="row column01">
<div class="col-md-12">

<?php $form = ActiveForm::begin([
  'id' => 'cart-update-addr',
  'validateOnBlur'  => false,
  'validateOnChange'=> false,
  'validateOnSubmit'=> false,
  'fieldConfig'     => ['template'=>'{input}{error}'],
]);?>

<table summary="会員登録" id="FormTable" class="table table-bordered">
<tbody>

    <tr>
    <th><div class="required"><label>お名前</label></div></th>
    <td>
    <span class="float-box2">姓</span>
    <?= $form->field($model, 'name01',['options'=>['class'=>'col-md-5']]) ?>
    <span class="float-box2">名</span>
    <?= $form->field($model, 'name02',['options'=>['class'=>'col-md-5']]) ?>
    </td>
    </tr>

    <tr>
    <th><div class="required">
    <label>郵便番号</label>
    </div></th>
    <td><div class="field-signupform-zip"> <span class="float-box2">〒</span>
    <?= $form->field($model, 'zip01')->textInput(['class'=>'form-control js-zenkaku-to-hankaku Zip']) ?>
    <span class="float-box">-</span>
    <?= $form->field($model, 'zip02')->textInput(['class'=>'form-control js-zenkaku-to-hankaku Zip']) ?>

    &nbsp;
<button type="submit" class="btn btn-primary" name="scenario" value="zip2addr">住所を検索</button>
	
    &nbsp;
<a href="http://www.post.japanpost.jp/zipcode/" class="btn btn-default pull-right" target="_blank"><span class="fs12">郵便番号検索へ</span></a>
    <p class="help-block help-block-error"></p>
    </div></td>
    </tr>

    <tr>
    <th><div class="required">
    <label>住所</label>
    </div></th>
    <td>
    <?= $form->field($model, 'pref_id')->dropDownList($prefs) ?>
    <label class="control-label" for="signupform-addr01">市区町村名（例：千代田区神田神保町）</label>
<?php if($candidates = $model->addrCandidate):

$items = [];
foreach($candidates as $value)
{
    $items[$value] = $value;
}
echo $form->field($model, 'addr01')->dropDownList($items)->render();
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

    <?= Html::submitButton("変更する", [
        'class' => 'btn btn-primary',
        'name'  => 'scenario',
        'value' => 'default',
    ]) ?>


    <?php if(Yii::$app->user->isGuest): ?>
    <?php else: ?>
    <?= Html::submitButton("やり直し", [
        'class' => 'btn btn-success pull-right',
        'title' => 'ご自分の登録住所に設定します',
        'name'  => 'reset',
        'value' => '1',
    ]) ?>
    <?php endif ?>

    </div><!--form-group-->

    <?php ActiveForm::end(); ?>

  </div><!--col-md-12-->
  </div><!--row column01-->

