<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;
use \backend\models\Staff;

/**
 * This is the model class for table "dtb_inventory".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Inventory.php $
 * $Id: Inventory.php 2796 2016-07-30 07:20:40Z mori $
 *
 * @property integer $inventory_id
 * @property integer $branch_id
 * @property string  $create_date
 * @property string  $update_date
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $istatus_id
 *
 * @property InventoryStatus  $status
 * @property Branch           $branch
 * @property Staff            $createdBy
 * @property Staff            $updatedBy
 * @property InventoryItems[] $items
 */
class Inventory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_inventory';
    }

    public function behaviors()
    {
        return [
            'date' => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
                'updatedAtAttribute' => 'update_date',
                'value' => new \yii\db\Expression('NOW()'),
            ],
            'staff' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['branch_id'], 'required'],
            [['branch_id'], 'exist', 'targetClass'=>Branch::className() ],
            [['create_date','update_date'], 'safe'],
            [['created_by','updated_by'], 'exist', 'targetClass'=>\backend\models\Staff::className(),'targetAttribute'=>'staff_id'],
            [['istatus_id'], 'default', 'value' => InventoryStatus::PKEY_INIT ],
            [['istatus_id'], 'exist', 'targetClass'=>InventoryStatus::className() ],
            [['items'], 'required', 'when' => function($model){ return ! $model->isNewRecord; }],
            [['items'], 'validateItems', 'when' => function($model){ return ! $model->isNewRecord && (InventoryStatus::PKEY_SUBMIT == $model->istatus_id); }],
            [['create_date'],'date','format'=>'php:Y-m-d H:i:s','min'=> ($p = $this->prev) ? strtotime($p->create_date) : null,],
            ['branch_id','filterDuplication', 'when'=>function($model){ return $model->isNewRecord; }],
        ];
    }

    public function filterDuplication($attr, $param)
    {
        $q = self::find()->andWhere(['branch_id' => $this->branch_id])
                         ->andWhere(['not',['istatus_id' => InventoryStatus::PKEY_APPROVED]]);
        if($model = $q->one())
            $this->addError($attr, "前回の棚卸が完了していないか、未承認です: (ID {$model->inventory_id})");

        return $this->hasErrors($attr);
    }

    public function validateItems($attr, $param)
    {
        if($this->getItems()->where(['updated_by'=>Staff::PKEY_NOBODY])->exists())
            $this->addError($attr, "品目の更新者がシステム自動処理のままではいけません");

        else
        foreach($this->items as $item)
            if(! $item->validate())
        {
            $msg = $item->kana .': '. implode('; ',$item->firstErrors);
            $this->addError($attr, $msg);
        }

        return $this->hasErrors($attr);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'inventory_id' => '棚卸ID',
            'branch_id'    => '拠点',
            'created_by'   => '起票者',
            'updated_by'   => '更新者',
            'create_date'  => '対象日時',
            'update_date'  => '最終更新日',
            'istatus_id'   => '状態',
            'items'        => '品目',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'create_date'  => '前回の棚卸からこの日時までの入出庫を参照して帳簿在庫を自動計算しています',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($insert)
            $this->initItems();

        elseif(in_array('create_date', array_keys($changedAttributes)))
            $this->updateItems();

        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(InventoryItem::className(), ['inventory_id' => 'inventory_id']);
    }

    public function getNext()
    {
        return $this->findOne(['inventory_id' => $this->find()
                                                      ->andWhere(['branch_id'       => $this->branch_id])
                                                      ->andWhere(['>', 'inventory_id', $this->inventory_id])
                                                      ->min('inventory_id')
        ]);
    }

    public function getPrev()
    {
        return $this->findOne(['inventory_id' => $this->find()
                                                      ->andWhere(['branch_id'       => $this->branch_id])
                                                      ->andWhere(['<', 'inventory_id', $this->inventory_id])
                                                      ->max('inventory_id')
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(ProductMaster::className(),  ['product_id' => 'product_id'])
                    ->viaTable(InventoryItem::tableName(), ['inventory_id' => 'inventory_id']);
    }

    /**
     * 前回の棚卸から現在までの売り上げ商品を抽出する
     * @return \yii\db\ActiveQuery
     */
    public function getPurchaseItems()
    {
        $now = $this->isNewRecord ? new \yii\db\Expression('NOW()') : $this->create_date;

        $q1 = Purchase::find()
                      ->active()
                      ->andWhere(['<=', 'create_date', $now])
                      ->andWhere(['branch_id' => $this->branch_id])
                      ->select('purchase_id')
                      ->indexBy('purchase_id');

        if($prev = $this->prev)
            $q1->andWhere(['>', 'create_date', $prev->create_date]);

        return PurchaseItem::find()
               ->andWhere(['>', 'quantity', 0])
               ->andWhere(['purchase_id' => $q1 ]);
    }

    /**
     * 前回の棚卸から現在までの入庫商品を抽出する
     * @return \yii\db\ActiveQuery
     */
    public function getTransferInboundItems()
    {
        $now = $this->isNewRecord ? new \yii\db\Expression('NOW()') : $this->create_date;

        $q1 = Transfer::find()
                      ->active()
                      ->andWhere(['<=', 'got_at', $now])
                      ->select('purchase_id')
                      ->indexBy('purchase_id');

        if($prev = $this->prev)
            $q1->andWhere(['>', 'got_at', $prev->create_date]);

        if(Branch::PKEY_ATAMI == $this->branch_id)
            $q1->andWhere(['dst_id' => 0]);
        else
            $q1->andWhere(['dst_id' => $this->branch_id]);

        return TransferItem::find()
               ->andWhere(['purchase_id' => $q1->column()]);
    }

    /**
     * 前回の棚卸から現在までの出庫商品を抽出する
     * @return \yii\db\ActiveQuery
     */
    public function getTransferOutboundItems()
    {
        $now = $this->isNewRecord ? new \yii\db\Expression('NOW()') : $this->create_date;

        $q1 = Transfer::find()
                      ->active()
                      ->andWhere(['<=', 'posted_at', $now])
                      ->select('purchase_id')
                      ->indexBy('purchase_id');

        if($prev = $this->prev)
            $q1->andWhere(['>', 'posted_at', $prev->create_date]);

        if(Branch::PKEY_ATAMI == $this->branch_id)
            $q1->andWhere(['src_id' => 0]);
        else
            $q1->andWhere(['src_id' => $this->branch_id]);

        return TransferItem::find()
               ->andWhere(['purchase_id' => $q1->column()]);
    }

    public function getStatus()
    {
        return $this->hasOne(InventoryStatus::className(), ['istatus_id'=>'istatus_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return (InventoryStatus::PKEY_APPROVED == $this->istatus_id);
    }

    public function isSubmitted()
    {
        return (InventoryStatus::PKEY_SUBMIT == $this->istatus_id) || $this->isApproved();
    }

    /**
     * @return void
     */
    public function initItems()
    {
        $q1 = $this->getPurchaseItems()->asArray();
        $q2 = $this->getTransferInboundItems()->asArray();
        $q3 = $this->getTransferOutboundItems()->asArray();
        $q4 = ($prev = $this->getPrev())
             ? $prev->getItems()->where(['not', ['actual_qty' => 0 ]])->asArray()
             : InventoryItem::find()->where('0 = 1');
        $q5 = ProductMaster::find()->where(['not',['vial_id'=>[RemedyVial::DROP,
                                                               RemedyVial::MIDDLE_BOTTLE]]]);
        $branch = $this->branch;
        if($branch->isRemedyShop())
            $q5->andWhere(['company_id'=>Company::PKEY_HJ]);
        else
            $q5->andWhere('0 = 1');

        if(0 === array_sum([ $q1->count(),
                             $q2->count(),
                             $q3->count(),
                             $q4->count(),
                             $q5->count(),
        ]))
            return true; // nothing has been recorded, so no item is initialized
        
        $query = ProductMaster::find();
        $pid  = [];
        $pid  = array_merge($pid,  $q1->select('product_id')->distinct()->column());
        $pid  = array_merge($pid,  $q2->select('product_id')->distinct()->column());
        $pid  = array_merge($pid,  $q3->select('product_id')->distinct()->column());
        $pid  = array_merge($pid,  $q4->select('product_id')->distinct()->column());
        $pid  = array_unique($pid);

        $code = [];
        $code = array_merge($code, $q1->select('code')->distinct()->column());
        $code = array_merge($code, $q2->select('code')->distinct()->column());
        $code = array_merge($code, $q3->select('code')->distinct()->column());
        $code = array_merge($code, $q4->select('ean13')->distinct()->column());
        $code = array_merge($code, $q5->select('ean13')->distinct()->column());
        $code = array_unique($code);

        $query->orFilterWhere(['or',
                               ['product_id' => $pid  ],
                               ['ean13'      => $code ]]);

        if(100 < $query->count())
        {
//            ini_set("memory_limit",      "2048M"); // 2GB of total 32GB memory @ arnica.toyouke.com
            ini_set("max_execution_time",    300); // 5 min
        }

        if(0 < $this->getItems()->count())
            $exclude = $this->getItems()->select('ean13')->column();

        foreach($query->batch() as $rows) foreach($rows as $product)
        {
            if(isset($exclude) && in_array($product->ean13, $exclude))
                continue;

            $item = $this->initItem($product);

            if(! $item->save())
                Yii::error(['could not save InventoryItem','errors'=>$item->firstErrors,'attributes'=>$item->attributes,'class'=> $this->className(),'function'=>__FUNCTION__]);

            if(false !== ($k = array_search($product->product_id, $pid))) // found the value
                unset($pid[$k]);

            if(false !== ($k = array_search($product->ean13, $code)))
                unset($code[$k]);

            if($p = $product->product)
                if(false !== ($k = array_search($p->code, $code)))
                    unset($code[$k]);
        }

        if(empty($code))
            return;

        // ProductMasterから検索できなかった商品（販売終了）を抽出する
        $query = Product::find()->andWhere(['code'=>$code]);
        foreach($query->batch() as $rows) foreach($rows as $product)
        {
            $master = new ProductMaster(['category_id' => $product->category_id,
                                         'product_id'  => $product->product_id,
                                         'restrict_id' => $product->restrict_id,]);
            $item = $this->initItem($master);

            if(! $item->save())
                Yii::error(['could not save InventoryItem','errors'=>$item->firstErrors,'attributes'=>$item->attributes,'class'=> $this->className(),'function'=>__FUNCTION__]);

            if(false !== ($k = array_search($product->code, $code)))
                unset($code[$k]);
        }

        return;
    }

    private function initItem($product)
    {
        $item = new InventoryItem([
            'inventory_id' => $this->inventory_id,
            'ean13'        => $product->ean13,
            'kana'         => ($p = $product->product) ? $p->name : $product->kana,
            'product_id'   => $product->product_id,
            'updated_by'   => Staff::PKEY_NOBODY,
        ]);
        $item->actual_qty = max(0, $item->idealQty);
        $item->detachBehavior('staff');
        $item->calcurate();

        return $item;
    }

    public function updateItems()
    {
        foreach($this->getItems()->all() as $item)
        {
            $item->detachBehavior('staff');
            $item->calcurate();
            $item->save();
            unset($item);
        }
    }
}
