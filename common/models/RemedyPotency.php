<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyPotency.php $
 * $Id: RemedyPotency.php 3864 2018-05-01 08:18:08Z mori $
 */

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_remedy_potency".
 *
 * @property integer $potency_id
 * @property string $name
 * @property integer $weight
 */
class RemedyPotency extends \yii\db\ActiveRecord
{
    const COMBINATION = 1;
    const MT          = 2;
    const FE          = 55;
    const FE2         = 56;
    const JM          = 58;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_potency';
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
            [['name'], 'required'],
            [['weight'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'potency_id' => 'Potency ID',
            'name'   => 'Name',
            'weight' => 'Weight',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new RemedyPotencyQuery(get_called_class());
    }

    // this is referred from backend/controllers/RemedyController or some else
    public static function foreveryone()
    {
        return self::find()->where(['between','potency_id',1, 24 ])->all();
    }
}

class RemedyPotencyQuery extends \yii\db\ActiveQuery
{
    public function tincture($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::MT]);
        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::MT]]);
    }

    // not referred from anywhere so far 2015.07.20
    /*
    public function foreveryone($state = true)
    {
        if($state)
            return $this->andWhere(['between','potency_id',1, 24 ]);
        else
            return $this->andWhere(['not',['between','potency_id',1, 24 ]]);
    }
    */

    public function flower($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::FE]);
        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::FE]]);
    }

    public function flower2($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::FE2]);
        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::FE2]]);
    }

    public function flowers($state = true)
    {
        $condition = ['potency_id' => [RemedyPotency::FE, RemedyPotency::FE2]];

        if($state)
            return $this->andWhere($condition);
        else
            return $this->andWhere(['not', $condition]);
    }

    public function remedy()
    {
        if ('app-backend' == Yii::$app->id)
            // コンビネーション、12X、9C、12C、30C、200C
            $ids = [RemedyPotency::COMBINATION, 7, 11, 12, 15, 19];
        else
            $ids = [RemedyPotency::COMBINATION];

        return $this->andWhere(['potency_id' => $ids]);
    }

    public function jm($state = true)
    {
        if($state)
            return $this->andWhere(['potency_id' => RemedyPotency::JM]);
        else
            return $this->andWhere(['not', ['potency_id' => RemedyPotency::JM]]);
    }
}
