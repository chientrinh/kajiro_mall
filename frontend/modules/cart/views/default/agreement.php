<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/index.php $
 * $Id: index.php 1853 2015-12-09 11:06:24Z mori $
 *
 * $carts array of Cart
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$title = "サポート注文同意書";
$this->title = $title;
$this->params['body_id']       = 'Cart';
$this->params['breadcrumbs'][] = ['label' => "カート", 'url' => ['/cart']];
$this->params['breadcrumbs'][] = ['label' => $title];
$this->title = sprintf('%s | %s | %s', $title, "カート", Yii::$app->name);

$jscode = "
    $('#cart-finish').attr('disabled', 'disabled');

    isCheck($('.agreed'));

    $('.agreed').on('click', function() {
        isCheck($(this));
    });

    function isCheck(obj){
        if (obj.prop('checked') == false) {
            $('#cart-finish').attr('disabled', 'disabled');
        } else {
            $('#cart-finish').removeAttr('disabled');
        }
    }
";
$this->registerJs($jscode);

$text = \common\models\SupportAgreement::find()->one();

?>
<div class="cart-default-index">

    <h1 class="mainTitle">確認事項</h1>

    <div class="row" style="height: 400px; overflow-y: scroll; border: 1px solid #DDD;padding:15px; width: 75%;margin: 0 auto;">
        <?= \yii\widgets\DetailView::widget([
                'model' => $text,
                'options'    => ['class'=>''],
                'attributes' => [
                    [
                        'attribute'=> 'text',
                        'label'    => false,
                        'format'   => 'raw',
                        'value'    => $text ? html_entity_decode(nl2br($text->text)) : ''
                    ],
                ]
            ]) ?>
    </div>
    <p class="mainLead">        
        <br>
        <label>
            <input type="checkbox" class="agreed" name="agreed" value="1" id="agreed">
            &nbsp;同意する
        </label><br><br>
        <?= Html::a("注文を確定する", ['finish', 'cart_idx' => $cart_idx, 'agreed' => 1], [
            'id'       => 'cart-finish',
            'class'    => 'btn btn-danger',
            'data'     => [
                'method' => 'post',
            ],
        ]); ?>
        <?= Html::a("戻る", ['index', 'cart_idx' => $cart_idx], [
            'class' => 'btn btn-primary',
        ]); ?>
    </p>

</div>
