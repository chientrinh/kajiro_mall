<?php

namespace common\models;
use Yii;

/**
 * ComplexRemedy
 * 滴下レメディーを表現するための Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/MachineRemedyForm.php $
 * $Id: MachineRemedyForm.php 3197 2017-02-26 05:22:57Z naito $
 */

class MachineRemedyForm extends \yii\base\Model
{
    public $abbr1;
    public $abbr2;
    public $potency1;
    public $potency2;

    private $_pid;

    public function attributeLabels()
    {
        return [
            'abbr'     => "レメディー名",
            'potency'  => "ポーテンシー",
        ];
    }

    public function rules()
    {
        return [
            [['abbr1','potency1'], 'required'],
            [['abbr1','abbr2'],'string', 'min'=>2, 'max'=>32],
            [['potency1','potency2'],'exist','targetClass'=>RemedyPotency::className(),'targetAttribute'=>'potency_id'],
            ['abbr2',   'string', 'min'=>1,'when'=>function($model){ return $model->potency2; } ],
            ['potency2','integer','min'=>1,'when'=>function($model){ return $model->abbr2;    } ],
            [['product'],'required','skipOnError'=>true],
            [['abbr1','abbr2'],'match','not'=>true,'pattern'=>'/[\[\]:]/u','message'=>'レメディー名に記号を含むことはできません'],
            ['price','integer','min'=>1],
            ['product_id','exist','targetClass'=>Product::className()],
            ['product_id','in','range'=>[ Product::PKEY_MACHINE_REMEDY_COMB,
                                          Product::PKEY_MACHINE_REMEDY_30C,
                                          Product::PKEY_MACHINE_REMEDY_200C,
                                          Product::PKEY_MACHINE_REMEDY_1M,   ]],
        ];
    }

    public function getCode()
    {
        if($p = $this->getProduct())
            return $p->code;
    }

    public function getPotency_id()
    {
        return $this->potency2 ? RemedyPotency::COMBINATION : $this->potency1;
    }

    public function getVial_id()
    {
        return RemedyVial::SMALL_BOTTLE;
    }

    public function getProduct_id()
    {
        return $this->_pid;
    }

    public function setProduct_id($pid)
    {
        $this->_pid = $pid;
    }

    public function getPrice()
    {
        if($p = $this->getProduct())
            return $p->price;
    }

    public function getProduct()
    {
        if($this->hasErrors())
            return null;

        $p1 = RemedyPotency::findOne(['name'=>'30C' ]);
        $p2 = RemedyPotency::findOne(['name'=>'200C']);

        if($this->abbr2)
            $this->_pid = Product::PKEY_MACHINE_REMEDY_COMB;

        elseif($this->potency1 <= $p1->potency_id)
            $this->_pid = Product::PKEY_MACHINE_REMEDY_30C;

        elseif($this->potency1 <= $p2->potency_id)
            $this->_pid = Product::PKEY_MACHINE_REMEDY_200C;

        else
            $this->_pid = Product::PKEY_MACHINE_REMEDY_1M;

        return Product::findOne($this->_pid);
    }

    public function getName()
    {
        $name = '';

        if($this->product)
            $name .= $this->product->name;

        if($this->potency1)
            if($p = RemedyPotency::findOne($this->potency1))
                $name .= sprintf('[%s : %s]', $this->abbr1, $p->name);

        if($this->potency2)
            if($p = RemedyPotency::findOne($this->potency2))
                $name .= sprintf('[%s : %s]', $this->abbr2, $p->name);

        return $name;
    }

    public function setName($name)
    {
        if(! preg_match_all('/\[([^\[]+)\]/', $name, $match))
            return;

        $buf1 = explode(' : ', array_shift($match[1]));
        $buf2 = explode(' : ', array_shift($match[1]));

        if($buf1)
        {
            $this->abbr1 = array_shift($buf1);

            if($pmodel = RemedyPotency::findOne(['name'=>array_shift($buf1)]))
                $this->potency1 = $pmodel->potency_id;
        }
        if($buf2)
        {
            $this->abbr2 = array_shift($buf2);

            if($pmodel = RemedyPotency::findOne(['name'=>array_shift($buf2)]))
                $this->potency2 = $pmodel->potency_id;
        }
    }

    public function feed($params)
    {
        if(! is_array($params))
            return;

        foreach($params as $name => $value)
            if($this->canSetProperty($name))
                $this->$name = $value;
    }

/*
    public function convertToRecipeItem()
    {
        if(! $this->product)
            return null;

        return new RecipeItem([
            'product_id' => $this->product_id,
            'code'       => $this->product->code,
            'name'       => $this->name,
            'potency_id' => $this->potency_id,
            'vial_id'    => $this->vial_id,
        ]);
    }
*/
}
