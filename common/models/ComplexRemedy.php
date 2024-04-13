<?php

namespace common\models;
use Yii;

/**
 * ComplexRemedy
 * 滴下レメディーを表現するための Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ComplexRemedy.php $
 * $Id: ComplexRemedy.php 3197 2017-02-26 05:22:57Z naito $
 */

class ComplexRemedy extends \yii\base\Model
{
    const SCENARIO_PRESCRIBE = 'prescribe'; // ホメオパスが適用書を作る
    const SCENARIO_TAILOR    = 'tailor';    // エンドユーザが自分用のコンビネーションを作る

    public $vial;
    public $drops;
    public $maxDropLimit;
    public $qty;

    protected $recipeItem;

    public function init()
    {
        parent::init();
        
        if(! isset($this->vial))
            $this->vial = new RemedyStock(['prange_id' => 8]); // 滴下母体

        if(! isset($this->drops))
            $this->drops = [new RemedyStock()];

        if(! isset($this->maxDropLimit))
            $this->maxDropLimit = 2; // default value

        if(! isset($this->qty))
            $this->qty = 1;    // default value

    }

    public function attributeLabels()
    {
        return [
            'vial'     => "容器",
            'drops'    => "滴下",
            'qty'      => "数量",
            'price'    => "定価",
        ];
    }

    public function scenarios()
    {
        return [
            parent::SCENARIO_DEFAULT => self::attributes(),
            self::SCENARIO_TAILOR    => self::attributes(),
            self::SCENARIO_PRESCRIBE => self::attributes(),
        ];
    }

    public function rules()
    {
        return [
            ['maxDropLimit', 'default', 'value'=> 2 ],
            ['maxDropLimit', 'integer', 'min'=> 1, 'max'=>2, 'except'=>self::SCENARIO_PRESCRIBE ],
            ['maxDropLimit', 'integer', 'min'=> 1, 'max'=>6,     'on'=>self::SCENARIO_PRESCRIBE ],
            [['price','qty'], 'integer', 'min'=> 1,],
            [['vial','drops','price'],  'required'],
            ['vial',  'validateVial'],
            ['drops', 'validateDrops'],
        ];
    }

    public function beforeValidate()
    {
        if(! parent::beforeValidate())
            return false;

        if('app-frontend' == Yii::$app->id)
            if(! $this->recipeItem && $this->vial->vial_id <= \common\models\RemedyVial::MIDDLE_BOTTLE)
                $this->maxDropLimit = 2;

        return true;
    }

    public static function convertFromRecipeItem(\common\models\RecipeItem $parentItem)
    {
        $model = new self(['scenario'=>self::SCENARIO_PRESCRIBE]);

        $vial = new RemedyStock([
            'remedy_id'  => $parentItem->remedy_id,
            'potency_id' => $parentItem->potency_id,
            'vial_id'    => $parentItem->vial_id,
            'prange_id'  => 8, // 滴下母体
        ]);
        $model->vial = $vial;

        $drops = [];
        foreach($parentItem->children as $child)
        {
            $drop = new RemedyStock([
                'remedy_id'  => $child->remedy_id,
                'potency_id' => $child->potency_id,
                'vial_id'    => $child->vial_id,
            ]);
            $drops[] = $drop;
        }
        $model->drops = $drops;

        $model->recipeItem = $parentItem;

        return $model;
    }

    public function convertToRecipeItem($seq = 0, $options = [])
    {
        if(! $this->validate())
            return [];

        $items = [];

        if($this->vial->remedy_id === null)
            $this->vial->remedy_id = 0;

        // convert vial
        $items[] = new RecipeItem([
            'seq'       => $seq++,
            'remedy_id' => $this->vial->remedy_id,
            'vial_id'   => $this->vial->vial_id,
            'potency_id'=> $this->vial->potency_id,
            'code'      => $this->vial->code,
            'name'      => $this->vial->name,
            'parent'    => null,
        ]);

        // convert drops
        foreach($this->drops as $drop)
            $items[] = new RecipeItem([
                'seq'       => $seq++,
                'remedy_id' => $drop->remedy_id,
                'vial_id'   => $drop->vial_id,
                'potency_id'=> $drop->potency_id,
                'code'      => $drop->code,
                'name'      => $drop->name,
                'parent'    => $items[0]->seq,
            ]);

        // init options
        $recipe_id     = isset($options['recipe_id'])    ? $options['recipe_id']     : null;

        // apply options
        foreach($items as $i => $item)
        {
            $items[$i]->recipe_id  = $recipe_id;
            $items[$i]->product_id = null;
            $items[$i]->quantity   = $this->qty;
        }

        return $items;
    }

