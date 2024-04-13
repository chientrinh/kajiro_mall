<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_commission".
 *
 * @property integer $purchase_id
 * @property integer $company_id
 * @property integer $customer_id
 * @property integer $fee
 *
 * @property MtbCompany $company
 * @property DtbCustomer $customer
 * @property DtbPurchase $purchase
 */
class Commission extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_commission';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
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
            [['purchase_id', 'company_id', 'customer_id', 'fee'], 'required'],
            [['purchase_id', 'company_id', 'customer_id', 'fee'], 'integer'],
            [['purchase_id'], 'exist', 'targetClass' => Purchase::className()],
            [['customer_id'], 'exist', 'targetClass' => Customer::className()],
            [['company_id'],  'exist', 'targetClass' => Company::className()],
            [['purchase_id', 'company_id', 'customer_id'], 'unique', 'targetAttribute' => ['purchase_id', 'company_id', 'customer_id'], 'message' => 'Purchase ID, Company ID and Customer ID の組み合わせは登録済みです'],
            ['fee','integer','min'=>0,'max'=>$this->purchase ? $this->purchase->total_charge : 1000 * 1000],
            [['create_date','update_date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'commision_id' => 'ID',
            'purchase_id'  => '注文番号',
            'company_id'   => '販社',
            'customer_id'  => '受益者',
            'fee'          => '手数料',
            'create_date'  => '起票日時',
            'update_date'  => '更新日時',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'purchase_id'  => 'どの注文に対する手数料であるかを示します',
            'company_id'   => '手数料を支払う会社を示します',
            'fee'          => '受益者に支払う金額を示します',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchase()
    {
        return $this->hasOne(Purchase::className(), ['purchase_id' => 'purchase_id']);
    }
}
