<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_sales_category".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Streaming.php $
 * $Id: Streaming.php 2795 2020-04-20 11:55:11Z kawai $
 *
 * @property int  $streaming_id
 * @property int  $product_id
 * @property string  $name
 * @property string  $expire_from
 * @property string  $expire_to
 * @property string  $streaming_url
 * @property string  $post_url
 * @property string  $document_url
 * @property string  $create_date
 * @property string  $expire_date
 * @property string  $update_date
 *
 * @property Product $product
 */

class Streaming extends \yii\db\ActiveRecord
{
    const DATETIME_MAX = '3000-12-31 00:00:00';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_streaming';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return date('Y-m-d H:i:s');
                },
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
            [['name'], 'required'],
            [['streaming_id','product_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['streaming_url','post_url','document_url'], 'string'],
            [['expire_from','expire_to','expire_date'], 'safe'],
            ['create_date','default', 'value'=> date('Y-m-d 00:00:00') ],
            ['expire_date','default','value'=> self::DATETIME_MAX ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'streaming_id'  => "配信ID",
            'product_id'    => "商品ID",
            'name'          => "配信タイトル",
            'expire_from'   => "配信開始日時",
            'expire_to'     => "配信終了日時",
            'streaming_url' => "配信URL",
            'post_url'      => "アンケートページURL",
            'document_url'  => "配布資料URL",
            'create_date'   => "作成日時",
            'expire_date'   => "公開終了日",
            'update_date'   => "更新日時",
        ];
    }

    public function isExpired()
    {
        return (strtotime($this->expire_date) <= time());
    }

    public function getStreamingId()
    {
        return $this->streaming_id;
    }

    public function setStreamingId($val)
    {
        $this->streaming_id = $val;
    }
    public function getProductId()
    {
        return $this->product_id;
    }

    public function setProductId($val)
    {
        $this->product_id = $val;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['product_id' => 'product_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStreamingBuy()
    {
        return $this->hasMany(StreamingBuy::className(), ['streaming_id' => 'streaming_id']);
    }    
}

/*
class SalesCategoryQuery extends \yii\db\ActiveQuery
{
    public function forCampaign()
    {
        return $this->andWhere(['or', 
                                    ['company_id' => 3], 
                                    ['branch_id' => [Branch::PKEY_FRONT, Branch::PKEY_ATAMI, Branch::PKEY_ROPPONMATSU, Branch::PKEY_HJ_TOKYO, Branch::PKEY_EVENT]]
                    ]);
    }

    public function wareHouse()
    {
        return $this->andWhere(['branch_id' => [Branch::PKEY_ATAMI, Branch::PKEY_ROPPONMATSU]]);
    }
}
*/
