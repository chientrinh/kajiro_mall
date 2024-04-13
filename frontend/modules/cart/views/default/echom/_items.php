<?php
use yii\helpers\Html;

/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_items.php $
 * @version $Id: _items.php 2841 2016-08-14 07:37:50Z mori $
 *
 * $cart_idx index of CartManger->carts
 */
if(! isset($editable)) $editable = false;

$widget = new \frontend\widgets\CartItemColumn([
    'cart_idx'=> $cart_idx,
    'purchase'=> $model->purchase,
    'items'   => $model->items,
    'recipes' => $model->recipes,
]);

$formatter = new \yii\i18n\Formatter();
?>
<?= \yii\grid\GridView::widget([
'dataProvider' => new \yii\data\ArrayDataProvider([
    'allModels' => $widget->items,
        'sort' => [
            //'attributes' => ['id', 'username', 'email'],
            ],
        ]),
    'layout' => '{items}{pager}{summary}'. sprintf('<strong>のべ %d 点</strong>', $model->itemCount),

    'emptyText' => 'カートの中に商品はありません',
    'columns' => [
        [
            'label'    => "商品画像",
            'format'   => 'html',
            'value'    => function($data,$key,$idx,$col) use ($widget)
            {
                return $widget->renderImageColumn($key);
            },
        ],
        [
            'attribute'=> 'name',
            'label'    => '品名',
            'format'   => 'html',
            'value'    => function($data,$key,$idx,$col) use ($widget)
            {
                return $widget->renderLabelColumn($key);
            },
            'headerOptions' => ['class'=>'Name'],
        ],
        [
            'attribute'=> 'price',
            	'label'    => '価格',
            'format'   => 'html',
            'contentOptions' => ['class'=>'text-right'],
            'headerOptions' => ['class'=>'Price'],
            'value'         => function($data,$key,$idx,$col) use($widget)
            {
                return $widget->renderPriceColumn($key);
            },
        ],
        [
            'attribute'=> 'qty',
            'label'    => '数量',
            'format'   => 'raw',
            // 'value'         => function($model,$key,$idx,$col)use($widget)
            // {
            //     return $widget->renderQtyColumn($key);
            // },
            'contentOptions' => ['class'=>'text-right col-md-1'],
            'headerOptions'  => ['class'=>'qty'],
            'footer'         => '小計<br>消費税',
        ],
        [
            'attribute'      => 'charge',
            'label'          => '',
            'format'         => 'html',
            'contentOptions' => ['class'=>'text-right'],
            'headerOptions'  => ['class'=>'sum'],
            'value'          => function($model,$key,$idx,$col) use($widget)
            {
                return $widget->renderChargeColumn($key);
            },
            'footer'         => $formatter->asCurrency($model->subtotal) .'<br>'. $formatter->asCurrency($model->tax),
        ],
        ],
        'showFooter'       => true,
        'footerRowOptions' => ['class'=>'text-right'],
        'options'          => ['class'=>'grid-view Details' ],
    ]);
 ?>

<?php if(0 < count($model->recipes)): asort($model->recipes);?>
    <div class="alert alert-info">

    <ul>
        <?php foreach($model->recipes as $rid): ?>
            <li><strong><?php echo sprintf('%06d <small>%s</small>',$rid,Html::a("削  除", ['recipedel','cart_idx'=>$cart_idx, 'recipe_id'=>$rid])); ?></strong></li>
        <?php endforeach ?>
    </ul>
    適用書レメディーが <?= count($model->recipes) ?> 件購入されます
    </div>
<?php endif ?>
