<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the model class for table "tbld_item".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/DenpyoItem.php $
 * $Id: DenpyoItem.php 1819 2015-11-16 20:33:44Z mori $
 *
 * @property integer $d_itemid
 * @property integer $denpyoid
 * @property integer $denpyo_num
 * @property string $denpyo_num_division
 * @property integer $customerid
 * @property integer $d_item_delid
 * @property integer $d_item_chinkiid
 * @property integer $d_item_print_remedy_f
 * @property integer $d_item_syohinid
 * @property integer $d_item_2syohinid
 * @property integer $d_item_1_syohin_nameid
 * @property integer $d_item_2_syohin_nameid
 * @property integer $d_item_4_syohin_nameid
 * @property string $d_item_syohin_num
 * @property integer $d_item_syohin_count
 * @property integer $d_item_lot_syohinid
 * @property integer $d_item_syohin_tanka
 * @property integer $d_item_syohin_amount
 * @property string $d_item_lot_division
 * @property integer $d_item_lot_numid
 * @property string $d_item_name
 * @property string $d_item_kana
 * @property string $d_item_1_division
 * @property string $d_item_2_division
 * @property string $d_item_tel
 * @property string $d_item_fax
 * @property string $d_item_date
 * @property string $d_item_coment
 * @property string $d_item_1_syohin_name_hidden
 * @property string $denpyo_num_sub
 * @property integer $d_item_waribiki
 * @property integer $d_item_std_tanka
 * @property integer $commission_rate
 * @property string $commission
 * @property string $expire_date
 * @property string $expire_time
 * @property string $download_rest_num
 *
 * @property TmdItem1SyohinName $dItem1SyohinName
 * @property TmdItem2SyohinName $dItem2SyohinName
 * @property TmdItem2syohin $dItem2syohin
 * @property TmdItem4SyohinName $dItem4SyohinName
 * @property TmdItemChinki $dItemChinki
 * @property TmdItemDel $dItemDel
 * @property TmdItemLotSyohin $dItemLotSyohin
 * @property TmdItemSyohin $dItemSyohin
 */
abstract class DenpyoItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbld_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['denpyoid', 'denpyo_num', 'customerid', 'd_item_delid', 'd_item_chinkiid', 'd_item_print_remedy_f', 'd_item_syohinid', 'd_item_2syohinid', 'd_item_1_syohin_nameid', 'd_item_2_syohin_nameid', 'd_item_4_syohin_nameid', 'd_item_syohin_count', 'd_item_lot_syohinid', 'd_item_syohin_tanka', 'd_item_syohin_amount', 'd_item_lot_numid', 'd_item_waribiki', 'd_item_std_tanka', 'commission_rate'], 'integer'],
            [['denpyo_num_division', 'd_item_syohin_num', 'd_item_lot_division', 'd_item_name', 'd_item_kana', 'd_item_1_division', 'd_item_2_division', 'd_item_tel', 'd_item_fax', 'd_item_date', 'd_item_coment', 'd_item_1_syohin_name_hidden', 'denpyo_num_sub', 'commission', 'expire_date', 'expire_time', 'download_rest_num'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'd_itemid' => 'D Itemid',
            'denpyoid' => 'Denpyoid',
            'denpyo_num' => 'Denpyo Num',
            'denpyo_num_division' => 'Denpyo Num Division',
            'customerid' => 'Customerid',
            'd_item_delid' => 'D Item Delid',
            'd_item_chinkiid' => 'D Item Chinkiid',
            'd_item_print_remedy_f' => 'D Item Print Remedy F',
            'd_item_syohinid' => 'D Item Syohinid',
            'd_item_2syohinid' => 'D Item 2syohinid',
            'd_item_1_syohin_nameid' => 'D Item 1 Syohin Nameid',
            'd_item_2_syohin_nameid' => 'D Item 2 Syohin Nameid',
            'd_item_4_syohin_nameid' => 'D Item 4 Syohin Nameid',
            'd_item_syohin_num' => 'D Item Syohin Num',
            'd_item_syohin_count' => 'D Item Syohin Count',
            'd_item_lot_syohinid' => 'D Item Lot Syohinid',
            'd_item_syohin_tanka' => 'D Item Syohin Tanka',
            'd_item_syohin_amount' => 'D Item Syohin Amount',
            'd_item_lot_division' => 'D Item Lot Division',
            'd_item_lot_numid' => 'D Item Lot Numid',
            'd_item_name' => 'D Item Name',
            'd_item_kana' => 'D Item Kana',
            'd_item_1_division' => 'D Item 1 Division',
            'd_item_2_division' => 'D Item 2 Division',
            'd_item_tel' => 'D Item Tel',
            'd_item_fax' => 'D Item Fax',
            'd_item_date' => 'D Item Date',
            'd_item_coment' => 'D Item Coment',
            'd_item_1_syohin_name_hidden' => 'D Item 1 Syohin Name Hidden',
            'denpyo_num_sub' => 'Denpyo Num Sub',
            'd_item_waribiki' => 'D Item Waribiki',
            'd_item_std_tanka' => 'D Item Std Tanka',
            'commission_rate' => 'Commission Rate',
            'commission' => 'Commission',
            'expire_date' => 'Expire Date',
            'expire_time' => 'Expire Time',
            'download_rest_num' => 'Download Rest Num',
        ];
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItem1SyohinName()
    {
        return $this->hasOne(TmdItem1SyohinName::className(), ['d_item_1_syohin_nameid' => 'd_item_1_syohin_nameid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItem2SyohinName()
    {
        return $this->hasOne(TmdItem2SyohinName::className(), ['d_item_2_syohin_nameid' => 'd_item_2_syohin_nameid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItem2syohin()
    {
        return $this->hasOne(TmdItem2syohin::className(), ['d_item_2syohinid' => 'd_item_2syohinid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItem4SyohinName()
    {
        return $this->hasOne(TmdItem4SyohinName::className(), ['d_item_4_syohin_nameid' => 'd_item_4_syohin_nameid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItemChinki()
    {
        return $this->hasOne(TmdItemChinki::className(), ['d_item_chinkiid' => 'd_item_chinkiid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDItemDel()
    {
        return $this->hasOne(TmdItemDel::className(), ['d_item_delid' => 'd_item_delid']);
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
    public function getDItemSyohin()
    {
        return $this->hasOne(TmdItemSyohin::className(), ['d_item_syohinid' => 'd_item_syohinid']);
    }
}
