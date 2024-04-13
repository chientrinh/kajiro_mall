<?php
/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/_addrbook.php $
 * @version $Id: _addrbook.php 2834 2016-08-11 03:53:10Z mori $
 *
 * $model 
 */

use \yii\helpers\Html;
use \yii\widgets\ActiveForm;

$this->params['breadcrumbs'][] = ['label' => "お届け先の指定", 'url' => ['/cart']];

$customer = Yii::$app->user->identity;
?>

<h1 class="mainTitle">お届け先の指定</h1>
<p class="mainLead">
ご注文の商品をお届けする住所・連絡先を指定できます。
</p>

<?= Html::tag('h3','ご本人登録住所') ?>
<p>
    <?=Html::a('決定',['update','target'=>'addr','cart_idx'=>$cart_idx,'id'=>0],['class'=>'btn btn-xs btn-success']) ?>
    <?= $customer->fulladdress ?>
</p>

<h1>&nbsp;</h1>

<?= Html::tag('h3','お届け先') ?>
<?= \yii\grid\GridView::widget([
    'dataProvider' => new \yii\data\ActiveDataProvider([
        'query' => $customer->getAddrbooks(),
        'pagination' => [
            'pageSize' => 50,
        ],
        'sort' => [
            'attributes' => [
                'zip' => ['asc'  => ['zip01' => SORT_ASC,  'zip02' => SORT_ASC],
                          'desc' => ['zip01' => SORT_DESC, 'zip02' => SORT_DESC],
                ],
                'addr'=> ['asc'  => ['zip01' => SORT_ASC,  'zip02' => SORT_ASC, 'addr02' => SORT_ASC ],
                          'desc' => ['zip01' => SORT_DESC, 'zip02' => SORT_DESC,'addr02' => SORT_DESC],
                ],
                'name'=> ['asc'  => ['kana01' => SORT_ASC,  'kana02' => SORT_ASC],
                          'desc' => ['kana01' => SORT_DESC, 'kana02' => SORT_DESC],
                ],
                'tel' => ['asc'  => ['tel01' => SORT_ASC,  'tel02' => SORT_ASC,  'tel03'=>SORT_ASC],
                          'desc' => ['tel01' => SORT_DESC, 'tel02' => SORT_DESC, 'tel03'=>SORT_DESC],
                ],
            ],
            'defaultOrder' => ['zip' => SORT_ASC],
        ],
    ]),
    'layout'      => '{items}{pager}',
    'showOnEmpty' => false,
    'columns'     => [
        [
            'label' => '',
            'format'=> 'raw',
            'value' => function($data, $key) use ($cart_idx)
            {
                return Html::a('決定',['update','target'=>'addr','cart_idx'=>$cart_idx,'id'=>$key],['class'=>'btn btn-xs btn-success']);
            },
        ],
        'zip',
        'addr',
        'name',
        'tel',
        [
            'label' => '',
            'format'=> 'raw',
            'value' => function($data, $key)
            {
                return Html::a('編集',['/profile/addrbook/update','id'=>$key],['class'=>'btn btn-xs btn-default'])
                     . Html::a('削除',['/profile/addrbook/delete','id'=>$key],['class'=>'btn btn-xs btn-default','data'=>['method'=>'post']]);
            },
        ],
    ],
]) ?>

<?= Html::a("新しいお届け先を追加", ['/profile/addrbook/create'],['class' => 'btn btn-primary']) ?>


