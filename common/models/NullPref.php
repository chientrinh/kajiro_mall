<?php

namespace common\models;

use Yii;

/**
 * This is the null model class for table "mtb_pref".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/NullPref.php $
 * $Id: NullPref.php 1223 2015-08-02 01:35:03Z mori $
 *
 * @property integer $pref_id
 * @property string $name
 */

class NullPref extends NullActiveRecord
{
    public $pref_id;
    public $name;
    
    public function init()
    {
        parent::init();

        $this->pref_id = null;
        $this->name    =   '';
    }

    public static function primaryKey()
    {
        return 'pref_id';
    }

    public function attributes()
    {
        return ['pref_id','name'];
    }

    public function getPrimaryKey($asArray = false)
    {
        return $this->pref_id;
    }

    public function getOldPrimaryKey($asArray = false)
    {
        return $this->pref_id;
    }

    public static function isPrimaryKey($keys)
    {
        return ($keys === ['pref_id']);
    }

}
