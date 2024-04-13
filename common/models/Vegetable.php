<?php

namespace common\models;

use Yii;
use \common\components\ean13\CheckDigit;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Vegetable.php $
 * $Id: Vegetable.php 2933 2016-10-08 02:47:03Z mori $
 *
 * This is the model class for table "mtb_vegetable".
 *
 * @property integer $veg_id
 * @property string $name
 * @property string $kana
 * @property string $create_date
 * @property string $update_date
 */
class Vegetable extends \yii\db\ActiveRecord
{
    const EAN13_PREFIX = 23;
    const PRODUCT_NAME = '生野菜';
    const DIV_0 = '豊受';
    const DIV_1 = '豊受特撰';

    public $price = 0;
    public $vender_key = "";
    public $bunrui_code1 = "";
    public $bunrui_code2 = "";
    public $bunrui_code3 = "";
    public $sku_id = "";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_vegetable';
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
            // ['veg_id','default','value'=>function() { return Vegetable::find()->min('veg_id') -1; } ],
            [['kana'],'filter','filter'=>function($value) { return \common\components\Romaji2Kana::translate($value,'hiragana'); }],
            [['name', 'kana', 'division', 'origin_area'], 'filter', 'filter' => 'trim'],
            [['name', 'kana', 'division', 'origin_area', 'print_name', 'is_other'], 'required'],
            [['name', 'kana', 'print_name'], 'string', 'max' => 255],
            [['veg_id'], 'unique'],
	    [['veg_id', 'capacity', 'dsp_priority', 'is_other'], 'integer'],
            ['is_other', 'default', 'value' => 0],
            [['vender_key', 'bunrui_code1', 'bunrui_code2', 'bunrui_code3', 'sku_id'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'veg_id'      => 'Veg ID',
            'is_other'      => '野菜／その他商品',
            'division'    => '種別',
            'origin_area' => '原産地',
            'name'        => '品名',
            'kana'        => 'かな',
            'capacity'    => '容量(g)',
            'dsp_priority'    => '表示順',
            'print_name'  => '印刷用名称',
            'vender_key'  => '製造元',
            'sku_id'      => 'SKU-ID',
            'bunrui_code1'  => '大分類',
            'bunrui_code2'  => '中分類',
            'bunrui_code3'  => '小分類',
            'create_date' => '作成日時',
            'update_date' => '更新日時',
        ];
    }

    public function attributeHints()
    {
        return [
            'capacity'   => '半角数字で入力してください。',
            'dsp_priority'   => '半角数字で入力してください。',
	    'print_name' => '印刷時に出力される名称です。<br>上部項目入力時、又は右部の「名称更新」ボタンで入力内容が反映され、当項目内で変更も可能です。',
        ];
    }

    /**
     * \common\components\ean13\ModelFinder::getOne() から呼び出されることを想定している
     * @return null | Product
     */
    public static function findByBarcode($ean13)
    {
        if(self::EAN13_PREFIX != substr($ean13, 0, strlen(self::EAN13_PREFIX)))
            return null;

        if(! CheckDigit::verify($ean13))
            return null;

        $veg_id = (int) substr($ean13, 3, 4);
        $price  = (int) substr($ean13, 7, 5);
        $veg = self::findOne($veg_id);
        if(! $veg)
            return null;

        $product = Product::findOne(['name' => self::PRODUCT_NAME]);
        if(! $product)
            return null;

        $product->name  = $veg->print_name;
        $product->kana  = $veg->kana;
        $product->code  = $ean13;
        $product->price = $price;

        return $product;
    }

    public function getEan13()
    {
        $base  = sprintf('%02d%d%04d%05d', self::EAN13_PREFIX, $this->is_other, $this->veg_id, $this->price);
        $check = CheckDigit::generate($base);
        return $base . $check;
    }

    /**
     * 売上分類用SKU-ID
     * 先頭２３　＋　野菜（０）orその他商品（１） ＋　野菜ID 9桁　＋ チェックデジット
     **/
    public function getSkuId()
    {
        $base  = sprintf('%02d%d%09d', self::EAN13_PREFIX, $this->is_other, $this->veg_id);
        $check = CheckDigit::generate($base);
        return $base . $check;
    }


