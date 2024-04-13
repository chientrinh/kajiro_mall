<?php
namespace common\models\factory;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/factory/ProductCost.php $
 * $Id: ProductCost.php 2320 2016-03-27 07:55:17Z mori $
 *
 * This is the model class for table "product_cost".
 *
 * @property integer $cost_id
 * @property string $ean13
 * @property double $cost
 * @property string $name
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $start_date
 * @property string $end_date
 */
class ProductCost extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_cost';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('factory');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['start_date'], 'default', 'value' => new \yii\db\Expression('NOW()') ],
            [['end_date'  ], 'default', 'value' => '3000-12-31 00:00:00' ],
            [['ean13', 'cost', 'start_date', 'end_date'], 'required'],
            [['cost'],  'number', 'min'    =>   0],
            [['ean13'], 'string', 'length' =>  13],
            [['name'],  'string', 'max'    => 255],
            [['created_by', 'updated_by'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['created_by','updated_by'],'exist','targetClass'=>\backend\models\Staff::className(),'targetAttribute'=>'staff_id'],
        ];
    }

    public function behaviors()
    {
        return [
            'staff' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
            ],
            'date'  => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cost_id'    => 'Cost ID',
            'ean13'      => 'Ean13',
            'cost'       => 'Cost',
            'name'       => 'Name',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'start_date' => 'Start Date',
            'end_date'   => 'End Date',
        ];
    }

    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id'=>'created_by']);
    }

    public function getUpdator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id'=>'updated_by']);
    }
}
