<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tmdenpyo_center".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/DenpyoCenter.php $
 * $Id: DenpyoCenter.php 1637 2015-10-11 11:12:30Z mori $
 *
 * @property integer $denpyo_centerid
 * @property string $denpyo_center
 */
abstract class DenpyoCenter extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmdenpyo_center';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['denpyo_center'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'denpyo_centerid' => '拠点ID',
            'denpyo_center'   => '拠点名',
        ];
    }

    public function getName()
    {
        return $this->denpyo_center;
    }
}
