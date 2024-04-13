<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_sales_category_3".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SalesCategory3.php $
 * $Id: SalesCategory3.php 2795 2020-02-07 11:55:11Z kawai $
 *
 * @property integer $bunrui_id
 * @property string  $bunrui_code3
 * @property string  $name
 * @property string  $bunrui_code2
 *
 */

class SalesCategory3 extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_sales_category_3';
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
            [['bunrui_id', 'bunrui_code3', 'name', 'bunrui_code2'], 'required'],
            [['bunrui_id'], 'integer'],
            [['bunrui_code3'], 'string', 'max' => 4],
            [['bunrui_code2'], 'string', 'max' => 2],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bunrui_id'  => "分類ID",
            'bunrui_code3'       => "分類コード",
            'name'       => "名称",
            'bunrui_code2'       => "中分類",
        ];
    }
}
