<?php

namespace common\models;

use Yii;
use common\models\ProductMaster;
use common\models\Remedy;
use common\models\RemedyStock;

/**
 * This is the model class for table "mtb_remedy_vial".
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyVial.php $
 * $Id: RemedyVial.php 4051 2018-11-05 04:24:29Z mori $
 *
 * @property integer $vial_id
 * @property integer $unit_id
 * @property integer $volume
 * @property string $name
 *
 * @property RemedyPriceRangeItem[] $remedyPriceRangeItems
 * @property Unit $unit
 */
class RemedyVial extends \yii\db\ActiveRecord
{
    const MICRO_BOTTLE  = 1;  // プラスチック マイクロ瓶 (直径0.7cm x 天地3.0cm)
    const SMALL_BOTTLE  = 2;  // プラスチック小瓶 (直径1.18cm x 天地3.0cm)
    const MIDDLE_BOTTLE = 3;  // 旧プラスチック小瓶 (直径1.4cm x 天地3.1cm)
    const LARGE_BOTTLE  = 4;  // プラスチック大瓶 (横2.4cm x 縦4.7cm x 幅1.3cm)
    const GLASS_5ML     = 5;  // ガラス瓶(5ml)
    const GLASS_SPRAY_10ML    = 6;  // ガラススプレー(10ml)
    const GLASS_20ML    = 7;  // ガラス瓶(20ml)
    const GLASS_150ML   = 8;  // ガラス瓶(150ml)
    const GLASS_720ML   = 9;  // ガラス瓶(720ml)
    const DROP          = 10; // 滴下
    const PLASTIC_SPRAY_100ML   = 11;  // プラスチックスプレー(100ml)
    const PLASTIC_SPRAY_50ML   = 12;  // プラスチックスプレー(50ml)
    const PLASTIC_SPRAY_20ML   = 13;  // プラスチックスプレー(20ml)
    const ALP_20ML       = 14;  // アルポ（20ml）
    const ALP_100ML      = 15;  // アルポ（100ml）
    const ORIGINAL_20ML  = 16;  // オリジナル(20ml)
    const ORIGINAL_150ML = 17;  // オリジナル(150ml)


