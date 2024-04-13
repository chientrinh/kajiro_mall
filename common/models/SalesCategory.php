<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_sales_category".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SalesCategory.php $
 * $Id: SalesCategory.php 2795 2020-02-07 11:55:11Z kawai $
 *
 * @property string  $sku_id
 * @property string  $vender_key
 * @property string  $bunrui_code1
 * @property string $bunrui_code2
 * @property string  $bunrui_code3
 *
 * @property SalesCategory1[] $sales1
 * @property SalesCategory2[] $sales2
 * @property SalesCategory3[] $sales3
 */

class SalesCategory extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_sales_category';
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
            [['sku_id', 'vender_key', 'bunrui_code1', 'bunrui_code2', 'bunrui_code3'], 'required'],
            [['sku_id'], 'string', 'min' => 13,'max' => 13],
            [['vender_key'], 'string', 'max' => 2],
            [['bunrui_code1','bunrui_code2'], 'string', 'max' => 2],
            [['bunrui_code3'], 'string', 'max' => 4],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sku_id'  => "SKU-ID",
            'vender_key' => '製造元',
            'bunrui_code1'       => "大分類",
            'bunrui_code2'       => "中分類",
            'bunrui_code3'       => "小分類",
        ];
    }

    /**
     * @inheritdoc
     */

    public function validate()
    {
        $sales1 = SalesCategory1::find()->where(['bunrui_code1' => $this->bunrui_code1])->one();
        $sales2 = SalesCategory2::find()->where(['bunrui_code2' => $this->bunrui_code2])->one();
        $sales3 = SalesCategory3::find()->where(['bunrui_code3' => $this->bunrui_code3])->one();
        $sale2error = false;
        $sale3error = false;
        $error = "";

        if($this->bunrui_code1 != $sales2->bunrui_code1) {
            $sales2parent = SalesCategory1::find()->where(['bunrui_code1' => $sales2->bunrui_code1])->one();
            $sale2error = true;
            $error .= "大分類コードと中分類の親コードが一致しません<br>大分類コード：".$this->bunrui_code1." ".$sales1->name."<br> 中分類親コード：".$sales2->bunrui_code1." ".$sales2parent->name."<br><br><br><br>";
        }
        if($this->bunrui_code2 != $sales3->bunrui_code2) {
            $sales3parent = SalesCategory2::find()->where(['bunrui_code2' => $sales3->bunrui_code2])->one();
            $sale3error = true;
            $error .= "中分類コードと小分類の親コードが一致しません<br>中分類コード：".$this->bunrui_code2." ".$sales2->name."<br> 小分類親コード：".$sales3->bunrui_code2." ".$sales3parent->name."<br>";
        }

        if($sale2error || $sale3error) {
           Yii::$app->session->addFlash('danger', $error);
           return false; 
        }

        return true;

    }
/*
    public static function find()
    {
        return new SalesCategoryQuery(get_called_class());
    }
*/

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSales1()
    {
        return $this->hasOne(SalesCategory1::className(), ['bunrui_code1' => 'bunrui_code1']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSales2()
    {
        return $this->hasOne(SalesCategory2::className(), ['bunrui_code2' => 'bunrui_code2']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSales3()
    {
        return $this->hasOne(SalesCategory3::className(), ['bunrui_code3' => 'bunrui_code3']);
    }

    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['sku_id' => 'sku_id']);
    }

    public function getVegetable()
    {
        $veg_id = (int)substr($this->sku_id, 3, 9);
        return $this->hasOne(Vegetable::className(), ['veg_id' => $veg_id]);
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
