<?php 
namespace common\models\webdb18;

use Yii;

class Customer extends \common\models\webdb\Customer
{
    protected static $schema = 'webdb18';

    public function init()
    {
        parent::init();
    }

    public static function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_HJ);
    }
}
