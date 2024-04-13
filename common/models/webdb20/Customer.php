<?php 
namespace common\models\webdb20;

use Yii;

class Customer extends \common\models\webdb\Customer
{
    protected static $schema = 'webdb20';

    public function init()
    {
        parent::init();
    }

    public static function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_HE);
    }
}
