<?php
namespace common\models;

use Yii;

/**
 * This is the model class for table "dtb_customer".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/NullCustomer.php $
 * $Id: NullCustomer.php 1569 2015-10-01 05:59:56Z mori $
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
class NullCustomer extends Customer
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
    public function getAddr()
    {
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddrbooks()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getAuthKey()
    {
        return null;
    }

    public function getCustomer_id()
    {
        return null;
    }

    public function currentPoint()
    {
        return 0;
    }

    public function getExpired()
    {
        return false; // not expired
    }

    public function getGrade()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getKana()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getMemberships()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPoint()
    {
        return 0;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSex()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavorites()
    {
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchases()
    {
        return null; // no history
    }

    public function getPasswordResetToken()
    {
        return null;
    }

    public function activate()
    {
        return null;
    }

    public function expire()
    {
        return null;
    }

    public static function findOne($condition)
    {
        return self;
    }

    public function findByPasswordResetToken($token)
    {
        return null;
    }

    public function generatePasswordResetToken()
    {
        return false;
    }

    public function isAgency()
    {
        return false;
    }

    public function isAgencyOf($pk)
    {
        return false;
    }

    public function removePasswordResetToken()
    {
        return null; // do nothing
    }

    public function updatePassword($password)
    {
        return false;
    }

    public function validateAuthKey($authKey)
    {
        return false; // authKey always invalid
    }

    public function validatePassword($password)
    {
        return false; // password always invalid
    }

    /**
     * @return boolean
     */
    public function beforeValidate()
    {
        return false; // validate() fails
    }

    /**
     * @return boolean: whether allow save() or not
     */
    public function beforeSave($insert)
    {
        
        return false; // never allow save()
    }
}

