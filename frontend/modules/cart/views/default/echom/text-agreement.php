<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/index.php $
 * $Id: index.php 1853 2015-12-09 11:06:24Z mori $
 *
 * $carts array of Cart
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$title = "確認事項";
$this->title = $title;
$this->params['body_id'] = 'Cart';
$this->params['breadcrumbs'][] = ['label' => $title];
$this->title = sprintf('%s | %s | %s', $title, '商品', Yii::$app->name);

$jscode = "
    $('#add-finish').attr('disabled', 'disabled');

    isCheck($('.agreed'));

    $('.agreed').on('click', function() {
        isCheck($(this));
    });

    function isCheck(obj){
        if (obj.prop('checked') == false) {
            $('#add-finish').attr('disabled', 'disabled');
        } else {
            $('#add-finish').removeAttr('disabled');
        }
    }
";
$this->registerJs($jscode);

$text = \common\models\TicketAgreement::find()->where(['product_id' => $pid])->one();

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
        <?= Html::a("カートにいれる", ['add', 'pid' => $pid, 'agreed' => 1], [
            'id'       => 'add-finish',
            'class'    => 'btn btn-danger',
            'data'     => [
                'method' => 'post',
            ],
        ]); ?>
        <?= Html::a("戻る", ['/product/' . $pid], [
            'class' => 'btn btn-primary',
        ]); ?>
    </p>

</div>
