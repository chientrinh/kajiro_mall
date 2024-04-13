<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/remedy/_drop.php $
 * $Id: _drop.php 3411 2017-06-08 10:32:46Z kawai $
 *
 * $model    RemedyStock (an item of \common\components\cart\TailorMadeRemedyForm()->drops)
 * $key      
 * $index
 * $vial     RemedyStock
 * $form     ActiveForm
 * $remedies array of remedy_id => abbr
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>

<div class="col-md-12">
  <h4><span>滴下 <?= $index +1 ?></span></h4>
    <div class="form group">
    <?= \yii\jui\AutoComplete::widget([
        'id' => sprintf('remedy-abbr-%d',$index),
        'attribute'     => 'abbr',
        'name'  => sprintf('Drops[%d][abbr]', $index),
        'value' => $model->remedy ? $model->remedy->abbr : '',
        'clientOptions' => ['source' => new yii\web\JsExpression('
                    function( request, response ) {
                    var tags = ' . json_encode($remedies) . ';
                    var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( request.term ), "i" );
                    response( $.grep( tags, function( item ){
                        return matcher.test( item );
                    }) );
                    }
                ')],
        'options'       => [
            'class' => 'form-contorl has-success',
        ],
    ]);?>
    <?= Html::submitButton("決定", ['class'=>'btn btn-default']) ?>
    </div>

    <?= Html::activeHiddenInput($model, 'potency_id',[
        'name'  => sprintf('Drops[%d][potency_id]', $index),
        'id'    => sprintf('Drops[%d][potency_id]', $index),
    ])?>

  <p>
<?php
$query = \common\models\RemedyStock::find();
$query->where([ 'remedy_id' => $model->remedy_id ])
      ->forcustomer(Yii::$app->user->identity)
      ->drops();

if(! in_array($vial->vial_id, [\common\models\RemedyVial::GLASS_5ML,
                               \common\models\RemedyVial::GLASS_20ML]))
    $query->foreveryone(); // exclude LM potencies

$drops = $query->all();

foreach($drops as $stock)
{
    $class = ($stock->potency_id == $model->potency_id) ? 'btn btn-success' : 'btn btn-primary';
    echo Html::submitButton($stock->potency->name,[
        'name'  => sprintf('Drops[%d][potency_id]', $index),
        'value' => $stock->potency_id,
        'class' => $class,
        'onClick' => sprintf('document.getElementById(Drop[%d][potency_id].value = %d; this.submit();', $index, $stock->potency_id),
    ]);
    echo '&nbsp;';
}
?>
  </p>

</div>

