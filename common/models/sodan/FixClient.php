<?php
namespace common\models\sodan;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/FixClient.php $
 * $Id: FixClient.php 3851 2018-04-24 09:07:27Z mori $
 */

use Yii;

class FixClient extends \yii\base\Behavior
{
    public $client_id;

    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'initClient',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'initClient',
        ];
    }

    /* @return void */
    public function initClient($event)
    {
        if(! $this->client_id || Client::findOne($this->client_id))
            return;

        $model = new Client(['client_id' => $this->client_id]);
        $model->save();
    }
}
