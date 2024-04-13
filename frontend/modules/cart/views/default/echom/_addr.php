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
$this->title = sprintf('%s | %s | %s', "ご連絡先の指定", "カート", Yii::$app->name);

$prefs = \yii\helpers\ArrayHelper::map(\common\models\Pref::findAllDomestic(), 'pref_id', 'name');
array_unshift($prefs, "選択してください");

//$model->validate();
?>

<h1 class="mainTitle">ご連絡先の指定</h1>
<p class="mainLead">
お客様のご連絡先を指定できます。
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

