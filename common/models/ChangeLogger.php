<?php 

namespace common\models;

/**
 * ChangeLogger: saving data to trace 'who has changed which record in which table'
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ChangeLogger.php $
 * $Id: ChangeLogger.php 2808 2016-08-05 05:57:13Z mori $
 */

use Yii;
use yii\db\ActiveRecord;

class ChangeLogger extends \yii\base\Behavior
{
    /**
     * @var current user ID (of Customer, or Staff)
     */
    public $user;

    /* @inheritdoc */
    public function init()
    {
        parent::init();
    }

    /* @inheritdoc */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT  => 'insertLog',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'updateLog',
            ActiveRecord::EVENT_AFTER_DELETE  => 'deleteLog',
        ];
    }

    /* @return int */
    public function getUserid()
    {
        return $this->user ? $this->user->id : null;
    }

    /* @return void */
    public function insertLog($event)
    {
        if($this->validate())
            static::insertRecord('insert', $this->userid, $this->owner);
    }

    /* @return void */
    public function updateLog($event)
    {
        if($this->validate())
            static::insertRecord('update', $this->userid, $this->owner);
    }

    /* @return void */
    public function deleteLog($event)
    {
        if($this->validate())
            static::insertRecord('delete', $this->userid, $this->owner);
    }

    /* @return void */
    public static function insertRecord($action, $userid, $model)
    {
        if(in_array($action, ['update','delete']))
            $before = $model->oldAttributes;
        else
            $before = null;

        $after = $model->attributes;

        if($before)
        foreach($before as $k => $v)
        {
            if($v == $after[$k])
            {
                unset($before[$k]);
                unset($after[$k]);
            }
        }

        $route = \yii\helpers\ArrayHelper::getValue(Yii::$app, 'controller.route');
        $model = new ChangeLog(['action' => $action,
                                'tbl'    => $model->tableName(),
                                'pkey'   => $model->getPrimaryKey(),
                                'user_id'=> $userid,
                                'route'  => implode(';',[Yii::$app->id, $route]),
                                'before' => $before,
                                'after'  => $after]);

        if(! $model->save())
            Yii::error(['ChangeLog::save() failed:', $model->firstErrors]);
    }

    /* @return bool */
    private function validate()
    {
        if(! $this->owner instanceof \yii\db\ActiveRecord)
        {
            Yii::error(['owner is not acceptable',$this->owner->className()], $this->className().'::'.__FUNCTION__);
            return false;
        }

        return true;
    }

}

