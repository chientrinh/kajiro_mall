<?php
/**
 * @link    $URL: http://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_payment.php $
 * @version $Id: _payment.php 3471 2017-07-01 04:58:29Z naito $
 *
 * $cart \common\components\cart\Cart
 */

use \yii\helpers\ArrayHelper;
use \yii\helpers\Html;
use \yii\helpers\Url;
use \yii\widgets\ActiveForm;
use \yii\widgets\ActiveField;

use \common\models\Payment;

$title = "お支払い方法の指定";
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

$user = Yii::$app->user->identity;
if(! $user)
    throw new \yii\web\ForbiddenHttpException();

$params = [

    Payment::PKEY_DIRECT_DEBIT => [
        'text' => '口座振替でのお支払いとなります。',
        'help' => '当月末までのご購入分について翌日２６日にご指定の銀行口座より自動引き落としいたします。',
    ],
    Payment::PKEY_CREDIT_CARD => [
        'text' => 'クレジットカードでのお支払いとなります。',
        'help' => '購入完了時に、クレジット決済ページに遷移しますので、手続きをお願いいたします',
    ],
];

$params = [Payment::PKEY_DIRECT_DEBIT,Payment::PKEY_CREDIT_CARD];

foreach($params as $payment_id => $param)
    if(! in_array($payment_id, $cart->payments))
        unset($params[$payment_id]);

?>

<h1 class="mainTitle"><?= $title ?></h1>

<div class="row column01">
    <div class="col-md-12">

        <?php $form = ActiveForm::begin([
            'fieldConfig' => [
                'template' => "{input}\n{hint}\n{error}",
            ],
        ]) ?>

        <table summary="<?= $title ?>" id="FormTable" class="table table-bordered">
            <tbody>

                <?php foreach($params as $payment_id => $param):
                {

                    $model = Payment::findOne($payment_id);

                    $options = [
                        'label'  => $model->name,
                        'value'  => $payment_id,
                        'uncheck'=> null,
                        'checked'=> null,
                    ];

                    $checked = (($p = $cart->payment) && ($p->payment_id == $model->payment_id)) ? true : false;
                        
                } ?>
                <tr>
                    <th>
                        <?= Html::radio('payment', $checked, $options) ?>
                    </th>
                    <td>
                        <?= ArrayHelper::getValue($param, 'text') ?>
                        <p class="help-block">
                            <?= ArrayHelper::getValue($param, 'help') ?>
                        </p>
                    </td>
                </tr>
                <?php endforeach ?>

            </tbody>
        </table>

        <div class="form-group" style="text-align:center;">
            <button type="submit" class="btn btn-primary" name="delivery-edit">指定する</button>
        </div>
        <?php $form->end(); ?>
    </div>
</div>
