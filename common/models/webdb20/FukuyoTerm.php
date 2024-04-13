<?php

namespace common\models\webdb20;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/FukuyoTerm.php $
 * $Id: FukuyoTerm.php 2664 2016-07-06 08:36:09Z mori $
 *
 * This is the model class for table "tmfukuyo_term".
 *
 * @property integer $fukuyo_termid
 * @property string $fukuyo_term
 * @property integer $fukuyo_term_cnt
 *
 * @property Tblfukuyo[] $tblfukuyos
 */
class FukuyoTerm extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tmfukuyo_term';
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
            [['fukuyo_term'], 'string'],
            [['fukuyo_term_cnt'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fukuyo_termid' => 'Fukuyo Termid',
            'fukuyo_term' => 'Fukuyo Term',
            'fukuyo_term_cnt' => 'Fukuyo Term Cnt',
        ];
    }

    public function getName()
    {
        return $this->fukuyo_term;
    }
}
