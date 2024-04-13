<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * This is the model class for table "dtb_pointing".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Pointing.php $
 * $Id: Pointing.php 4185 2019-09-30 16:12:44Z mori $
 *
 * @property integer $pointing_id
 * @property integer $seller_id
 * @property integer $customer_id
 * @property string $create_date
 * @property string $update_date
 * @property integer $status
 * @property string $note
 *
 * @property DtbCustomer $customer
 * @property DtbCustomer $seller
 * @property DtbPointingItem[] $dtbPointingItems
 */
class Pointing extends \yii\db\ActiveRecord
{
    const STATUS_SOLD    = 1;
    const STATUS_EXPIRED = 9;

    const POINT_OFFSET_RATE_MAX = 5;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_pointing';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            'pkey' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'pointing_id',
                ],
                'value' => function ($event) {
                    return static::find()->select('pointing_id')->max('pointing_id') + 1;
                },
            ],
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return date('Y-m-d H:i:s');
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
            'toranoko' => [
                'class'  => FixMembership::className(),
                'owner'  => $this,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['seller_id', 'company_id', 'point_given', 'customer_id'], 'required'],
            [['seller_id', 'company_id', 'customer_id', 'status'], 'integer'],
            ['point_consume', 'compareWithSubtotal'],
            ['point_consume', 'compareWithCustomerPoint'],
            ['receive', 'compareWithTotalCharge'],
            [['point_consume','receive','point_given','point_offset'], 'default', 'value' => 0 ],
            [['total_charge','subtotal','point_consume','tax','receive','change'], 'integer', 'min'=> 0 ],
            [['seller_id', 'customer_id'], 'exist', 'targetClass' => Customer::className(), 'targetAttribute'=>'customer_id'],
            ['customer_id', 'compare', 'operator' => '!=', 'compareAttribute' => 'seller_id', 'when' => function(){ return 'app-frontend' == Yii::$app->id; }, 'message' => '販売者は自分をお客様に設定できません'],
            ['company_id', 'exist', 'targetClass' => Company::className()],
            [['status'], 'default', 'value' => self::STATUS_SOLD],
            [['status'], 'in', 'range' => [self::STATUS_SOLD, self::STATUS_EXPIRED]],
            [['note'], 'string', 'max' => 255],
            [['note'], 'required', 'when' => function(){ return 'app-backend' == Yii::$app->id; }],
        ];
    }

    /**
     * @brief alternate CompareValidation + specific validation for point_consume
     * @see yii 2.0.4 bug report on CompareValidator
     * @link http://stackoverflow.com/questions/26353254/yii2-compare-validator-alert-not-disapear
     */
    public function compareWithSubtotal($attr,$param)
    {
        if($this->$attr <= $this->subtotal)
            return true;

        $this->addError($attr, 'ポイント値引きは"商品計"以下である必要があります');
        return false;
    }

    public function compareWithCustomerPoint($attr,$param)
    {
        if(! $this->customer)
            $this->point_consume = 0;

        if(0 == $this->point_consume)
            return true;

        if($this->point_consume <= $this->customer->point)
            return true;

        $this->addError($attr, sprintf('ポイント値引きは"お客様所持ポイント(%d)"以下である必要があります', $this->customer->point));
        return false;
    }

    public function compareWithTotalCharge($attr,$param)
    {
        if($this->total_charge <= $this->receive)
            return true;

        if(0 == $this->receive)
            $this->addError($attr, 'お預かりを入力してください');
        else
            $this->addError($attr, sprintf('お預かりは"お支払い合計(%d)"以上である必要があります', $this->total_charge));

        return false;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pointing_id' => '伝票NO',
            'seller'      => '販売者',
            'customer'    => '顧客',
            'create_date' => '売上日',
            'update_date' => '修正日',
            'status'      => '状態',
            'note'        => '備考',
            'total_charge'  => "お支払い合計",
            'subtotal'      => "商品計",
            'tax'           => "消費税",
            'point_consume' => "Pt 値引",
            'point_given'   => "Pt 付与",
            'point_offset'  => "Pt ご負担",
            'receive'       => "お預かり",
            'change'        => "おつり",
            'company_id'    => '会社',
            'customer_id'   => 'お客様情報',
            'staff_id'      => 'スタッフ'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'point_offset'  => "当社負担MAX％を控除した、代理店が負担するPt額",
        ];
    }

    public function beforeValidate()
    {
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     * @return PointingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PointingQuery(get_called_class());
    }

    /* @return void */
    public function compute($search=true)
    {
        if($search && $this->customer)
        {
            $finder = new SearchProductFavor(['customer'=>$this->customer]);
            foreach($this->items as $k => $item)
            {
                $param = $item->getAttributes(['product_id','remedy_id','potency_id','vial_id']);
                $query = ProductMaster::find()->where($param);
                if(! $query->exists())
                    continue; // point rate remain unchanged
                $finder->item = $query->one();
//                $this->items[$k]->point_rate = $finder->getPointForMember()->rate;
                $item->point_rate = $finder->getPointForMember()->rate;
            }
        }
        $this->point_consume = intval($this->point_consume);
        $point_given         = array_sum(ArrayHelper::getColumn($this->items, 'pointTotal', 0));

        // reduce given point
        if((0 < $point_given) && (0 < $this->point_consume))
        {
            $numer = $this->subtotal - $this->point_consume;
            $denom = $this->subtotal;

            $point_given = floor($point_given * ($numer / $denom));
        }
        $this->point_given = $point_given;

        // 消費税計算
        $tax = 0;
        // 軽減税率を別に集計する
        $tax_total = 0;
        $reduced_tax_total = 0;

        foreach ($this->items as $index => $item) {
            // 消費税 = 消費税1アイテムあたりの金額＊個数
            $item->unit_tax = null;
            if($this->create_date && strtotime($this->create_date) <= \common\models\Tax::newDate()) {
                $tax += $item->getUnitTax(true) * $item->quantity;
            } else {
                if($item->isReducedTax()) {
                    $reduced_tax_total += ($item->getUnitTax() * $item->quantity);
                } else {
                    $tax_total += ($item->getUnitTax() * $item->quantity);
                }
            }
        }
        if(!($this->create_date && strtotime($this->create_date) <= \common\models\Tax::newDate())) {
            $tax = $tax_total + $reduced_tax_total;
        }

        //$this->tax           = Yii::$app->tax->compute($this->subtotal);
        $this->tax           = $tax;

        $this->subtotal      = array_sum(ArrayHelper::getColumn($this->items, 'basePrice', 0));
        $this->total_charge  = $this->subtotal
                             + $this->tax
                             - $this->point_consume;

        $this->point_offset  = $this->computePointOffset();

        $this->change = max(0, ($this->receive - $this->total_charge));

        return;
    }

    private function computePointOffset()
    {
        $offset = 0;

         foreach($this->items as $item)
        {
            if(! $item->point_rate)
                continue;

            $tmp             = clone $item;
                $tmp->point_rate = ($tmp->point_rate - self::POINT_OFFSET_RATE_MAX);
                if($tmp->point_rate < 0)
                    $tmp->point_rate = 0;
                
//                $offset += $tmp->getPointTotal() * ($this->subtotal - $this->point_consume)/$this->subtotal;
                  $offset += $tmp->getPointTotal();
        }
        
        if($this->subtotal == 0)
            return 0;
        
        $offset = $offset * ($this->subtotal - $this->point_consume)/$this->subtotal;
        // 貴社負担分、なので、切り上げてあげる
        $pt_offset = ceil($offset);
        
        if(0 == $pt_offset)
            return 0;

        return $pt_offset;
    }

    public function expire() // would be performed by seller
    {
        if(self::STATUS_EXPIRED <= $this->status)
            return true;

        $this->status = self::STATUS_EXPIRED;
        return $this->save(false);
    }

    /* @return \yii\db\ActiveQuery */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /* @return \yii\db\ActiveQuery */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    public function getStaff()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'staff_id']);
    }

    /* @return integer */
    public function getItemCount()
    {
        return array_sum(ArrayHelper::getColumn($this->items, 'quantity'));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(PointingItem::className(), ['pointing_id' => 'pointing_id']);
    }

    public function getParentItems()
    {
        return $this->hasMany(RecipeItem::className(), ['recipe_id' => 'recipe_id'])->where(['parent' => null]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeller()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'seller_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusName()
    {
        switch ($this->status)
        {
            case self::STATUS_SOLD    : return "売上";
            case self::STATUS_EXPIRED : return "無効";
            defalut                   : return null;
        }
    }

    public function getTaxable()
    {
//        return ((int)$this->subtotal - (int)$this->point_consume);
        return (int)$this->subtotal;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return (self::STATUS_EXPIRED <= $this->status);
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return ($this->create_date < $this->update_date);
    }

}

/**
 * This is the ActiveQuery class for [[Pointing]].
 *
 * @see Pointing
 */
class PointingQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['dtb_pointing.status' => Pointing::STATUS_SOLD]);
    }

    /**
     * @inheritdoc
     * @return Pointing[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Pointing|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
