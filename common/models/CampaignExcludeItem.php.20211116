<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_sales_category".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CampaignExcludeItem.php $
 * $Id: CampaignExcludeItem.php 2795 2020-04-20 11:55:11Z kawai $
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

class CampaignExcludeItem extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_campaign_exclude_item';
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
            [['ean13','sku_id'], 'string', 'max' => 13]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ean13'  => "JANコード",
            'sku_id'    => "SKU_ID",
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13']);
    }
}