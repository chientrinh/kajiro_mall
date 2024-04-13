<?php

namespace common\models\webdb18;

use Yii;

/**
 * This is the model class for table "tmdenpyo_center".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb18/DenpyoCenter.php $
 * $Id: DenpyoCenter.php 1819 2015-11-16 20:33:44Z mori $
 *
 * @property integer $denpyo_centerid
 * @property string $denpyo_center
 */
class DenpyoCenter extends \common\models\webdb\DenpyoCenter
{
     /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('webdb18');
    }

}
