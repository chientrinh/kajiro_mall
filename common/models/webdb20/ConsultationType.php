<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tmsodan_kind".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/ConsultationType.php $
 * $Id: ConsultationType.php 1637 2015-10-11 11:12:30Z mori $
 *
 * @property integer $sodan_kindid
 * @property string $sodan_kind
 */
class ConsultationType extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmsodan_kind';
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
            [['sodan_kind'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sodan_kindid' => '種別ID',
            'sodan_kind'   => '種別名',
        ];
    }

    public function getName()
    {
        return $this->sodan_kind;
    }

}
