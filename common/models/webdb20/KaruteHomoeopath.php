<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tmsyoho_homeopath".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/KaruteHomoeopath.php $
 * $Id: KaruteHomoeopath.php 1637 2015-10-11 11:12:30Z mori $
 *
 * @property integer $syoho_homeopathid
 * @property string $syoho_homeopath
 */
class KaruteHomoeopath extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmsyoho_homeopath';
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
            [['syoho_homeopath'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'syoho_homeopathid' => 'ホメオパスID',
            'syoho_homeopath'   => 'ホメオパス名',
        ];
    }

    public function getName()
    {
        return $this->syoho_homeopath;
    }
}
