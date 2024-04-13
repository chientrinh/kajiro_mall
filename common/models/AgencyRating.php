<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "rtb_agency_rating".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/AgencyRating.php $
 * $Id: AgencyRating.php 3148 2016-12-11 02:43:25Z mori $
 *
 * @property integer $rating_id
 * @property integer $customer_id
 * @property integer $discount_rate
 * @property string $start_date
 * @property string $end_date
 *
 * @property DtbCustomer $customer
 */
class AgencyRating extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rtb_agency_rating';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['customer_id', 'discount_rate', 'start_date', 'end_date'], 'trim'],
            [['customer_id', 'discount_rate', 'start_date', 'end_date', 'company_id'], 'required'],
            ['customer_id','exist','targetClass'=>Customer::className()],
            ['company_id','exist','targetClass'=>Company::className()],
            ['discount_rate', 'integer', 'min'=>0, 'max'=>100],
            [['start_date', 'end_date'], 'date', 'format'=>'php:Y-m-d'],
            ['start_date','compare','compareAttribute'=>"end_date",'operator'=>'<'],
            ['customer_id','validateCustomer'],
       ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_id'    => '契約会社',
            'customer_id'   => '顧客',
            'discount_rate' => '割引率(％)',
            'start_date'    => '開始日',
            'end_date'      => '終了日',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new AgencyRatingQuery(get_called_class());
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

    public function validateCustomer($attr, $params)
    {
        if(! $this->customer->isAgency())
            $this->addError($attr, "この顧客はどの代理店でもありません。よって割引率は指定できません");

        elseif(! $this->customer->isAgencyOf($this->company_id))
            $this->addError($attr, "この顧客は{$this->company->name}の代理店ではありません。よって割引率は指定できません");

        elseif(AgencyRating::find()->where(['company_id' => $this->company_id,
                                            'customer_id'=> $this->customer_id,
                                            'start_date' => $this->start_date,
                                            'end_date'   => $this->end_date])->exists()
        )
            $this->addError($attr, "同じ要素で登録済みです(顧客、会社、開始、終了)");

        return $this->hasErrors($attr);
    }

}

class AgencyRatingQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere('rtb_agency_rating.start_date <= NOW() AND NOW() <= rtb_agency_rating.end_date');
        else
            return $this->andWhere('NOW() < rtb_agency_rating.start_date OR rtb_agency_rating.end_date < NOW()');
    }
}
