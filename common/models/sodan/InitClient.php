<?php

namespace common\models\sodan;

use Yii;

class InitClient extends \yii\base\Behavior
{
    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
        ];
    }

    /* @return void */
    public function afterInsert($event)
    {
        if($this->owner->hasAttribute('client_id'))
            $cid = $this->owner->client_id;

        if(! isset($cid))
            // i don't know how to create the model without primary key
            return;

        $this->insertClient($cid);
    }

    /* @return void */
    private function insertClient($client_id)
    {
        if(! $client_id || Client::findOne($client_id))
            return;

        $model = new Client([
            'client_id' => $client_id,
        ]);

        if(! $model->save())
            Yii::error([
                sprintf('saving Client failed for client_id(%d)', $client_id),
                $model->errors,
            ], self::className().'::'.__FUNCTION__);
    }

}
