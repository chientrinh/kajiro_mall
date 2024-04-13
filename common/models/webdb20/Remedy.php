<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tmd_item_4_syohin_name".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/Remedy.php $
 * $Id: Remedy.php 2664 2016-07-06 08:36:09Z mori $
 *
 * @property integer $d_item_1_syohin_nameid
 * @property string $d_item_1_syohin_name
 * @property string $remedy_name_formal
 * @property string $remedy_name_formal_yomigana
 * @property string $remedy_name
 * @property string $remedy_name_japanese
 * @property string $prod_name_yomigana
 * @property integer $is_tanpin_flg
 * @property integer $is_sake_flg
 * @property string $web_template
 * @property string $catch_copy
 * @property integer $remedy_category
 *
 * @property TbldItem[] $tbldItems
 * @property TbldItemLog[] $tbldItemLogs
 * @property TmlotKind[] $tmlotKinds
 */
class Remedy extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmd_item_1_syohin_name';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb20');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['d_item_1_syohin_name', 'remedy_name_formal', 'remedy_name_formal_yomigana', 'remedy_name', 'remedy_name_japanese', 'prod_name_yomigana', 'web_template', 'catch_copy'], 'string'],
            [['is_tanpin_flg', 'is_sake_flg', 'remedy_category'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'd_item_1_syohin_nameid' => 'D Item 1 Syohin Nameid',
            'd_item_1_syohin_name' => 'D Item 1 Syohin Name',
            'remedy_name_formal' => 'Remedy Name Formal',
            'remedy_name_formal_yomigana' => 'Remedy Name Formal Yomigana',
            'remedy_name' => 'Remedy Name',
            'remedy_name_japanese' => 'Remedy Name Japanese',
            'prod_name_yomigana' => 'Prod Name Yomigana',
            'is_tanpin_flg' => 'Is Tanpin Flg',
            'is_sake_flg' => 'Is Sake Flg',
            'web_template' => 'Web Template',
            'catch_copy' => 'Catch Copy',
            'remedy_category' => 'Remedy Category',
        ];
    }

    public function getName()
    {
        return $this->d_item_1_syohin_name;
    }

}
