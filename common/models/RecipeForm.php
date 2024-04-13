<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RecipeForm.php $
 * $Id: RecipeForm.php 3946 2018-06-22 04:07:17Z mori $
 *
 */
class RecipeForm extends Recipe
{
    public $items    = null;

    public function init()
    {
        parent::init();

        if(null === $this->items)
            $this->items = [];
    }

    public function afterFind()
    {
        parent::afterFind();

        $this->items = RecipeItemForm::findAll(['recipe_id'=>$this->recipe_id,'parent'=>null]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['items', 'required','message'=>"レメディーが選択されていません"];
        $rules[] = ['items', 'validateItems','message'=>"レメディーに不整合があります"];

        return $rules;
    }

    public function validateItems($attribute, $params)
    {
        foreach($this->items as $item)
            if(! $item->validate())
                return false;
        
        return true;
    }

    public function getParentItems()
    {
        return $this->items;
    }

    public function addItem($model)
    {
        $item = new RecipeItemForm([
            'model'    => $model,
            'name'     => $model->name,
            'quantity' => 1,
        ]);

        foreach($this->items as $k => $i)
        {
            if($i->model->attributes !== $model->attributes)
                continue;

            $this->items[$k]->quantity++;
            return true;
        }

        $this->items[] = $item;
        return true;
    }

    public function dump()
    {
        $items = [];
        foreach($this->items as $item)
            $items[] = $this->dumpItem($item);

        $dump = $this->attributes;
        $dump['items']    = $items;
        $dump['manual_client_age_disp'] = $this->computeAge($this->manual_client_birth); 
        $dump['manual_protector_age_disp'] = $this->computeAge($this->manual_protector_birth); 

        return $dump;
    }

    private function dumpItem($item)
    {
        if($item->canGetProperty('model'))
            $model = $item->model;
        else
            $model = $item;

        $dump = [
            'formName'   => $model->formName(),
            'attributes' => $model->attributes,
            'quantity'   => $item->quantity,
            'instruct_id'=> $item->instruct_id,
            'memo'       => $item->memo,
        ];
        if('ComplexRemedyForm' == $model->formName())
            $dump['attributes'] = $model->dump();

        return $dump;
    }

    public function feed($dump)
    {
        $recipe_id = $dump['recipe_id'] ? $dump['recipe_id'] : null;
        $items = [];

        if(isset($dump['items']))
        foreach($dump['items'] as $k => $item)
        {
            $formName = ArrayHelper::getValue($item, 'formName');
            switch($formName)
            {
            case 'Product':
                $model = new \common\models\Product($item['attributes']);
                break;

            case 'RemedyStock':
                $model = new \common\models\RemedyStock($item['attributes']);
                break;

            case 'ComplexRemedyForm':
                $model = new \common\components\cart\ComplexRemedyForm([
                    'scenario'     => 'prescribe',
                    'maxDropLimit' => 5,
                ]);
                $model->feed($item['attributes']);
                break;

            case 'MachineRemedyForm':
                $model = new \common\models\MachineRemedyForm();
                $model->load($item['attributes'],'');
                break;

            default:
                break;
            }
            $items[] = new RecipeItemForm([
                'model'      => $model,
                'recipe_id'  => $recipe_id,
                'name'       => $model->name,
                'quantity'   => $item['quantity'],
                'instruct_id'=> $item['instruct_id'],
                'memo'       => $item['memo'],
            ]);
        }

        $this->items = $items;

        foreach($dump as $attr => $value)
            if($this->hasAttribute($attr))
                $this->$attr = $value;

        if($this->client)
        {
            $target = $this->client->parent ? $this->client->parent : $this->client;

            $attr = $target->getAttributes([
                'customer_id',
                'name01','name02','kana01','kana02',
                'zip01','zip02','pref_id','addr01','addr02',
                'tel01','tel02','tel03'
            ]);
        }

        if(isset($dump['note']))
            $this->note = $dump['note'];
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            if(! parent::save($runValidation, $attributeNames) )
                throw new \yii\db\Exception('failed to save dtb_recipe');

            $recipe_id = $this->recipe_id;
            RecipeItem::deleteAll(['recipe_id' => $recipe_id]);

            $seq = 0;
            foreach($this->items as $k => $item)
            {
                $item->recipe_id = $recipe_id;
                $item->seq = $seq++;

                if(! $item->save())
                {
                    if('ComplexRemedyForm' == $item->model->formName())
                    {
                        $rows = $item->model->convertToRecipeItem($seq, ['recipe_id'=>$recipe_id]);
                        foreach($rows as $row)
                        {
                            $row->instruct_id = $item->instruct_id;
                            $row->memo        = $item->memo;
                            $row->quantity    = $item->quantity;
                            if(! $row->save())
                            {
                                Yii::error($row->attributes);
                                Yii::error($row->errors);
                                throw new \yii\db\Exception('failed to save dtb_recipe_item of ComplexRemedyForm');
                            }
                        }
                        $seq = end($rows)->seq + 1;
                    }
                    else
                    {
                        Yii::error($item->errors);
                        throw new \yii\db\Exception('failed to save dtb_recipe_item');
                    }
                }
            }
        }
        catch (\yii\db\Exception $e)
        {
            Yii::warning($e->__toString(), $this->className().'::'.__FUNCTION__);

            $transaction->rollBack();
            return false;
        }
        $transaction->commit();
        return true;
    }
}
