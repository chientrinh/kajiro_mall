<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_agency_rank".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/AgencyRank.php $
 * $Id: AgencyRank.php $
 */
class AgencyRank extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_agency_rank';
    }

    public function behaviors()
    {
        return [
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function () {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
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
            [['name', 'liquor_rate', 'remedy_rate', 'goods_rate', 'other_rate'], 'required'],
            [['rank_id', 'liquor_rate', 'remedy_rate', 'goods_rate', 'other_rate'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rank_id'     => 'ランクID',
            'name'        => 'ランク名',
            'liquor_rate' => '酒類割引率',
            'goods_rate'  => '雑貨割引率',
            'remedy_rate' => 'レメディー割引率',
            'other_rate'  => 'その他割引率'
        ];
    }
}

