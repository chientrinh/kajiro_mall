<?php

namespace common\models;

use Yii;
use \yii\helpers\ArrayHelper;
use \backend\models\Staff;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Transfer.php $
 * $Id: Transfer.php 3052 2016-10-30 01:03:22Z mori $
 *
 * This is the model class for table "dtb_transfer".
 *
 * @property integer $purchase_id
 * @property integer $src_id
 * @property integer $dst_id
 * @property string $create_date
 * @property string $update_date
 * @property string $asked_at
 * @property string $posted_at
 * @property string $got_at
 * @property integer $create_by
 * @property integer $update_by
 * @property integer $status_id
 * @property string $note
 *
 * @property MtbPurchaseStatus $status
 * @property MtbBranch $dst
 * @property MtbBranch $src
 * @property DtbTransferItem[] $dtbTransferItems
 */
class Transfer extends Purchase implements PurchaseInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_transfer';
    }

    public function behaviors()
    {
        return [
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return date('Y-m-d H:i:s');
                },
            ],
            'staff_id' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by','updated_by'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_by'],
                ],
                'value' => function ($event) {
                    return Yii::$app->user->id;
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
            ['status_id','default','value'=>TransferStatus::PKEY_INIT],
            ['payment_id','default','value'=>Payment::PKEY_NO_CHARGE],
            [['src_id', 'dst_id','status_id','payment_id'], 'required'],
            [['src_id', 'dst_id', 'created_by', 'updated_by', 'status_id'], 'integer'],
            [['src_id','dst_id'],'exist','targetClass'=>Branch::className(),'targetAttribute'=>'branch_id'],
            [['updated_by','created_by'],'exist','targetClass'=>\backend\models\Staff::className(),'targetAttribute'=>'staff_id'],
            ['status_id','exist','targetClass'=>TransferStatus::className()],
            [['create_date', 'update_date','asked_at','posted_at','got_at'], 'safe'],
            [['note'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'purchase_id' => '移動ID',
            'src_id'      => '発送所',
            'dst_id'      => '受取拠点',
            'asked_at'    => '発注日',
            'posted_at'   => '配送日',
            'got_at'      => '検収日',
            'create_date' => '起票日',
            'update_date' => '更新日',
            'created_by'  => '起票者',
            'updated_by'  => '更新者',
            'status_id'   => '状態',
            'note'        => '備考',
        ]);
    }

    public function afterValidate()
    {
        $this->updateStatus();

        return \yii\db\ActiveRecord::afterValidate();
    }

    public function cancelate()
    {
        if($this->isExpired())
            return true;

        $this->status_id = TransferStatus::PKEY_CANCEL;

        return $this->save(false);
    }

    public static function find()
    {
        return new TransferQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'created_by']);
    }

    public function getBranch()
    {
        return $this->getSrc();
    }

    public function getCustomer()
    {
        return new NullCustomer();
    }

    public function getCustomer_id()
    {
        return $this->customer->id;
    }
    
    public function getDelivery()
    {
        if(! $this->dst)
            return null;

        $attrs = ['zip01','zip02','pref_id','addr01','addr02','tel01','tel02','tel03'];
        $model = new PurchaseDelivery(['gift' => true,'name01'=>$this->dst->name]);

        foreach($attrs as $attr)
            $model->$attr = $this->dst->$attr;

        return $model;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDst()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'dst_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(TransferItem::className(), ['purchase_id' => 'purchase_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMails()
    {
        return $this->hasMany(MailLog::className(), ['pkey' => 'purchase_id'])->where(['tbl'=>self::tableName()]);
    }

    public function getNext()
    {
        return static::find()
            ->andWhere(['>','purchase_id', $this->purchase_id])
            ->orderBy('purchase_id ASC')
            ->one();
    }

    public function getPaid()
    {
        return (TransferStatus::PKEY_DONE == $this->status_id);
    }

    public function getPrev()
    {
        return static::find()
            ->andWhere(['<','purchase_id', $this->purchase_id])
            ->orderBy('purchase_id DESC')
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStat()
    {
        return $this->hasOne(TransferStatus::className(), ['status_id' => 'status_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSrc()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'src_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

    /* @return bool */
    public function isExpired()
    {
        return (TransferStatus::PKEY_CANCEL <= $this->status_id);
    }

    public function updateStatus()
    {
        if(($this->status_id < TransferStatus::PKEY_DONE) && $this->shipped && $this->paid)
            $this->status_id = TransferStatus::PKEY_DONE;

        $now = new \yii\db\Expression('NOW()');

        if(! $this->getDirtyAttributes(['status_id']))
            return;

        switch($this->status_id)
        {
        case TransferStatus::PKEY_ASKED:
            $this->asked_at  = $now;
            break;

        case TransferStatus::PKEY_POSTED:
            $this->posted_at = $now;
            break;

        case TransferStatus::PKEY_RECEIVED:
            $this->got_at    = $now;
            break;

        default:
            break;
        }
    }
}

class TransferQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere(['<=', 'dtb_transfer.status_id', TransferStatus::PKEY_DONE]);
        else
            return $this->andWhere(['>',  'dtb_transfer.status_id', TransferStatus::PKEY_DONE]);
    }
}
