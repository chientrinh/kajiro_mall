<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_delivery_time".
 *
 * @property integer $time_id
 * @property string $name
 * @property string $provider
 */
class DeliveryTime extends \yii\db\ActiveRecord
{
    const PROVIDER_YAMATO = 'yamato';
    const PROVIDER_SAGAWA = 'sagawa';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_delivery_time';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['time_id','name', 'provider'], 'required'],
            [['provider'], 'match', 'pattern'=> sprintf('/(%s|%s)/', self::PROVIDER_YAMATO, self::PROVIDER_SAGAWA)],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name'     => '時間帯',
        ];
    }

    public function getId()
    {
        return $this->primaryKey;
    }

    /* @return int timestamp */
    public function getLapse()
    {
        $oclock = 23; // defaults to midnight eleven oclock
        
        if(preg_match('/^午前/', $this->name))
            $oclock = 9;
        elseif(preg_match('/^\d+/', $this->name, $match))
            $oclock = array_shift($match);

        return ($oclock * 60 * 60);
    }

    public function getSagawa()
    {
        return $this->find()->where(['provider'=>self::PROVIDER_SAGAWA])->all();
    }

    public function getYamato()
    {
        // ヤマトが12時〜14時指定を廃止した。time_id 2を除外したいがTBLから削除すると過去データに影響が及ぶリスクがあるため、Modelからの除外で対応　2017/06/15
        return $this->find()->where(['provider'=>self::PROVIDER_YAMATO])->andWhere('time_id != 2')->all();
    }
}
