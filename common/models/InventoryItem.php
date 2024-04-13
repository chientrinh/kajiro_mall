<?php

namespace common\models;

use Yii;
use \backend\models\Staff;

/**
 * This is the model class for table "dtb_inventory_item".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/InventoryItem.php $
 * $Id: InventoryItem.php 2503 2016-05-12 09:38:58Z mori $
 *
 * This is the model class for table "dtb_inventory_item".
 *
 * @property integer $inventory_id
 * @property integer $product_id
 * @property integer $actual_qty
 * @property integer $diff_qty
 * @property integer $iitem_id
 * @property string  $ean13
 * @property string  $name
 * @property integer $updated_by
 *
 * @property Staff         $updator
 * @property Inventory     $inventory
 * @property ProductMaster $product
 */
class InventoryItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_inventory_item';
    }

    public function behaviors()
    {
        return [
            'staff' => [
                'class' => \yii\behaviors\BlameableBehavior::className(),
                'createdByAttribute' => false,
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ean13', 'actual_qty'], 'required'],
            [['inventory_id', 'product_id', 'actual_qty', 'diff_qty'], 'integer'],
            ['inventory_id', 'exist', 'targetClass'=> Inventory::className() ],
            ['product_id', 'exist', 'targetClass' => Product::className()],
            [['ean13'], 'unique', 'targetAttribute' => ['ean13','inventory_id'],'message'=>'その商品は登録済みです'],
            [['ean13'], 'string', 'length' => 13],
            [['kana'], 'filter', 'filter' => function($value){ return preg_replace('/ combination /',' ',$value); }],
            [['kana'], 'string', 'max' => 255],
            [['updated_by'], 'exist', 'targetClass'=> Staff::className(),'targetAttribute'=>'staff_id'],
            [['actual_qty'], 'integer', 'min' => 0, 'when'=>function($model){ return ! $model->isNewRecord; } ],
            [['iitem_id'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'inventory_id' => '棚卸しID',
            'product_id'   => '商品ID',
            'kana'         => '品名',
            'ean13'        => 'バーコード',
            'actual_qty'   => '棚卸数',
            'ideal_qty'    => '帳簿在庫',
            'in_qty'       => '入庫',
            'out_qty'      => '出庫',
            'prev_qty'     => '前回',
            'sold_qty'     => '売上',
            'diff_qty'     => '+/-',
            'updated_by'   => '更新者',
        ];
    }

    /* @return void */
    public function calcurate()
    {
        $this->prev_qty  = $this->getPrev();
        $this->in_qty    = $this->getInbound();
        $this->out_qty   = $this->getOutbound();
        $this->sold_qty  = $this->getSold();
        $this->ideal_qty = $this->getIdealQty();

        if((Staff::PKEY_NOBODY == $this->updated_by) &&
            ($this->actual_qty != max(0, $this->ideal_qty))
        )
             $this->actual_qty  = max(0, $this->ideal_qty);

        $this->diff_qty  = $this->actual_qty - $this->ideal_qty;
    }

    public function getCost()
    {
        return $this->hasOne(factory\ProductCost::className(),['ean13'=>'ean13']);

        /** TBD **
        $query = factory\ProductCost::find()->a;

        if($model = $this->inventory)
            $query->andWhere(['>=','start_date',$model->create_date])
                  ->andWhere(['<=','end_date',  $model->create_date]);

        if($this->product_id)
            $jan  = ProductJan::find()->where(['jan' => $this->product_id])->select('jan')->column();
        else
            $jan = Jancode::find()->where(['sku_id' => $this->ean13])->column();

        $query->andFilterWhere(['or',
                                ['ean13'=>$this->ean13],
                                ['ean13'=>$jan        ]]);
        if($query->exists())
            return $query->one()->cost;

        return null;
        */
   }

    /**
     * @return integer
     */
    public function getIdealQty()
    {
        if(! $model = $this->inventory)
            return 0;

        $prev     = $this->prev;
        $sold     = $this->sold;
        $inbound  = $this->inbound;
        $outbound = $this->outbound;

        return ($prev - $sold - $outbound + $inbound);
    }

    /**
     * @return integer
     */
    public function getInbound()
    {
        if(! $model = $this->inventory)
            return 0;

        $query = $model->getTransferInboundItems();
     
        if($this->product_id){ $query->andWhere(['product_id' => $this->product_id]); }
        elseif($this->ean13) { $query->andWhere(['code'       => $this->ean13]);      }
        else                 { $query->andWhere('1 = 0');                             }

        return (int) $query->sum('qty_shipped');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInventory()
    {
        return $this->hasOne(Inventory::className(), ['inventory_id' => 'inventory_id']);
    }

    /**
     * @return integer
     */
    public function getOutbound()
    {
        if(! $model = $this->inventory)
            return 0;

        $query = $model->getTransferOutboundItems();

        if($this->product_id){ $query->andWhere(['product_id' => $this->product_id]); }
        elseif($this->ean13) { $query->andWhere(['code'       => $this->ean13]);      }
        else                 { $query->andWhere('1 = 0');                             }

        return (int) $query->sum('qty_shipped');
    }

    /**
     * @return integer
     */
    public function getPrev()
    {
        if((! $model = $this->inventory) || ! $model->prev)
            return 0;

        return (int) $model->prev->getItems()
                     ->andWhere(['ean13' => $this->ean13])
                     ->sum('actual_qty');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(ProductMaster::className(), ['ean13' => 'ean13']);
    }

    /**
     * @return integer
     */
    public function getSold()
    {
        if(! $model = $this->inventory)
            return 0;

        $query = $model->getPurchaseItems();

        if($this->product_id){ $query->andWhere(['product_id' => $this->product_id]); }
        elseif($this->ean13) { $query->andWhere(['code'       => $this->ean13]);      }
        else                 { $query->andWhere('1 = 0');                             }

        return (int) $query->sum('quantity');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

}
