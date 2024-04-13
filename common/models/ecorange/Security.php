<?php
namespace common\models\ecorange;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/Security.php $
 * $Id: Security.php 1231 2015-08-05 07:47:41Z mori $
 */

class Security extends \yii\base\Security
{
    private $_magic = "31eafcbd7a81d7b401a7fdc12bba047c02d1fae6";

    /* @return string */
    public function generatePasswordHash($password)
    {
        return sha1($password . ':' . $this->_magic);
    }

}
