<?php
/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_datetime.php $
 * @version $Id: _datetime.php 4236 2020-03-11 08:45:58Z mori $
 *
 * $model 
 */

use \yii\helpers\Html;
use \yii\widgets\ActiveForm;

$timeModel = new \common\models\DeliveryTime();
$drop1 = $model->getDateCandidates($cart->purchase->include_frozen);
array_unshift($drop1, "指定なし");

// ヤマトが12時〜14時指定を廃止した。time_id 2を除外したいがarray_unshiftやarray_mergeを使用すると数値添え字が振り直しになり値との対応が狂うので注意　2017/06/15
$drop2 = \yii\helpers\ArrayHelper::map($timeModel->yamato, 'time_id', 'name');
$array = ['0' => "指定なし"];
$drop2 = $array + $drop2;
?>

<h1 class="mainTitle">配達日時の指定</h1>
<p class="mainLead">
ご注文の商品をお届けする日時が指定できます。
</p>

<?php $form = ActiveForm::begin([
  'id' => 'cart-update-datetime',
  'validateOnBlur'  => true,
  'validateOnChange'=> true,
  'validateOnSubmit'=> true,
  'fieldConfig' => [
      'template' => '{input}{hint}{error}',
  ],
]);
?>

<table summary="会員登録" id="FormTable" class="table table-bordered">
<tbody>

    <tr>
    <th><div><label>お届け先</label></div></th>
    <td>
        <p>
            〒<?= $cart->delivery->zip ?>
            <?= $cart->delivery->addr ?>
        </p>
        <p>
            <?= $cart->delivery->name ?> 様
        </p>
    </td>
    </tr>

<?php if(0){ ?>
    <tr>
    <th><div><label>希望日</label></div></th>
    <td>
        <div class="form-group field-deliverydatetimeform-date required">
<select id="deliverydatetimeform-date" class="form-control" name="DeliveryDateTimeForm[date]">
<option value="0">指定なし</option>
</select><div class="help-block">ただいま多くのご注文を頂戴しており、順次作業を進めておりますが、
ご希望の日付指定に沿う形で発送対応が難しい状況です。</br>
誠に申し訳ありませんが、希望日指定の選択は受付を停止しております。</br>
※時間帯指定は通常通り選択可能です。
</div>
</div>    </td>
    </tr>
<?php }else{ ?>
    <tr>
    <th><div><label><?=$model->getAttributeLabel('date')?></label></div></th>
    <td>
        <?php if($cart_idx == 0 || $cart->purchase->campaign): ?>
        <span>現在、配達希望日の選択はできません。ご了承ください。</span>
        <?php else: ?>
        <?= $form->field($model, 'date')->dropDownList($drop1) ?>
        <?php endif ?>
    </td>
    </tr>
<?php } ?>

    <tr>
    <th><div><label><?=$model->getAttributeLabel('time_id')?></label></div></th>
    <td>
        <?= $form->field($model, 'time_id')->dropDownList($drop2) ?>
    </td>
    </tr>

</tbody>
</table>

<?= Html::submitButton("更新", [
        'class' => 'btn btn-primary',
        'name'  => 'scenario',
        'value' => 'default',
    ]) ?>

<?php ActiveForm::end(); ?>

<p class="help-block">
<?= $model->toCustomerMessage ?>
ご希望があればお届け日時を指定してください。
</p>


