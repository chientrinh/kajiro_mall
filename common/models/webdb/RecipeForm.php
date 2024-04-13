<?php

namespace common\models\webdb;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/RecipeForm.php $
 * $Id: RecipeForm.php 1649 2015-10-12 15:13:57Z mori $
 *
 */
class RecipeForm extends \common\models\RecipeForm
{
    public $items;
    public $delivery;

    public function init()
    {
        parent::init();

        $this->items = [];
        $this->delivery = null;

        $this->detachBehavior('pkey');
        $this->detachBehavior('pw');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['recipe_id','pw'], 'required'],
            ['recipe_id', 'unique'],
            ['recipe_id', 'integer', 'max'=> -1 ],
            ['pw', 'string', 'min' => 4, 'skipOnEmpty'=>true],
            [['homoeopath_id', 'client_id', 'staff_id', 'status'], 'integer'],
            ['staff_id', 'exist', 'targetClass' => \backend\models\Staff::className(), 'targetAttribute'=>'staff_id'],
            [['homoeopath_id','client_id'], 'exist', 'targetClass' => \common\models\Customer::className(), 'targetAttribute'=>'customer_id'],
            ['status', 'default', 'value' => self::STATUS_INIT ],
            ['status', 'in', 'range' => [
                self::STATUS_INIT,
                self::STATUS_SOLD,
                self::STATUS_EXPIRED,
                self::STATUS_CANCEL,
                self::STATUS_VOID,
            ]],
            [['note'], 'string', 'max' => 255],
        ];
    }

}
