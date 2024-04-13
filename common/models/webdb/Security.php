<?php
namespace common\models\webdb;

/**
 * $URL: http://test-webhj.homoeopathy.co.jp:8000/svn/MALL/common/models/ecorange/Security.php $
 * $Id: Security.php 1231 2015-08-05 07:47:41Z mori $
 */

abstract class Security extends \yii\base\Security
{
    const CIPHER = MCRYPT_BLOWFISH;
    const MODE   = MCRYPT_MODE_CBC;

    /* @return string */
    public function generatePasswordHash($password)
    {
        return static::_encrypt($password);
    }

    private static function _encrypt($text)
    {
        $text = static::_padding($text);

        $encrypt = mcrypt_encrypt(self::CIPHER, 'my secret key', $text, self::MODE, '12kak234');

        return array_shift(unpack('H*', $encrypt));
    }
    
    private static function _padding($text)
    {
        $block   = mcrypt_get_block_size(self::CIPHER, self::MODE);
        $len     = strlen($text);
        $padding = $block - ($len % $block);
        $text   .= str_repeat(chr(0), $padding);

        return $text;
    }

}
