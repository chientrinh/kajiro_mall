<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_summary_category".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SummaryCategory.php $
 * $Id: Category.php 2722 2016-07-15 08:38:22Z mori $
 *
 */
class SummaryCategory extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_summary_category';
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
            [['name', 'summary_category_id', 'company_id'], 'required'],
            [['summary_category_id', 'company_id', 'category_id'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasMany(Category::className(), ['category_id' => 'category_id']);
    }

}
