<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_staff_discount".
 *
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/StaffDiscount.php $
 * @version $Id: StaffDiscount.php 2722 2020-07-29 08:38:22Z kawai $
 *
 * @property integer $category_id
 * @property integer $subcategory_id
 * @property integer $ean13
 * @property integer $staff_grade_id
 * @property integer $discount_rate
 * @property integer $point_rate
 *
 * @property MtbStaffGrade $grade
 * @property MtbCategory $category
 */
class StaffDiscount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_staff_discount';
    }

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
            [['staff_grade_id'], 'required'],
            [['category_id', 'subcategory_id', 'staff_grade_id', 'discount_rate', 'point_rate'], 'integer'],
            [['discount_rate','point_rate'],'integer','min'=>0,'max'=>100],
            [['category_id','staff_grade_id','subcategory_id'], 'default','value'=> null],
            [['discount_rate','point_rate'], 'default','value'=> 0],
            [['ean13'], 'string', 'length' => 13],
            [['ean13'], 'default','value'=> ''],
            [['category_id', 'staff_grade_id', 'subcategory_id','ean13'], 'unique', 'targetAttribute' => ['category_id', 'staff_grade_id', 'subcategory_id', 'ean13'],'message' => 'この割引は既に登録されています。'],
            [['staff_grade_id',   ],'exist', 'targetClass'=>\common\models\StaffGrade::className()],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'category_id'   => 'カテゴリー',
            'subcategory_id'   => 'サブカテゴリー',
            'ean13'   => '商品',
            'staff_grade_id'      => '社員区分',
            'discount_rate' => '値引き率',
            'point_rate'    => 'ポイント率',
        ];
    }

    public function getTarget()
    {
        return $this->category ? $this->category : ($this->subcategory ? $this->subcategory : $this->product);
    }

    public function beforeValidate()
    {
        if($this->category_id == 0)
            $this->category_id = null;
        if($this->subcategory_id == 0)
            $this->subcategory_id = null;
        if($this->ean13 == "0")
            $this->ean13 = "";
        

        if(! $this->category && !$this->subcategory && ($this->ean13 == "" || !$this->ean13)) {
            $this->addError('category_id', "カテゴリ、サブカテゴリ、商品のいずれかを必ず指定してください");
            $this->addError('subcategory_id', "カテゴリ、サブカテゴリ、商品のいずれかを必ず指定してください");
            $this->addError('ean13', "カテゴリ、サブカテゴリ、商品のいずれかを必ず指定してください");
            return false;
        }

        return parent::beforeValidate();
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaffGrade()
    {
        return $this->hasOne(StaffGrade::className(), ['staff_grade_id' => 'staff_grade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['category_id' => 'category_id']);
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
    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->category ? $this->category->seller : ($this->subcategory ? $this->subcategory->company : ($this->product ? $this->product->company : null));
    }    
    
}
