<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tblkarute".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/Karute.php $
 * $Id: Karute.php 2664 2016-07-06 08:36:09Z mori $
 *
 * @property integer $karuteid
 * @property integer $customerid
 * @property string $karute_date
 * @property string $karute_syuso
 * @property integer $syoho_homeopathid
 * @property string $karute_fax_data
 *
 * @property KaruteItems[] $items
 */
class Karute extends \common\models\webdb\ActiveRecord
{
    const SCENARIO_SEARCH = 'search';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tblkarute';
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
            [['karuteid'], 'safe', 'on'=> self::SCENARIO_SEARCH],
            [['customerid', 'syoho_homeopathid'], 'integer'],
            [['karute_date', 'karute_syuso', 'karute_fax_data'], 'string'],
            ['karute_date', 'date', 'format' => 'yyyy/MM/dd'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'karuteid' => 'カルテID',
            'customerid' => '顧客ID',
            'karute_date' => '作成日',
            'karute_syuso' => '主訴',
            'syoho_homeopathid' => 'ホメオパス',
            'karute_fax_data' => 'Karute Fax Data',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customerid' => 'customerid']);
    }

    public function getSyohos()
    {
        return $this->hasOne(Syoho::className(), ['karuteid' => 'karuteid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(KaruteItem::className(), ['karuteid' => 'karuteid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHomoeopath()
    {
        return $this->hasOne(KaruteHomoeopath::className(), ['syoho_homeopathid' => 'syoho_homeopathid']);
    }

    public function getNext()
    {
        return self::find()->where(['>','karuteid',$this->karuteid])
                           ->orderBy('karuteid ASC')
                           ->one();
    }

    public function getPrev()
    {
        return self::find()->where(['<','karuteid',$this->karuteid])
                           ->orderBy('karuteid DESC')
                           ->one();
    }
}
