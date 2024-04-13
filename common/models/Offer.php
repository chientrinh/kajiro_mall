<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_offer".
 *
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/Offer.php $
 * @version $Id: Offer.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $category_id
 * @property integer $grade_id
 * @property integer $discount_rate
 * @property integer $point_rate
 *
 * @property MtbCustomerGrade $grade
 * @property MtbCategory $category
 */
class Offer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_offer';
    }

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
            [['category_id', 'grade_id'], 'required'],
            [['category_id', 'grade_id', 'discount_rate', 'point_rate'], 'integer'],
            [['discount_rate','point_rate'],'integer','min'=>0,'max'=>100],
            [['category_id',],'exist', 'targetClass'=>\common\models\Category::className()],
            [['grade_id',   ],'exist', 'targetClass'=>\common\models\CustomerGrade::className()],
            [['category_id', 'grade_id'], 'unique', 'targetAttribute' => ['category_id', 'grade_id'], 'message' => 'The combination of Category ID and Grade ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'category_id'   => 'カテゴリー',
            'grade_id'      => '会員区分',
            'discount_rate' => '値引き率',
            'point_rate'    => 'ポイント率',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrade()
    {
        return $this->hasOne(CustomerGrade::className(), ['grade_id' => 'grade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
    }
}
