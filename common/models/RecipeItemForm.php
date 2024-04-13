<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;
use \common\components\cart\ComplexRemedyForm;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RecipeItemForm.php $
 * $Id: RecipeItemForm.php 2007 2016-01-23 08:32:45Z mori $
 *
 */
class RecipeItemForm extends RecipeItem
{
    public $model;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['model', 'required'];
        $rules[] = ['model', 'validateModel', 'skipOnError'=>true,'message'=>'不整合があります'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        parent::afterFind();

        $this->initModel();
    }

    public function validateModel($attribute, $params)
    {
        if(! in_array($this->model->formName(), ['Product','RemedyStock','ComplexRemedyForm']))
           return false;

        return true;
    }

    public function beforeValidate()
    {
        $model = $this->model;

        $this->load($model->attributes,'');

        switch($model->formName())
        {
        case 'ComplexRemedyForm':
            break;

        case 'MachineRemedyForm':
            $this->potency_id = $model->potency_id;
            $this->vial_id    = $model->vial_id;
        case 'Product':
            $this->product_id = $model->product_id;
        case 'RemedyStock':
        default:
            $this->code = $model->code;
            break;
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if('ComplexRemedyForm' == $this->model->formName())
            return false;

        return parent::beforeSave($insert);
    }

    private function initModel()
    {
        $model = null;

        if($this->product_id)
        {
            $model = Product::findOne($this->product_id);
            $model->name = $this->name;
        }
        else
        {
            $vial = new RemedyStock(['remedy_id'  => $this->remedy_id,
                                     'potency_id' => $this->potency_id,
                                     'vial_id'    => $this->vial_id]);
            $drops = [];
            if($this->children)
                foreach($this->children as $child)
                    $drops[] = new RemedyStock([
                        'remedy_id'  => $child->remedy_id,
                        'potency_id' => $child->potency_id,
                        'vial_id'    => $child->vial_id,
                    ]);

            if($drops)
                $model = new ComplexRemedyForm(['vial'=>$vial,'drops'=>$drops]);
            else
                $model = $vial;
        }

        $this->model = $model;
    }

}
