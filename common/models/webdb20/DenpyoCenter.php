<?php

namespace common\models\webdb20;

use Yii;

/**
 * This is the model class for table "tmdenpyo_center".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/DenpyoCenter.php $
 * $Id: DenpyoCenter.php 1637 2015-10-11 11:12:30Z mori $
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
        return Yii::$app->get('webdb20');
    }

}
