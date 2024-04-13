<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_membercode".
 *
 * @link $URL: https://tarax.toyouke.com/svn/MALL/common/models/Membercode.php $
 * @version $Id: Membercode.php 3100 2016-11-23 02:49:07Z mori $
 *
 * @property string $code
 * @property integer $status
 * @property integer $customer_id
 *
 * @property DtbCustomer $customer
 */
class Membercode extends \yii\db\ActiveRecord
{
    const   AUTO_GENERATE_PREFIX = '0000';
    private $_checkdigit;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_membercode';
    }

    public static function getPrefix()
    {
        return Customer::EAN13_PREFIX;
    }

    public function init()
    {
        parent::init();

        $this->_checkdigit = new \common\components\ean13\CheckDigit();

        //$this->detachBehavior('newcode');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'newcode' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['code'],
                ],
                'value' => function ($event) {
                    $prefix = self::AUTO_GENERATE_PREFIX;
                    $digit  = 6;
                    do
                    {
                        $numbers = [];
                        foreach(range(1, $digit) as $i)
                            $numbers[] = mt_rand(0, 9);
                        $code = $prefix . implode('', $numbers);
                    }
                    while (1 <= Yii::$app->db->createCommand('SELECT COUNT(code) FROM '.self::tableName().' WHERE code = :c',[':c' => $code])->queryScalar());

                    return $code;
                },
            ],
            'status' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['status'],
                ],
                'value' => function ($event) {
                    $min = self::find()->where(['customer_id' => $this->customer_id])->min('status');
                    if(null === $min)
                        return 0;
                    else
                        return $min - 1;
                },
            ],
            'pw' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['pw'],
                ],
                'value' => function ($event) {
                    $length = 4;
                    return \common\components\Security::generateRandomString($length);
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
            'update'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'prefix', 'status', 'customer_id','migrate_id'], 'integer'],
            [['code'], 'string', 'length' => 10],
            [['code'], 'unique'],
            [['pw'], 'string', 'length' => 4],
            [['migrate_id'], 'integer'],
            [['customer_id'], 'exist', 'targetClass'=>Customer::className()],
            [['directive'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code'        => '会員証NO',
            'customer_id' => 'えびす顧客ID(非公開)',
            'directive'   => '旧データベース',
            'migrate_id'  => '旧ID',
            'pw'          => '仮パスワード',
        ];
    }

    public function getBarcode()
    {
        if(! $this->validate())
            return null;

        return $this->prefix . $this->code . $this->checkdigit;
    }

    public function getCheckdigit()
    {
        if(12 !== (strlen($this->prefix) + strlen($this->code)))
        {
            Yii::warning(sprintf('prefix or code is not valid in length: ("%s","%s")', $this->prefix, $this->code), $this->className().'::'.__FUNCTION__);
            return false;
        }

        return $this->_checkdigit->generate($this->prefix.$this->code);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return mixed (model | null)
     */
    public function getMigratedModel()
    {
        if('webdb18' == $this->directive)
            return \common\models\webdb18\SearchCustomer::findOne($this->migrate_id);

        if('webdb20' == $this->directive)
            return \common\models\webdb20\SearchCustomer::findOne($this->migrate_id);

        if('ecorange' == $this->directive)
            return \common\models\ecorange\Customer::findOne($this->migrate_id);

        if('eccube' == $this->directive)
            return \common\models\eccube\Customer::findOne($this->migrate_id);

        return null;
    }

    public function getNext()
    {
        return static::find()
            ->andWhere(['>','code', $this->code])
            ->orderBy('code ASC')
            ->one();
    }

    public function getPrev()
    {
        return static::find()
            ->andWhere(['<','code', $this->code])
            ->orderBy('code DESC')
            ->one();
    }

    /**
     * 一時的に発行した仮会員証NOであるかどうか
     * @return bool
     */
    public function isVirtual()
    {
        $prefix = self::AUTO_GENERATE_PREFIX;

        return $prefix == substr($this->code, 0, strlen($prefix));
    }
}

class GenerateUniqueCode extends \yii\behaviors\AttributeBehavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
        ];
    }

    public function afterInsert($event)
    {
        $profile = new Membercode([
            'customer_id'   => $this->customer_id,
        ]);

        $profile->save();
    }

}
