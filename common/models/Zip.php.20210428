<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_zip".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Zip.php $
 * $Id: Zip.php 4087 2018-12-20 07:03:47Z kawai $
 *
 * @property integer $region
 * @property string $zipcode
 * @property integer $pref_id
 * @property string $city
 * @property string $town
 *
 * @property MtbPref $pref
 */

class Zip extends \yii\db\ActiveRecord
{
    const PROVIDER_YAMATO = 'yamato_22';
    const PROVIDER_SAGAWA = 'sagawa_22';
    const DELEVERY_10     = 10;
    const DELEVERY_15     = 15;
    const DELIVERY_20     = 20;
    const SCENARIO_TY     = 'ty';
    const DAY_DEFAULT     = 7; // 通常の選択可能日数
    const DAY_10_15       = 3; // yamato_22・sagawa_22の値が10・15の場合
    const DAY_20_25       = 4; // yamato_22・sagawa_22の値が20・25の場合

    public static function primaryKey()
    {
        return ['region','zipcode','pref_id','city','town'];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_zip';
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
            [['region', 'zipcode', 'pref_id', 'city'], 'required'],
            [['region', 'pref_id'], 'integer'],
            [['zipcode'], 'string', 'length' => 7],
            [['city', 'town'], 'string', 'max' => 32],
            [['sagawa_22','yamato_22'], 'in', 'range'=> [10, 15, 20, 25, 30] ],
            [['spat'], 'integer', 'min' => 0, 'max' => 1 ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'region'  => '地域コード',
            'zipcode' => '〒',
            'pref_id' => '都道府県',
            'city'    => '市区町村',
            'town'    => '町域',
            'yamato_22'=> '宅急便・静岡県からの所要日数',
            'sagawa_22'=> '佐川便・静岡県からの所要日数',
            'spat'     => '宅急便・タイムサービス「S‐PAT」可否',
        ];
    }

    public function attributeHints()
    {
        return [
            'region'  => '郵政省が定めるコード',
            'zipcode' => 'ハイフンなし数字７桁',
            'yamato_22'=> '10:１日、15:１日半、30:３日',
            'sagawa_22'=> '10:１日、15:１日半、30:３日',
        ];
    }

    /**
     * search zipcode from address
     * @return null or Zip model
     */
    public static function addr2zip($pref_id, $addr01)
    {
        return self::find()->where(['pref_id' => $pref_id, 'CONCAT(city,town)' => $addr01])->one();
    }

    public function getMinDelivTimestamp($provider = self::PROVIDER_YAMATO, $time = null)
    {
        if(null === $time)
            $time = time();

        if(! in_array($provider, [self::PROVIDER_YAMATO, self::PROVIDER_SAGAWA]))
            throw new \yii\base\UnknownPropertyException(sprintf('unknown property Zip::%s', $provider));

        if(! $data = $this->$provider)
             $data = self::find()->max($provider);

        $_24h = (60 * 60 * 24);
        // 郵便番号から、宅急便・静岡県からの所要日数を時間に変換して、現在日時に加算する（20なら24時間＊２というのが、下記の計算式）
        $min_time = $time + ($_24h * floor($data / 10));

        // TODO: 2020/09/17以降はこの処理に統一され、土曜休業対応はなくなる 2020/09/16 kawai
        if (time() >= strtotime('2020-09-17 21:00:00')) {
            if(12 <= (int) date('H', $time)) // it is afternoon
            {
                $min_time += $_24h;

                // 土曜日
                if('Sat' == date('D', $time))
                    $min_time += $_24h;
            }
            // 日曜の場合
            elseif('Sun' == date('D', $time))
                $min_time += $_24h;
        } else {
        // 土曜日休業対応。土曜日の場合、さらに２日加算する
	        if('Sat' == date('D', $time))
	            $min_time += $_24h*2;
	       
	        // 日曜の場合は従来通り
	        elseif('Sun' == date('D', $time))
	            $min_time += $_24h;
	
	        elseif(12 <= (int) date('H', $time)) // it is afternoon
	        {
	            $min_time += $_24h;
	
	            // 土曜日休業対応。金曜午後なら、さらに２日加算する
	            if('Fri' == date('D', $time)) {
	                // 2018-12-28午後では未適用とする
	                if('2018-12-28' != date('Y-m-d', $time)) {
	                    $min_time += $_24h*2;
	                }
	            }
	
	        }

        }

        if(0 == ($this->$provider % 10))
            $min_date = date('Y-m-d 09:00:00', $min_time);
        else
            $min_date = date('Y-m-d 14:00:00', $min_time);
        return strtotime($min_date);
    }

