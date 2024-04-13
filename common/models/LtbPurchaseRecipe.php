<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ltb_purchase_recipe".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/LtbPurchaseRecipe.php $
 * $Id: LtbPurchaseRecipe.php 2347 2016-03-31 08:50:59Z mori $
 *
 * @property integer $purchase_id
 * @property integer $recipe_id
 * @property string $created_at
 */
class LtbPurchaseRecipe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ltb_purchase_recipe';
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['purchase_id', 'recipe_id'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['purchase_id', 'recipe_id'], 'required'],
            [['purchase_id', 'recipe_id'], 'integer'],
            [['purchase_id'], 'exist', 'targetClass'=>Purchase::className() ],
            [['recipe_id'  ], 'exist', 'targetClass'=>Recipe::className()   ],
            [['created_at'], 'safe'],
            [['purchase_id', 'recipe_id'], 'unique', 'targetAttribute' => ['purchase_id', 'recipe_id'], 'message' => 'The combination of Purchase ID and Recipe ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'purchase_id' => 'Purchase ID',
            'recipe_id'   => 'Recipe ID',
            'created_at'  => 'Created At',
        ];
    }

    public function behaviors()
    {
        return [
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
        ];
    }
}
