<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tmd_item_4_syohin_name".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/Potency.php $
 * $Id: Potency.php 2664 2016-07-06 08:36:09Z mori $
 *
 * @property integer $d_item_4_syohin_nameid
 * @property string $d_item_4_syohin_name
 * @property integer $dilution_seq
 *
 * @property TbldItem[] $tbldItems
 * @property TbldItemLog[] $tbldItemLogs
 * @property Tblfukuyo[] $tblfukuyos
 */
class Potency extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmd_item_4_syohin_name';
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
            [['d_item_4_syohin_name'], 'string'],
            [['dilution_seq'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'd_item_4_syohin_nameid' => 'id',
            'd_item_4_syohin_name' => 'name',
            'dilution_seq' => 'Dilution Seq',
        ];
    }

    public function getName()
    {
        return $this->d_item_4_syohin_name;
    }

}
