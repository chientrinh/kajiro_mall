<?php
namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_customer".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/NullCompany.php $
 * $Id: NullCompany.php 1113 2015-06-28 06:49:42Z mori $
 *
 * @property integer $customer_id
 * @property string $name01
 * @property string $name02
 * @property string $kana01
 * @property string $kana02
 * @property integer $sex_id
 * @property string $birth
 * @property string $email
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 *
 * @property MtbSex $sex
 * @property MtbPref $pref
 * @property DtbCustomerAddrbook[] $dtbCustomerAddrbooks
 * @property DtbCustomerFavorite[] $dtbCustomerFavorites
 * @property DtbPurchase[] $dtbPurchases
 */
class NullCompany extends Company
{
    public function init()
    {
        parent::init();

        foreach($this->attributes as $k => $v)
        {
            $this->setAttribute($k, null);
        }
    }

    /**
     * @return string
     */
    public function getCompany_Id()
    {
        return null;
    }
}

