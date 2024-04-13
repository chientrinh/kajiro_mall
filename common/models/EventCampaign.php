<?php

namespace common\models;

use Yii;
// use \common\components\ean13\CheckDigit;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/EventCampaign.php $
 * $Id: $
 *
 */
class EventCampaign extends \yii\db\ActiveRecord
{
    public $statuses;
    // public $status = 1; // ステータスの初期値は「1:有効」
    public $prefix; //d:値引 , p:ポイント
    public $types;

    const DISCOUNT = 1;
    const POINT = 2;

    public function init()
    {
        parent::init();

        if ($this->isNewRecord && ! $this->campaign_code)
            $this->campaign_code = $this->setCampaignCode();
    }
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_event_campaign';
    }

    public static function primaryKey()
    {
        return ['ecampaign_id'];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'   => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => function ($event) { return new \yii\db\Expression('NOW()'); },
            ],
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
            [['campaign_code', 'start_date', 'end_date', 'subcategory_id'], 'required'],
            [['campaign_code'], 'string', 'max' => 255],
            [['subcategory_id', 'subcategory_id2'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ecampaign_id'     => 'ID',
            'campaign_code'    => 'キャンペーンコード',
            'subcategory_id'   => 'サブカテゴリ',
            'subcategory_id2'  => 'サブカテゴリ2',
            'campaign_name'    => '名称',
            'start_date'       => '利用開始日時',
            'end_date'         => '利用終了日時',
            'status'           => '有効/無効',
            'branch_id'        => '拠点',
            'branch'           => '拠点',
            'create_by'        => '作成者',
            'create_date'      => '作成日時',
            'update_by'        => '更新者',
            'update_date'      => '更新日時',
        ];
    }

    public function attributeHints()
    {
        return [
        ];
    }

    protected function setCampaignCode()
    {
        $code = $this->makeRandStr();
        while (Campaign::find()->andWhere(['campaign_code' => $code])->one()) {
            $code = $this->makeRandStr();
        }
        
        return $code;
    }

    /**
     * ランダム文字列生成 (英数字)
     * $length: 生成する文字数
     */
    protected function makeRandStr($length = 8) 
    {
        $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
        $r_str = null;
        for ($i = 0; $i < $length; $i++) {
            $r_str .= $str[rand(0, count($str) - 1)];
        }
        return $r_str;
    }

    /* @inheritdoc */
    public static function find()
    {
        return new EventCampaignQuery(get_called_class());
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcategory()
    {
        return $this->hasOne(Subcategory::className(), ['subcategory_id' => 'subcategory_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcategory2()
    {
        return $this->hasOne(Subcategory::className(), ['subcategory_id' => 'subcategory_id2']);
    }
}

/**
 * ActiveQuery for ProductMaster
 */
class EventCampaignQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function active()
    {
        $today = date('Y-m-d H:i:s', time());

        return $this->andWhere('start_date <= :start_date', [':start_date' => $today])
                    ->andWhere('end_date >= :end_date', [':end_date' => $today]);
    }
}