    /**
     * 種別を取得する
     * @param null | int $index キー
     * @return boolean | array | string
     */
    public static function getDivision($index = null)
    {
    	$division = [ Vegetable::DIV_0, Vegetable::DIV_1 ];

    	if (is_null($index))
    		return $division; 
    	
    	if (! array_key_exists($index, $division))
    		return false;

    	return $division[$index];
    }

    public function beforeValidate() {
        $vender_key = $this->vender_key;
        $bunrui_code1 = $this->bunrui_code1;
        $bunrui_code2 = $this->bunrui_code2;
        $bunrui_code3 = $this->bunrui_code3;

        if($vender_key && $bunrui_code1 && $bunrui_code2 && $bunrui_code3) {
            if($this->isNewRecord) {
                $sales = new SalesCategory([
                    'sku_id' => $this->getSkuId(),
                    'vender_key' => strtoupper(Company::find()->where(['company_id' => $vender_key + 1])->one()->key),
                    'bunrui_code1' => SalesCategory1::find()->where(['bunrui_id' => $bunrui_code1 + 1])->one()->bunrui_code1,
                    'bunrui_code2' => SalesCategory2::find()->where(['bunrui_id' => $bunrui_code2 + 1])->one()->bunrui_code2,
                    'bunrui_code3' => SalesCategory3::find()->where(['bunrui_id' => $bunrui_code3 + 1])->one()->bunrui_code3,
                ]);
            } else {
                $sales = SalesCategory::find()->where(['sku_id' => $this->getSkuId()])->one();
                $sales->vender_key =  strtoupper(Company::find()->where(['company_id' => $vender_key + 1])->one()->key);
                $sales->bunrui_code1 = SalesCategory1::find()->where(['bunrui_id' => $bunrui_code1 + 1])->one()->bunrui_code1;
                $sales->bunrui_code2 = SalesCategory2::find()->where(['bunrui_id' => $bunrui_code2 + 1])->one()->bunrui_code2;
                $sales->bunrui_code3 = SalesCategory3::find()->where(['bunrui_id' => $bunrui_code3 + 1])->one()->bunrui_code3;

            }
            if($sales->validate()){
            } else {
                return false;
            }
        }
        return parent::beforeValidate();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $vender_key = $this->vender_key;
        $bunrui_code1 = $this->bunrui_code1;
        $bunrui_code2 = $this->bunrui_code2;
        $bunrui_code3 = $this->bunrui_code3;

        if($vender_key && $bunrui_code1 && $bunrui_code2 && $bunrui_code3) {            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if($this->isNewRecord) {
                    $sales = new SalesCategory([
                        'sku_id' => $this->getSkuId(),
                        'vender_key' => strtoupper(Company::find()->where(['company_id' => (int)$vender_key + 1])->one()->key),
                        'bunrui_code1' => SalesCategory1::find()->where(['bunrui_id' => (int)$bunrui_code1 + 1])->one()->bunrui_code1,
                        'bunrui_code2' => SalesCategory2::find()->where(['bunrui_id' => (int)$bunrui_code2 + 1])->one()->bunrui_code2,
                        'bunrui_code3' => SalesCategory3::find()->where(['bunrui_id' => (int)$bunrui_code3 + 1])->one()->bunrui_code3,
                    ]);
                } else {
                    $sales = SalesCategory::find()->where(['sku_id' => $this->getSkuId()])->one();
                    if(!$sales) {
                        $sales = new SalesCategory([
                            'sku_id' => $this->getSkuId(),
                        ]);

                    } 
                    $sales->vender_key =  strtoupper(Company::find()->where(['company_id' => (int)$vender_key + 1])->one()->key);
                    $sales->bunrui_code1 = SalesCategory1::find()->where(['bunrui_id' => (int)$bunrui_code1 + 1])->one()->bunrui_code1;
                    $sales->bunrui_code2 = SalesCategory2::find()->where(['bunrui_id' => (int)$bunrui_code2 + 1])->one()->bunrui_code2;
                    $sales->bunrui_code3 = SalesCategory3::find()->where(['bunrui_id' => (int)$bunrui_code3 + 1])->one()->bunrui_code3;

                }
                if($sales->validate() && $sales->save()){
                } else {
                    Yii::warning($sales->errors);
                    $transaction->rollBack();
                    return false;
                }
            }
            catch (Exception $e)
            {
                Yii::warning($e->__toString(), $this->className().'::'.__FUNCTION__);
                $transaction->rollBack();
                return false;
            }

            $transaction->commit();
        }
        return true;
    }

}
