<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/recipe/batch.php $
 * $Id: batch.php 3606 2017-09-24 05:55:37Z naito $
 *
 * @var $this         yii\web\View
 * @var $model        SimpleForm
 */

$company = \common\models\Company::findOne(['key'=>'hj']);
$this->params['body_id']       = 'Company';
$this->params['breadcrumbs'][] = ['label'=>$company->name, 'url' => ['/hj']];
$this->params['breadcrumbs'][] = ['label'=>'販売店・取扱所様専用注文入力', 'url' => ['/hj/wholesale']];
$this->params['breadcrumbs'][] = ['label'=>'適用書レメディーの購入'];

$column = ArrayHelper::getColumn($this->params,'breadcrumbs.label');
rsort($column);
$this->title = implode(' | ', $column);

?>

<div class="product-index">

    <p class="text-right">
        <?= Html::a('単品レメディーの購入',['/hj/wholesale'],['class'=>'btn btn-success']) ?>
    </p>

    <h1 class="mainTitle">販売店・取扱所様専用注文入力</h1>

    <p class="mainLead"><?= $company->name?></p>

    <div class="col-md-12">
        <?php $form = \yii\bootstrap\ActiveForm::begin() ?>

        <?= $form->field($model,'recipe_id')->textArea(['class'=>'form-control js-zenkaku-to-hankaku','rows'=>8]) ?>
        <p class="hint-block">
            スペース または 改行 で区切って入力してください
        </p>

        <?= Html::submitbutton('購入',['class'=>'btn btn-warning']) ?>

        <?php $form->end() ?>

        <?php $form->errorSummary($model) ?>
    </div>

</div>
