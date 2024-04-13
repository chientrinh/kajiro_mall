<?php
/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_gift.php $
 * @version $Id: _gift.php 1932 2015-12-27 08:27:35Z mori $
 *
 * $model AddrbookForm
 */

use \yii\helpers\Html;
use \yii\helpers\Url;
use \yii\widgets\ActiveForm;
use \yii\widgets\ActiveField;

use \common\models\Payment;

$title = "納品書金額表示の指定";
$this->params['body_id']       = 'Mypage';
$this->params['breadcrumbs'][] = $title;
$this->title = sprintf('%s | %s | %s', $title, "カート", Yii::$app->name);

$this->registerCss('
div.required
 label:after {
  content: "";
  color: white;
}
div.required label {
  font-weight: bold;
}
');

$bankTransfer = Payment::findOne(Payment::PKEY_BANK_TRANSFER);
$dropShipping = Payment::findOne(Payment::PKEY_DROP_SHIPPING);
$yamatoCod    = Payment::findOne(Payment::PKEY_YAMATO_COD);

$gift = (int)$cart->delivery->gift;
$user = Yii::$app->user->identity;
if(! $user)
    throw new \yii\web\ForbiddenHttpException();
if($cart_idx == \common\models\Company::PKEY_TROSE)
    throw new \yii\web\ForbiddenHttpException('このカートでは納品書金額表示の指定ができません');
?>

<h1 class="mainTitle"><?= $title ?></h1>
<p class="mainLead">代引きをご利用の場合は、金額表示となります</p>
<div class="row column01">
    <div class="col-md-12">

        <?php $form = ActiveForm::begin([
            'fieldConfig' => [
                'template' => "{input}\n{hint}\n{error}",
            ],
        ]) ?>

        <table summary="<?= $title ?>" id="FormTable" class="table table-bordered">
            <tbody>

                <tr>
                    <th>
                        <?= $form->field($cart->delivery,'gift')->radio(['name'=>'gift','value'=>1,'uncheck'=>null,'label'=>'非表示','checked'=>true]) ?>
                    </th>
                    <td>
                        同梱する納品書には金額を記載しません。
                    </td>
                </tr>

                <tr>
                    <th><div>
                        <?= $form->field($cart->delivery,'gift')->radio(['name'=>'gift','value'=>0,'uncheck'=>null,'label'=> '表示']) ?>
                    </div></th>
                    <td>
                        納品書には通常どおり単価・小計・送料などを記載します。
                    </td>
                </tr>

            </tbody>
        </table>

        <div class="form-group" style="text-align:center;">
            <button type="submit" class="btn btn-primary" name="delivery-edit">指定する</button>
        </div>
        <?php $form->end(); ?>
    </div>
</div>
