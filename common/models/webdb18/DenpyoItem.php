<?php

namespace common\models\webdb18;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb18/DenpyoItem.php $
 * $Id: DenpyoItem.php 1819 2015-11-16 20:33:44Z mori $
 */

use Yii;

class DenpyoItem extends \common\models\webdb\DenpyoItem
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
    public function getDenpyo()
    {
        return $this->hasOne(Denpyo::className(), [
            'denpyo_num'          => 'denpyo_num',
            'denpyo_num_division' => 'denpyo_num_division',
        ]);
    }
}
