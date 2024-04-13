<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_recipe_item".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RecipeItem.php $
 * $Id: RecipeItem.php 3197 2017-02-26 05:22:57Z naito $
 *
 * @property integer $recipe_id
 * @property string $code
 * @property string $name
 * @property integer $remedy_id
 * @property integer $potency_id
 * @property integer $vial_id
 * @property integer $quantity
 * @property integer $seq
 * @property integer $parent
 * @property string  $memo
 *
 * @property MtbRemedyVial $vial
 * @property DtbRecipe $recipe
 * @property MtbRemedyPotency $potency
 * @property MtbRemedy $remedy
 */
class RecipeItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_recipe_item';
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['recipe_id','seq'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['quantity', 'default', 'value' => 1 ],
            [['recipe_id', 'code', 'name', 'seq'], 'required'],
            [['recipe_id', 'remedy_id', 'potency_id', 'vial_id', 'quantity', 'seq', 'parent'], 'integer'],
            ['remedy_id', 'exist', 'targetClass' => Remedy::className(), 'targetAttribute'=>'remedy_id'],
            ['potency_id', 'exist', 'targetClass' => RemedyPotency::className(), 'targetAttribute'=>'potency_id'],
            ['vial_id', 'exist', 'targetClass' => RemedyVial::className(), 'targetAttribute'=>'vial_id'],
            ['instruct_id','exist','targetClass' => RecipeInstruction::className(), 'targetAttribute'=>'instruct_id'],
            [['code', 'name', 'memo'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'seq'       => 'NO',
            'name'      => '品名',
            'remedy_id' => 'Remedy ID',
            'potency'   => "ポーテンシー",
            'vial'      => '容器',
            'quantity'  => '個',
            'memo'      => 'メモ',
            'instruction'=> '目安',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(static::className(), ['parent' => 'seq'])->where(['recipe_id'=>$this->recipe_id]);
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        if(! $this->children)
            return $this->name;

        $names = \yii\helpers\ArrayHelper::getColumn($this->children, 'name');
        array_unshift($names, $this->name);

        return implode("\n", $names);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstruction()
    {
        return $this->hasOne(RecipeInstruction::className(), ['instruct_id' => 'instruct_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentItem()
    {
        return $this->hasOne(static::className(), ['seq' => 'parent'])->where(['recipe_id'=>$this->recipe_id]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecipe()
    {
        return $this->hasOne(Recipe::className(), ['recipe_id' => 'recipe_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPotency()
    {
        return $this->hasOne(RemedyPotency::className(), ['potency_id' => 'potency_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(),['product_id' => 'product_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRemedy()
    {
        return $this->hasOne(Remedy::className(), ['remedy_id' => 'remedy_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVial()
    {
        return $this->hasOne(RemedyVial::className(), ['vial_id' => 'vial_id']);
    }

    private function isChild()
    {
        return (null !== $this->parent);
    }

    private function isParent()
    {
        return (null === $this->parent);
    }

    public function beforeSave($insert)
    {
        if( $insert && $this->isChild() )
        {
            $this->name = preg_replace('/^([^+])/', '+$1', $this->name);
            $this->name = preg_replace('/滴下$/', '', $this->name);
        }

        return parent::beforeSave($insert);
    }

}