    public function getNickname()
    {
        if(in_array($this->vial_id,[self::SMALL_BOTTLE,self::MIDDLE_BOTTLE]))
            return '小';

        if($this->vial_id == self::LARGE_BOTTLE)
            return '大';

        if($this->vial_id == self::MICRO_BOTTLE)
            return 'マイクロ';

        if($this->vial_id == self::DROP)
            return '';

        if($this->vial_id == self::GLASS_5ML)
            return '5ml';

        if($this->vial_id == self::GLASS_20ML)
            return '20ml';

        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_vial';
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
            [['unit_id', 'volume', 'name'], 'required'],
            [['unit_id', 'volume'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'vial_id' => 'Vial ID',
            'unit_id' => 'Unit ID',
            'volume' => 'Volume',
            'name' => 'Name',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new RemedyVialQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRemedyPriceRangeItems()
    {
        return $this->hasMany(RemedyPriceRangeItem::className(), ['vial_id' => 'vial_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Unit::className(), ['unit_id' => 'unit_id']);
    }

    public static function getComposables()
    {
        return self::findAll([ self::SMALL_BOTTLE, self::LARGE_BOTTLE ]);
    }

    public static function isRemedySuger()
    {
        return [   // 砂糖玉
                   self::MICRO_BOTTLE,
                   self::SMALL_BOTTLE,
                   self::MIDDLE_BOTTLE,
                   self::LARGE_BOTTLE,
                   self::GLASS_5ML,
               ];
    }

    public static function isRemedy()
    {
        return [
                    RemedyVial::SMALL_BOTTLE,
                    RemedyVial::LARGE_BOTTLE,
                    RemedyVial::GLASS_5ML
                ];
    }

    public static function isRemedyMT()
    {
        return [   // マザーティンクチャー
                   self::GLASS_SPRAY_10ML,
                   self::GLASS_20ML,
                   self::GLASS_150ML,
                   self::GLASS_720ML,
                   self::PLASTIC_SPRAY_100ML,
                   self::PLASTIC_SPRAY_50ML,
                   self::PLASTIC_SPRAY_20ML,
                   self::ALP_100ML,
                   self::ORIGINAL_20ML,
                   self::ORIGINAL_150ML
               ];
    }

    public static function isRemedyFE()
    {
        return [self::GLASS_SPRAY_10ML]; // フラワーエッセンス
    }

    /**
     * 酒類と判定されるVialの配列を返す
     */
    public static function isLiquorVials()
    {
        return [
                    RemedyVial::GLASS_5ML,
                    RemedyVial::GLASS_SPRAY_10ML,
                    RemedyVial::GLASS_20ML,
                    RemedyVial::GLASS_150ML,
                    RemedyVial::GLASS_720ML,
                    RemedyVial::PLASTIC_SPRAY_100ML,
                    RemedyVial::PLASTIC_SPRAY_50ML
                ];
    }

    /**
     * 価格一括更新の対象除外条件
     * @param $var null | interger　| obuject common\models\ProductMaster | object common\models\RemedyStock
     * @return array | false | integer 引数がない場合は、除外対象のvial_idを配列で返し、
     *                                 引数があるが除外対象に含まれていない場合はfalseを返す
     *                                 引数があり、且つ除外対象に含まれている場合はそのIDを返す
     */
    public function isPriceUpdateExclusion($var = null)
    {
        $id = "";
        $excludeVials = [
                            RemedyVial::MIDDLE_BOTTLE, // 3
                            RemedyVial::GLASS_5ML,     // 5
                            RemedyVial::DROP           // 10
                        ];

        if (is_null($var))
            return $excludeVials;

        if (! is_object ($var)) {
            $id = (int)$var;
        } else if ($var instanceof RemedyStock || $var instanceof ProductMaster) {
            $id = $var->vial_id;
        } else if ($var->hasAttribute('vial_id')) {
            $id = $var->vial_id;
        }

        // $idに何も代入されていない場合、又は配列内に$idの値がない場合はfalseを返す
        if (empty($id) || ! in_array($id, $excludeVials))
            return false;

        return $excludeVials[$id];
    }
}

class RemedyVialQuery extends \yii\db\ActiveQuery
{
    public function drop($state = true)
    {
        if($state)
            return $this->andWhere(['vial_id' => RemedyVial::DROP]);
        else
            return $this->andWhere(['not',['vial_id' => RemedyVial::DROP]]);
    }

    public function tincture($state = true)
    {
        $vial_ids_mt = RemedyVial::isRemedyMT();

        if($state)
            return $this->andWhere(['in', 'vial_id', $vial_ids_mt]);
        else
            return $this->andWhere(['not in', 'vial_id', $vial_ids_mt]);
    }

    public function remedy($state = true)
    {
        if ('app-backend' == Yii::$app->id)
            $vial_ids_remedy = [
                    RemedyVial::SMALL_BOTTLE, // 小瓶
                    RemedyVial::LARGE_BOTTLE, // 大瓶
                    RemedyVial::MICRO_BOTTLE  // マイクロ瓶
                ];
        else
            $vial_ids_remedy = RemedyVial::isRemedy();


        if($state)
            return $this->andWhere(['in', 'vial_id', $vial_ids_remedy]);
        else
            return $this->andWhere(['not in', 'vial_id', $vial_ids_remedy]);
    }

    public function flower($status = true)
    {
        $vial_ids_fe = RemedyVial::isRemedyFE();
        if($status)
            return $this->andWhere(['in', 'vial_id', $vial_ids_fe]);

        return $this->andWhere(['not in', 'vial_id', $vial_ids_fe]);
    }
}
