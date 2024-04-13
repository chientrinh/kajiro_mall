<?php

namespace common\models\webdb18;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb18/Denpyo.php $
 * $Id: Denpyo.php 1819 2015-11-16 20:33:44Z mori $
 */

use Yii;

class Denpyo extends \common\models\webdb\Denpyo
{
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb18');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCenter()
    {
        return $this->hasOne(DenpyoCenter::className(), ['denpyo_centerid' => 'denpyo_centerid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(DenpyoItem::className(), ['denpyoid' => 'denpyoid']);
    }

}
