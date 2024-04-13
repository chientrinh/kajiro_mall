<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_product_point".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductPoint.php $
 * $Id: ProductPoint.php 2722 2016-07-15 08:38:22Z mori $
 *
 * @property integer $point_id
 * @property integer $product_id
 * @property integer $membership_id
 * @property integer $branch_id
 * @property integer $point_per
 * @property integer $point_vol
 * @property string $start_date
 * @property string $expire_date
 *
 * @property MtbMembership $membership
 * @property MtbBranch $branch
 */
class ProductPoint extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_product_point';
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
            [['product_id', 'start_date', 'expire_date'], 'required'],
            [['product_id', 'membership_id', 'branch_id', 'point_per', 'point_vol'], 'integer'],
            [['start_date', 'expire_date'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'point_id' => 'Point ID',
            'product_id' => 'Product ID',
            'membership_id' => 'Membership ID',
            'branch_id' => 'Branch ID',
            'point_per' => 'Point Per',
            'point_vol' => 'Point Vol',
            'start_date' => 'Start Date',
            'expire_date' => 'Expire Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMembership()
    {
        return $this->hasOne(MtbMembership::className(), ['membership_id' => 'membership_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(MtbBranch::className(), ['branch_id' => 'branch_id']);
    }
}
