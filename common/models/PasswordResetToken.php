<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/PasswordResetToken.php $
 * $Id: PasswordResetToken.php 923 2015-04-22 07:49:23Z mori $
 *
 * This is the model class for table "dtb_password_reset_token".
 *
 * @property string $create_date
 * @property string $email
 * @property string $token
 * @property string $expire_date
 * @property string $commit_date
 */

class PasswordResetToken extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wtb_password_reset_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'token', 'expire_date'], 'required'],
            [['email'],          'email'],
            [['expire_date'],    'safe'],
            [['email', 'token'], 'string', 'max' => 255],
            [['email', 'token'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Email',
            'token' => 'Token',
            'expire_date' => 'Expire Date',
        ];
    }

    public function init()
    {
        parent::init();

        if(! $this->token)
            $this->updateToken();
    }

    public static function find()
    {
        return new PasswordResetTokenQuery(get_called_class());
    }

    public function isActive()
    {
        return time() < strtotime($this->expire_date);
    }

    public function findByToken($token)
    {
        return static::find(['token' => $token])->active()->one();
    }

    public function updateToken()
    {
        $this->token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function beforeValidate()
    {
        if($this->isNewRecord)
        {
            // define expire date
            $this->expire_date = new \yii\db\Expression('NOW() + INTERVAL 1 HOUR');
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        // extend expire date
        if(false == $insert)
        {
            if(! $this->isActive()) // expired
                $this->updateToken(); // renew token
            
            $this->expire_date = new \yii\db\Expression('NOW() + INTERVAL 1 HOUR');
        }

       return parent::beforeSave($insert);
    }
}

class PasswordResetTokenQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        $now = new \yii\db\Expression('NOW()');

        if($state)
            return $this->andWhere(['>',  $now, 'expire_date']);
        else
            return $this->andWhere(['<=', $now, 'expire_date']);

    }
}
