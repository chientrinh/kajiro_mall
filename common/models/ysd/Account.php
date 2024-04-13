<?php

namespace common\models\ysd;

use Yii;
use \common\models\Customer;
use \common\models\CustomerGrade;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ysd/Account.php $
 * $Id: Account.php 4241 2020-03-19 06:35:55Z mori $
 *
 * This is the model class for table "dtb_ysd_account".
 *
 * @property integer $customer_id
 * @property integer $expire_id
 *
 * @property MtbYsdAccountStatus $expire
 * @property DtbCustomer $customer
 */
class Account extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_ysd_account';
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
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
            [['customer_id'], 'required'],
            [['credit_limit'], 'default', 'value'=> 10 * 10000, 'when' => function($model){
                return ($c = $model->customer) && (!$c->isAgency()) && ($c->grade_id <= CustomerGrade::PKEY_AA);
            }],
            [['credit_limit'], 'default', 'value'=> 10 * 10000, 'when' => function($model){
                return ($c = $model->customer) && (!$c->isAgency()) && ($c->grade_id == CustomerGrade::PKEY_KA);
            }],
            [['credit_limit'], 'default', 'value'=> 8888 * 10000 /* 8,888 万円*/],
            [['credit_limit', 'customer_id', 'expire_id'], 'integer'],
            [['customer_id'], 'exist', 'targetClass' => Customer::className() ],
            [['expire_id'],   'exist', 'targetClass' => AccountStatus::className() ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id' => '顧客',
            'expire_id'   => '状態',
            'credit_limit'=> '振替限度額',
            'create_date' => '作成日',
            'update_date' => '更新日',
        ];
    }

    public function attributeHints()
    {
        return [
            'expire_id'   => '通常は「有効」です。その他の値に変更する場合、システム担当者にご相談ください',
            'credit_limit'=> '1回の支払い額および1回の振替額に対する上限を定めます',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new AccountQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDetail()
    {
        return $this->hasOne(RegisterResponse::className(),['custno' => 'customer_id'])
                    ->newest();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExpire()
    {
        return $this->hasOne(AccountStatus::className(), ['expire_id' => 'expire_id']);
    }

    public function isValid()
    {
        return AccountStatus::PKEY_VALID == $this->expire_id;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }
}

class AccountQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere(['expire_id' => 0]);
        else
            return $this->andWhere(['not', ['expire_id' => 0]]);
    }
}
