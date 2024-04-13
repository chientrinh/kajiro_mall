<?php

namespace common\models;

use Yii;
// use \common\components\ean13\CheckDigit;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Campaign.php $
 * $Id: $
 *
 */
class Campaign extends \yii\db\ActiveRecord
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
        $this->statuses = Campaign::getStatus();
        $this->types = Campaign::getCampaignType();

        if ($this->isNewRecord && ! $this->campaign_code)
            $this->campaign_code = $this->setCampaignCode();

        if ($this->isNewRecord && ! $this->status)
            $this->status = 1;
        
        if ($this->isNewRecord && ! $this->campaign_type)
            $this->campaign_type = 1;

        if ($this->isNewRecord && ! $this->prefix)
            $this->prefix = $this->setPrefix('d');

    }
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_campaign';
    }

    public static function primaryKey()
    {
        return ['campaign_id'];
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
            [['campaign_code', 'campaign_type', 'campaign_name', 'start_date', 'end_date', 'status', 'branch_id'], 'required'],
            [['campaign_code', 'campaign_name'], 'string', 'max' => 255],
            ['campaign_type', 'default', 'value' => 1],
            ['status', 'default', 'value' => 1],
            ['streaming_id', 'default', 'value' => null],
            // ['campaign_code', 'default', 'value' => function() { return makeRandStr(8); }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'campaign_id'      => 'ID',
            'campaign_code'    => 'キャンペーンコード',
            'campaign_type'    => 'キャンペーン区分',
            'campaign_name'    => '名称',
            'start_date'       => '利用開始日時',
            'end_date'         => '利用終了日時',
            'status'           => '有効/無効',
            'branch_id'        => '拠点',
            'branch'           => '拠点',
            'streaming_id'     => '配信ID',
            'create_by'        => '作成者',
            'create_date'      => '作成日時',
            'update_by'        => '更新者',
            'update_date'      => '更新日時',
        ];
    }

    public function attributeHints()
    {
        return [
            // 'capacity'   => '半角数字で入力してください。',
            // 'print_name' => '印刷時に出力される名称です。<br>上部項目入力時、又は右部の「名称更新」ボタンで入力内容が反映され、当項目内で変更も可能です。',
        ];
    }

    protected function setCampaignCode()
    {
        $code = $this->makeRandStr();
        while (Campaign::find()->andWhere(['campaign_code' => $code])->one()) {
            $code = $this->makeRandStr();
        }
        
        return $this->getPrefix().$code;  //prefix d:値引 , p:ポイント
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
        return new CampaignQuery(get_called_class());
    }

    public static function getStatus($status = null)
    {
        $statuses = ['無効', '有効'];

        if (! $status)
            return $statuses;

        if (! array_key_exists($status, $statuses))
            return null;

        return $statuses[$status];
    }
    
    public static function getCampaignType($type = null)
    {
        $types = [1 => '値引', 2 => 'ポイント'];

        if (! $type)
            return $types;

        if (! array_key_exists($type, $types))
            return null;

        return $types[$type];
    }
    
    public function getPrefix()
    {
        if($this->campaign_type){
            $this->setPrefix($this->campaign_type);
            return $this->prefix;
        }
        
        if (! $this->prefix)
            $this->setPrefix('d');
                
        return $this->prefix;
    }

    public function setPrefix($val = 1)
    {
        $this->prefix = 'd';

        switch($val){
            case 1:
                $this->prefix = 'd';
            case 2:
                $this->prefix = 'p';
            default:
        }
        
        $this->setCampaignType($val);
        
        return $this->prefix;
    }
    
    
    public function setCampaignType($val)
    {
        return $this->campaign_type = $val;
    }


    public function getStreamingId()
    {
        return $this->streaming_id;
    }
    public function setStreamingId($val)
    {
        return $this->streaming_id = $val;
    }
    
    public static function getCampaignWithBranch($branch_id)
    {
        $query = Campaign::find()
                ->active()
                ->andWhere(['branch_id' => $branch_id]);

        return $query->all();

    }

    public static function getCampaignOneWithBranch($branch_id)
    {
        $query = Campaign::find()
                ->active()
                ->andWhere(['branch_id' => $branch_id]);

        return $query->one();
    }

    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    public function getStreaming()
    {
        return $this->hasOne(Streaming::className(), ['streaming_id' => 'streaming_id']);
    }

    public function getDetails()
    {
        return $this->hasMany(CampaignDetail::className(), ['campaign_id' => 'campaign_id']);
    }

    public function isActiveOnlyStatus()
    {
        return ($this->status == 1);
    }



}

/**
 * ActiveQuery for ProductMaster
 */
class CampaignQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function active()
    {
        $today = date('Y-m-d H:i:s', time());

        return $this->andWhere(['status' => 1])
                    ->andWhere('start_date <= :start_date', [':start_date' => $today])
                    ->andWhere('end_date >= :end_date', [':end_date' => $today]);
    }
}
