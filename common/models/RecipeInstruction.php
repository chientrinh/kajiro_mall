<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_recipe_instruction".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RecipeInstruction.php $
 * $Id: RecipeInstruction.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $instruct_id
 * @property string $name
 *
 * @property DtbRecipeItem[] $dtbRecipeItems
 */
class RecipeInstruction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_recipe_instruction';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'instruct_id' => 'Instruct ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDtbRecipeItems()
    {
        return $this->hasMany(DtbRecipeItem::className(), ['instruct_id' => 'instruct_id']);
    }
}
