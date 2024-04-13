<?php

namespace common\models\webdb18;

use Yii;

/**
 * This is the form class for table "tblcustomer".
 */
class CustomerForm extends \common\models\webdb\CustomerForm
{
    public $db = 'webdb18';

    public static function getCompany()
    {
        return \common\models\Company::findOne(\common\models\Company::PKEY_HJ);
    }
}
