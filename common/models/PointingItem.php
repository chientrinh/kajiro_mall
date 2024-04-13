<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_pointing_item".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/PointingItem.php $
 * $Id: PointingItem.php 4185 2019-09-30 16:12:44Z mori $
 *
 * @property integer $pointing_id
 * @property string $code
 * @property string $name
 * @property integer $product_id
 * @property integer $remedy_id
 * @property integer $potency_id
 * @property integer $vial_id
 * @property integer $quantity
 * @property integer $seq
 * @property integer $parent
 *
 * @property MtbRemedyVial $vial
 * @property DtbPointing $pointing
 * @property MtbRemedyPotency $potency
 * @property MtbRemedy $remedy
 */
class PointingItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_pointing_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['quantity', 'default', 'value' => 1 ],
            [['pointing_id', 'code', 'name', 'seq'], 'required'],
            [['pointing_id', 'product_id', 'remedy_id', 'potency_id', 'vial_id', 'quantity', 'seq', 'parent'], 'integer'],
            ['product_id', 'exist', 'targetClass' => Product::className(), 'targetAttribute'=>'product_id'],
            ['remedy_id', 'exist', 'targetClass' => Remedy::className(), 'targetAttribute'=>'remedy_id'],
            ['potency_id', 'exist', 'targetClass' => RemedyPotency::className(), 'targetAttribute'=>'potency_id'],
            ['vial_id', 'exist', 'targetClass' => RemedyVial::className(), 'targetAttribute'=>'vial_id'],
            [['code', 'name'], 'string', 'max' => 255],
            [['pointing_id', 'seq'], 'unique', 'targetAttribute' => ['pointing_id', 'seq'], 'message' => 'The combination of Pointing ID and Seq has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'seq'       => 'NO',
            'code'      => "コード",
            'name'      => '品名',
            'quantity'  => "数量",
            'price'     => "定価",
            'unit_tax'  => "消費税",
            'point_rate'=> "ポイント％",
            'basePrice' => "小計",
        ];
    }

    /**
     * @return integer
     */
    public function getBasePrice()
    {
        return ($this->quantity * $this->price);
    }

    /**
     * 消費税（１商品あたり）
     **/
    public function getUnitTax($getOld = false)
    {
        if(isset($this->unit_tax))
            return $this->unit_tax;

        if($getOld) {
            $rate = 0.08;
        } else {
            $rate = $this->isReducedTax() ? \common\models\Tax::findOne(2)->getRate()/100 : \common\models\Tax::findOne(1)->getRate()/100;
        }

        // 小売レジには値引きは無いので、必ず定価＊税率
        return $this->unit_tax = floor($this->price * $rate);
    }

    public function setUnitTax($vol)
    {
        $this->unit_tax   = $vol;
    }


    public function isReducedTax() {
        if($this->product_id) {
            if($this->product->tax_id == 2) {
                return true;
            }
        } else if($this->remedy_id) {
            // 直接RemedyStockを取り出せない場合もあるため、PointingItemからパラメータを取り出して作成する
            $remedyStock = new \common\models\RemedyStock(['remedy_id' => $this->remedy_id, 'potency_id' => $this->potency_id, 'vial_id' => $this->vial_id]);
            if($remedyStock->isRemedy() && (!$remedyStock->isLiquor())) {
                return true;
            }
        }

        return false;

    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(static::className(), ['parent' => 'seq'])->where(['pointing_id'=>$this->pointing_id]);
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        if(! $this->children)
            return $this->name;

        $names = \yii\helpers\ArrayHelper::getColumn($this->children, 'name');
        array_unshift($names, $this->name);

        return implode("\n", $names);
    }

    public function getPointTotal()
    {
        if($this->point_rate)
            return $this->quantity * floor($this->price * $this->point_rate / 100);

        return 0;
    }

    public function getPointing()
    {
        return $this->hasOne(Pointing::className(),['pointing_id' => 'pointing_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPotency()
    {
        return $this->hasOne(RemedyPotency::className(), ['potency_id' => 'potency_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRemedy()
    {
        return $this->hasOne(Remedy::className(), ['remedy_id' => 'remedy_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVial()
    {
        return $this->hasOne(RemedyVial::className(), ['vial_id' => 'vial_id']);
    }

}
