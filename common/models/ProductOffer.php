<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_product_offer".
 *
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductOffer.php $
 * @version $Id: ProductOffer.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $product_id
 * @property integer $membership_id
 * @property integer $discount_rate
 * @property integer $discount_amount
 * @property string $start_date
 * @property string $end_date
 *
 * @property Membership $membership
 * @property Product $product
 */
class ProductOffer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_product_offer';
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
            [['product_id', 'membership_id', 'start_date', 'end_date'], 'required'],
            [['product_id', 'membership_id', 'discount_rate', 'discount_amount'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            ['start_date', 'compare', 'compareAttribute'=>'end_date', 'operator'=>'<'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id'      => 'Product ID',
            'membership_id'   => 'Membership ID',
            'discount_rate'   => 'Discount Rate',
            'discount_amount' => 'Discount Amount',
            'start_date'      => 'Start Date',
            'end_date'        => 'End Date',
        ];
    }

    /**
     * @return int
     */
    private function getEnd()
    {
        return strtotime($this->end_date);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMembership()
    {
        return $this->hasOne(Membership::className(), ['membership_id' => 'membership_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    /**
     * @return int
     */
    private function getStart()
    {
        return strtotime($this->start_date);
    }

    /**
     * @return bool
     */
     public function isAvaliable($now=null)
    {
        if(null === $now)
            $now = time();

        if(($this->start <= $now) && ($now <= $this->end))
            return true;

        return false;
    }

    /**
     * @return bool
     */
    public function isFor($membership)
    {
        if(($membership === $this->membership) || ($membership === $this->membership_id))
            return true;

        return false;
    }
}