    public function dump()
    {
        $dump = [
            'vial'         => $this->vial->attributes,
            'drops'        => [],
            'maxDropLimit' => $this->maxDropLimit,
            'qty'          => $this->qty,
            'recipeItem'   => $this->recipeItem ? $this->recipeItem->attributes : null,
        ];
        //var_dump($this->drops);exit;
        foreach($this->drops as $drop)
            $dump['drops'][] = $drop->attributes;

        return $dump;
    }

    public function extend()
    {
        if($this->maxDropLimit <= count($this->drops))
            return;

        if(0 == count($this->drops))
        {
            $this->drops[] = new RemedyStock;
            return;
        }

        $this->drops[] = new RemedyStock;
    }

    public function getCharge()
    {
        return ($this->price * $this->qty);
    }

    public function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_HJ);
    }

    public function getId()
    {
        return md5(json_encode($this->dump()));
    }

    public function getImage()
    {
        return \common\models\ProductImage::DEFAULT_URL;
    }

    public function getName()
    {
        $name = $this->vial->name
             . "\n"
             . implode("\n", \yii\helpers\ArrayHelper::getColumn($this->drops, 'name'));

        return trim($name);
    }

    public function getPrice()
    {
        $price = $this->vial->price;
        foreach($this->drops as $drop)
            $price += $drop->getPrice();

        return $price;
    }

    public function getUrl()
    {
        return '';
    }

    /**
     * re-construct model from buffer stored per session
     * @return bool
     */
    public function feed($data)
    {
        if(! isset($data['vial']) && ! isset($data['drops']) && ! isset($data['maxDropLimit']))
            return false;

        if(array_key_exists('vial', $data))
        {
            $row = (object) $data['vial'];
            $this->vial->remedy_id  = $row->remedy_id;
            $this->vial->potency_id = $row->potency_id;
            $this->vial->prange_id  = $row->prange_id;
            $this->vial->vial_id    = $row->vial_id;
        }

        if(array_key_exists('maxDropLimit', $data))
            $this->maxDropLimit = $data['maxDropLimit'];

        if(array_key_exists('drops', $data))
            if(is_array($data['drops']))
            {
               $this->drops = [];

               foreach($data['drops'] as $row)
               {
                   $drop = new RemedyStock();
                   $drop->load([$drop->formName() => $row]);
                   $this->drops[] = $drop;
               }
            }

        if(array_key_exists('qty', $data))
            $this->qty = $data['qty'];

        if(array_key_exists('recipeItem', $data))
            $this->recipeItem = \common\models\RecipeItem::find()->where([
                'recipe_id' => $data['recipeItem']['recipe_id'],
                'seq'       => $data['recipeItem']['seq'],
            ])->one();

        return true;
    }

    public function isProduct()
    {
        return false;
    }

    public function isRemedy()
    {
        return true;
    }

    /**
     * re-construct model from http request params
     * @return bool
     */
    public function load($data, $formName = null)
    {
        parent::load($data, $formName);

        if(isset($data['Vial']))
        {
            $ean13 = \yii\helpers\ArrayHelper::getValue($data,'Vial.barcode','');
            if(13 == strlen($ean13) && ($model = RemedyStock::findByBarcode($ean13, true)))
                $this->vial = $model;
            else
            {
                $attr = \yii\helpers\ArrayHelper::getValue($data,'Vial');
                $this->vial->load($attr,'');
            }
        }

        if(! isset($data['Drops']))
            return true;

        $this->drops = [];
        foreach($data['Drops'] as $data)
        {
            if(isset($data['delete']))
                continue;

            $drop = new RemedyStock(['vial_id'=>10]);
            
            if(isset($data['potency_id']))
                $drop->potency_id = $data['potency_id'];

            if(isset($data['abbr']))
            {
                $remedy = Remedy::findOne(['abbr'=>$data['abbr']]);
                if($remedy)
                    $drop->remedy_id = $remedy->remedy_id;
            }
            $this->drops[] = $drop;
        }

        return true;
    }

    public function validateDrops($attribute, $params)
    {
        if((! $this->drops) || (0 == count($this->drops)))
        {
            $this->addError($attribute, '滴下がありません');
            return false;
        }

        if(!$this->recipeItem && $this->maxDropLimit < count($this->drops))
        {
            $this->addError($attribute, sprintf('その容器(%s)で滴下できるのは %d 点までです', $this->vial->name,$this->maxDropLimit) );
            return false;
        }

        foreach($this->drops as $i => $drop)
        {
            $model = new \yii\base\DynamicModel($drop->attributes);
            $model->addRule(['remedy_id'], 'required', ['message'=>sprintf("滴下 %d のレメディーを指定してください",++$i)]);
            $model->addRule(['potency_id'], 'required', ['message'=>sprintf("滴下 %d のポーテンシーを指定してください",$i)]);
            $model->addRule(['remedy_id','potency_id','vial_id'], 'exist', ['targetClass'=>RemedyStock::className()]);
            if(! $model->validate())
                $this->addError($attribute, array_shift($model->getFirstErrors()));
            elseif(! RemedyStock::find()->where([
                'vial_id'   => $drop->vial_id,
                'remedy_id' => $drop->remedy_id,
                'potency_id'=> $drop->potency_id,
                'in_stock'  => 1,
            ])->one())
                $this->addError($attribute, sprintf('滴下 %d の在庫がありません', $i));
        }

        if(! $this->hasErrors($attribute))
        {
            $rid = \yii\helpers\ArrayHelper::getColumn($this->drops,'remedy_id');
            $rid = array_unique($rid);
            $rows = [];
            foreach($rid as $r){ $rows[$r] = []; }

            foreach($this->drops as $i => $drop)
            {
                if(in_array($drop->potency_id, $rows[$drop->remedy_id]))
                    $this->addError($attribute, sprintf('滴下 %d が重複しています', ++$i));
                else
                    $rows[$drop->remedy_id][] = $drop->potency_id;
            }
        }

        return $this->hasErrors($attribute);
    }

    public function validateVial($attribute, $params)
    {
        if(! $this->vial)
            $this->addError($attribute, '容器がありません');

        $model = new \yii\base\DynamicModel($this->vial->attributes);
        $model->addRule(['vial_id','prange_id'], 'required', ['message' => "容器を指定してください"]);
        $model->addRule(['vial_id'], 'in', ['not'=>true, 'range'=>[10]]);
        $model->addRule(['vial_id','prange_id'], 'exist', ['targetClass'=>RemedyPriceRangeItem::className()]);
        $model->addRule(['remedy_id','potency_id','vial_id','prange_id'], 'integer');
        $model->addRule(['remedy_id','potency_id'], 'default', ['value'=> 0]);

        if($this->scenario == self::SCENARIO_TAILOR)
            $model->addRule(['remedy_id','potency_id'], 'integer', ['min'=> 0, 'max'=> 0]);

        if($this->vial->remedy_id)
        {
            $model->addRule(['remedy_id','potency_id'], 'required');
            $model->addRule(['vial_id'], 'in', ['range'=>[7], ]); // ガラス瓶(20ml) 
            $model->addRule(['remedy_id','potency_id','vial_id','prange_id'], 'exist', ['targetClass'=>RemedyStock::className()]);
            if(! RemedyStock::find()->where([
                'vial_id'   => $this->vial->vial_id,
                'remedy_id' => $this->vial->remedy_id,
                'potency_id'=> $this->vial->potency_id,
                'prange_id' => $this->vial->prange_id,
                'in_stock'  => 1,
            ])->one())
                $this->addError($attribute, '容器の在庫がありません');
        }

        if(! $model->validate())
            $this->addError($attribute, array_shift($model->getFirstErrors()));
    }

}
