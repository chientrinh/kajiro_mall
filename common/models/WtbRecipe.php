<?php

namespace common\models;

use Yii;
use \yii\db\ActiveRecord;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/WtbRecipe.php $
 * $Id: WtbRecipe.php 1950 2016-01-07 00:55:53Z mori $
 *
 * This is the model class for table "wtb_purchase" which is used as session storage
 * @see frontend/modules/cart/Module.php
 * @see backend/modules/dispatch/Module.php
 *
 * @property integer $session
 * @property integer $homoeopath_id
 * @property string $data
 * @property string $expire
 */
class WtbRecipe extends ActiveRecord
{
    const WAIT_LIMIT = 86400; // 24 Hours == 60 * 60 * 24

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wtb_recipe';
    }

    public function rules()
    {
        return [
            ['homoeopath_id','filter','filter'=> function($value){ return (0 - abs(Yii::$app->user->id)); }, 'when'=>function(){ return ('app-frontend' !== Yii::$app->id); }, ],
            ['homoeopath_id','exist','targetClass'=>Customer::className(), 'targetAttribute'=>'customer_id','when'=>function(){ return ('app-frontend' === Yii::$app->id); } ],
            ['homoeopath_id','required'],
            ['homoeopath_id','integer'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'expire',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'expire',
                ],
                'value' => function ($event) {
                    return time() + self::WAIT_LIMIT;
                },
            ],
        ];
    }

    public function isExpired()
    {
        return $this->expire && ($this->expire < time());
    }

    public static function fetchOne(\yii\base\Application $app)
    {
        if('app-frontend' == $app->id)
            $userid = $app->user->id;
        else // app-backend
            $userid = 0 - abs($app->user->id);

        if(! $row = self::find()->where(['homoeopath_id' => $userid])->one())
             $row = new WtbRecipe([
                 'homoeopath_id' => $userid,
                 'session'       => $app->has('session')
                     ? $app->session->id
                     : \common\components\Security::generateRandomString(36)
             ]);

        if($row->isExpired())
            $row->data = null;

        return $row;
    }

    public static function removeOne(\yii\base\Application $app)
    {
        if('app-frontend' == $app->id)
            $userid = $app->user->id;
        else // app-backend
            $userid = 0 - abs($app->user->id);

        if(! $model = self::find()->where(['homoeopath_id' => $userid])->one())
            return true;

        return $model->delete();
    }
}
