<?php
namespace common\models\sodan;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/FixHomoeopath.php $
 * $Id: FixHomoeopath.php 3851 2018-04-24 09:07:27Z mori $
 */

use Yii;

class FixHomoeopath extends \yii\base\Behavior
{
    public $homoeopath_id;

    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'initHomoeopath',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'initHomoeopath',
        ];
    }

    /* @return void */
    public function initHomoeopath($event)
    {
        if(! $this->homoeopath_id || Homoeopath::findOne($this->homoeopath_id))
            return;

        $model = new Homoeopath(['homoeopath_id' => $this->homoeopath_id]);
        $model->save();
    }
}
