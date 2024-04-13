<?php

namespace common\models\webdb20;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/RecipeItem.php $
 * $Id: RecipeItem.php 2664 2016-07-06 08:36:09Z mori $
 *
 * This is the model class for table "tblfukuyo".
 *
 * @property integer $fukuyoid
 * @property integer $customerid
 * @property integer $karuteid
 * @property integer $syohoid
 * @property integer $dsp_num
 * @property integer $fukuyo_timeid
 * @property integer $d_item_1_syohin_nameid
 * @property integer $d_item_4_syohin_nameid
 * @property integer $fukuyo_count
 * @property integer $d_item_lot_syohinid
 * @property integer $fukuyo_termid
 * @property string $fukuyo_detail
 * @property integer $fukuyo_time_no
 * @property string $fukuyo_coment
 * @property integer $seal_f
 * @property integer $den_reg
 *
 * @property Tblcustomer $customer
 * @property Tblkarute $karute
 * @property TmdItem4SyohinName $dItem4SyohinName
 * @property TmdItemLotSyohin $dItemLotSyohin
 * @property TmfukuyoTerm $fukuyoTerm
 */
class RecipeItem extends \common\models\webdb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblfukuyo';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb20');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['customerid', 'karuteid', 'syohoid', 'dsp_num', 'fukuyo_timeid', 'd_item_1_syohin_nameid', 'd_item_4_syohin_nameid', 'fukuyo_count', 'd_item_lot_syohinid', 'fukuyo_termid', 'fukuyo_time_no', 'seal_f', 'den_reg'], 'integer'],
            [['fukuyo_detail', 'fukuyo_coment'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fukuyoid'       => 'Fukuyoid',
            'customerid'     => 'Customerid',
            'karuteid'       => 'Karuteid',
            'syohoid'        => 'Syohoid',
            'dsp_num'        => 'Dsp Num',
            'fukuyo_timeid'  => 'Fukuyo Timeid',
            'd_item_1_syohin_nameid' => 'Remedy',
            'd_item_4_syohin_nameid' => 'Potency',
            'fukuyo_count'   => 'Fukuyo Count',
            'd_item_lot_syohinid' => 'D Item Lot Syohinid',
            'fukuyo_termid'  => 'Fukuyo Termid',
            'fukuyo_detail'  => '備考',
            'fukuyo_time_no' => 'Fukuyo Time No',
            'fukuyo_coment'  => '内訳',
            'seal_f'         => 'Seal F',
            'den_reg'        => 'Den Reg',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'customerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getKarute()
    {
        return $this->hasOne(Karute::className(), ['karuteid' => 'karuteid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPotency()
    {
        return $this->hasOne(Potency::className(), ['d_item_4_syohin_nameid' => 'd_item_4_syohin_nameid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRemedy()
    {
        return $this->hasOne(Remedy::className(), ['d_item_1_syohin_nameid' => 'd_item_1_syohin_nameid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItemLotSyohin()
    {
        return $this->hasOne(TmdItemLotSyohin::className(), ['d_item_lot_syohinid' => 'd_item_lot_syohinid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTerm()
    {
        return $this->hasOne(FukuyoTerm::className(), ['fukuyo_termid' => 'fukuyo_termid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTime()
    {
        return $this->hasOne(FukuyoTime::className(), ['fukuyo_timeid' => 'fukuyo_timeid']);
    }
}