    /**
     * 配送日時の最長選択可能日を算出
     * @param string    $provider     配送業者名
     * @param timestamp $time         配送日時の算出開始日時（デフォルトは現在日時）
     * @param number    $seletableDay 選択可能日数（7 | 3 | 4）
     * @return timestamp 最長選択可能日時
     */
    public function getMaxdelivTimeStamp($provider = self::PROVIDER_YAMATO, $time = null, $selectableDay)
    {
        // 配送業者がヤマトと佐川以外の場合は例外処理を行う
        if(! in_array($provider, [self::PROVIDER_YAMATO, self::PROVIDER_SAGAWA]))
            throw new \yii\base\UnknownPropertyException(sprintf('unknown property Zip::%s', $provider));

        if(null === $time)
            $time = time();

        $max_date = $time + ($selectableDay * (60 * 60 * 24));
        return strtotime(date('Y-m-d 21:00:00', $max_date));
        
    }

    public function getMinDelivTimestampSagawa($time = null)
    {
        return $this->getMindelivTimeStamp(self::PROVIDER_SAGAWA, $time);
    }

    public function getMinDelivTimestampYamato($time = null)
    {
        return $this->getMindelivTimeStamp(self::PROVIDER_YAMATO, $time);
    }

    public function getMaxDelivTimestampSagawa($time, $selectableTime = null)
    {
        return $this->getMaxdelivTimeStamp(self::PROVIDER_SAGAWA, $time, $selectableTime);
    }

    public function getMaxDelivTimestampYamato($time, $selectableTime = null)
    {
        return $this->getMaxdelivTimeStamp(self::PROVIDER_YAMATO, $time, $selectableTime);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        return $this->hasOne(Pref::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return string
     */
    public function getZip01()
    {
        $start  = 0;
        $length = 3;

        return substr($this->zipcode, $start, $length);
    }

    /**
     * @return string
     */
    public function getZip02()
    {
        $start  = 3;
        $length = 4;

        return substr($this->zipcode, $start, $length);
    }

    /**
     * search address from zip code
     * @return false or array of address
     */
    public static function zip2addr($zip01, $zip02)
    {
        $zip   = sprintf('%03d%04d', $zip01, $zip02);
        $model = self::findAll(['zipcode'=>$zip]);
        if(! $model)
            return false;

        $candidate = [
            'pref_id' => $model[0]->pref_id,
            'addr01'  => [],
        ];

        foreach($model as $m)
        {
            $candidate['addr01'][] = $m->city . $m->town;
        }

        return (object) $candidate;
    }

    /**
     * ticket:677対応(フロント・クール宅急便お届け日指定（六本松）)で追加
     * mtb_zipのyamato_22又は、sagawa_22の値に応じてセレクトできる日数を指定する。
     * 
     * @return integer 選択可能日数
     */
    public function getDays($scenario = null)
    {
        // ticket: 677 フロント・クール宅急便お届け日指定（六本松）の修正
        if (! $scenario || ($scenario != self::SCENARIO_TY))
            return self::DAY_DEFAULT;

        if(isset($this->yamato_22) && $this->yamato_22 < self::DELIVERY_20)
            return self::DAY_10_15;
        

        return self::DAY_20_25;
    }

}
