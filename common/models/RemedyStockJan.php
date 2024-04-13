<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_remedy_stock_jan".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyStockJan.php $
 * $Id: RemedyStockJan.php 3197 2017-02-26 05:22:57Z naito $
 *
 * @property string $jan
 * @property string $sku_id
 */
class RemedyStockJan extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_stock_jan';
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
            [['jan', 'sku_id'], 'required'],
            [['jan', 'sku_id'], 'string', 'length' => 13],
            [['jan', 'sku_id'], 'number'],
            [['jan', 'sku_id'], 'checkDigit'],
            [['sku_id'], 'match', 'pattern'  => sprintf('/^%s/', RemedyStock::EAN13_PREFIX),
                                   'message' => '不正なコードです'],
            [['jan'],    'match',  'pattern' => '/^4/', 'message' => '先頭は4であるべきです'],
            [['jan'],    'unique', 'message' => 'このJANコードは別の商品で登録済みです'],
            [['sku_id'], 'unique', 'message' => 'このレメディー商品は別のJANコードで登録済みです'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'jan'    => 'JAN',
            'sku_id' => 'SKU ID',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        $stock = $this->getStock();
        if(! $stock->isNewRecord)
             $stock->update(); /* update ProductMaster::ean13 */

        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * inline validator
     * @return bool
     */
    public function checkDigit($attr, $param)
    {
     if(! \common\components\ean13\CheckDigit::verify($this->$attr))
            $this->addError($attr, sprintf("チェックディジット(最終桁)は %d であるべきです。",
                                           \common\components\ean13\CheckDigit::generate(substr($this->$attr,0,12))));

        return $this->hasErrors($attr);
    }

    /* @return RemedyStock */
    public function getStock()
    {
        $rid = (int) substr($this->sku_id, 3,5);
        $pid = (int) substr($this->sku_id, 8,2);
        $vid = (int) substr($this->sku_id,10,2);

        $stock = RemedyStock::findOne([
            'remedy_id' => $rid,
            'potency_id'=> $pid,
            'vial_id'   => $vid,
        ]);
        if(! $stock)
             $stock = new RemedyStock([
            'remedy_id' => $rid,
            'potency_id'=> $pid,
            'vial_id'   => $vid,
            'in_stock'  => false,
            ]);

        return $stock;
    }

}
