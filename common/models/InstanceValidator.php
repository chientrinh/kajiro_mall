<?php
namespace common\models;

/**
 * Instance Validator
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/InstanceValidator.php $
 * $Id: InstanceValidator.php 1768 2015-11-05 09:39:52Z mori $
 */

class InstanceValidator extends \yii\validators\Validator
{
    public $className;

    public function validateAttribute($model, $attr)
    {
        if(! is_array($this->className))
            $this->className = [ $this->className ];

        foreach($this->className as $className)
            if($model->$attr instanceof $className)
                return true;

        $model->addError($attr, 'is not expected instance');
        return false;
    }
}
