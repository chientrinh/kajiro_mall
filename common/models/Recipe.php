<?php

namespace common\models;

use Yii;
use \backend\models\Staff;

/**
 * This is the model class for table "dtb_recipe".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Recipe.php $
 * $Id: Recipe.php 4088 2018-12-20 07:07:05Z kawai $
 *
 * @property integer $recipe_id
 * @property integer $homoeopath_id
 * @property integer $client_id
 * @property integer $staff_id
 * @property string $create_date
 * @property string $update_date
 * @property integer $status
 * @property string $note
 *
 * @property MtbStaff $staff
 * @property DtbCustomer $client
 * @property DtbCustomer $homoeopath
 * @property DtbRecipeDeliv[] $dtbRecipeDelivs
 * @property DtbRecipeItem[] $dtbRecipeItems
 */
class Recipe extends \yii\db\ActiveRecord
{
    const STATUS_INIT    = 0;
    const STATUS_SOLD    = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_CANCEL  = 8;
    const STATUS_VOID    = 9;
    const STATUS_PREINIT = 10;

    const EXPIRE_AFTER   = 1123200; // 13 days == (60 * 60 * 24 * 13)

    const TEL_STR_MAX    = 11;

    const PUBLISH_ON = 1; // フロント公開中
    const PUBLISH_OFF = 0; // フロント非公開

    public $manual_client_age_disp;
    public $manual_protector_age_disp;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_recipe';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        $params = [
            'pkey' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'recipe_id',
                ],
                'value' => function ($event) {
                    return self::find()->select('recipe_id')->max('recipe_id') + 1;
                },
            ],
            'pw' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'pw',
                ],
                'value' => function ($event) {
                    $length = 4;
                    return \common\components\Security::generateRandomNumber($length);
                },
            ],
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    if($event->name == 'beforeUpdate' && $event->sender->getOldAttributes()['status'] == $this::STATUS_VOID) {
                        return $this->update_date;
                    }
                    return date('Y-m-d H:i:s');
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];

        if('app-backend' == Yii::$app->id)
            $params[] = [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['staff_id'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['staff_id'],
                ],
                'value' => function ($event) {
                        return Yii::$app->user->id;
                },
            ];

        return $params;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['homoeopath_id'], 'required'],
            [['homoeopath_id', 'client_id', 'staff_id', 'status', 'itv_id'], 'integer'],
            ['manual_client_age','validateClientAge','skipOnEmpty'=>true],
            ['manual_protector_age','validateProtectorAge','skipOnEmpty'=>true],
            ['tel','validateTel','skipOnEmpty'=>true],
            [['create_date', 'update_date'], 'safe'],
            ['staff_id', 'exist', 'targetClass' => Staff::className(), 'targetAttribute'=>'staff_id'],
            [['homoeopath_id','client_id'], 'exist', 'targetClass' => Customer::className(), 'targetAttribute'=>'customer_id'],
            ['homoeopath','validateHomoeopath','skipOnEmpty'=>true],
            ['pw', 'string', 'min' => 4, 'skipOnEmpty'=>true],
            ['status', 'default', 'value' => self::STATUS_INIT ],
            ['status', 'in', 'range' => [
                self::STATUS_INIT,
                self::STATUS_SOLD,
                self::STATUS_EXPIRED,
                self::STATUS_CANCEL,
                self::STATUS_VOID,
                self::STATUS_PREINIT,
            ]],
            [['note', 'center'], 'string', 'max' => 1024],
            [['manual_client_name', 'manual_protector_name'], 'string', 'max' => 255],
            [['client_id', 'manual_client_name'], 'validateClient'],
        ];
    }

    public function validateHomoeopath($attr, $params)
    {
        if(! $hpath = $this->homoeopath)
            return false;

        if(! $hpath->isHomoeopath() &&
           ! $hpath->isStudent() &&
           ! $hpath->isJphmatechnical()
        )
            $this->addError($attr, "ホメオパス資格者ではありません");

        if($this->hasErrors($attr))
            return false;

        return true;
    }

    public function validateClient($attr, $params)
    {
        if (! $this->client_id && ! $this->manual_client_name)
            $this->addError($attr, "クライアントを入力して下さい");

        if($this->hasErrors($attr))
            return false;

        return true;
    }

    public function validateClientAge($attr, $params)
    {
        if ($this->manual_client_age && ! preg_match('/[0-9]+/', $this->manual_client_age))
            $this->addError($attr, "クライアント（手入力）の年齢は半角数字で入力して下さい。");

        if($this->hasErrors($attr))
            return false;

        return true;
    }

    public function validateProtectorAge($attr, $params)
    {
        if ($this->manual_protector_age && ! preg_match('/[0-9]+/', $this->manual_protector_age))
            $this->addError($attr, "保護者（手入力）の年齢は半角数字で入力して下さい。");

        if($this->hasErrors($attr))
            return false;

        return true;
    }

    public function validateTel($attr, $params)
    {
        if ($this->tel && (! preg_match('/[0-9]+/', $this->tel)))// || strlen($this->tel)　> self::TEL_STR_MAX))
            $this->addError($attr, "電話番号は半角数字のみ（ハイフン無し）で入力して下さい。");

        if($this->hasErrors($attr))
            return false;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'recipe_id'             => '適用書NO',
            'homoeopath_id'         => 'ホメオパス',
            'client_id'             => 'クライアント',
            'items'                 => '品目',
            'staff_id'              => '担当',
            'create_date'           => '発行日',
            'update_date'           => '更新日',
            'expire_date'           => '失効日',
            'status'                => '状態',
            'note'                  => '備考',
            'pw'                    => 'パスワード',
            'manual_client_name'    => 'クライアント　名称（手入力）',
            'manual_client_age'     => 'クライアント　年齢（手入力）',
            'manual_client_birth'     => 'クライアント　生年月日（手入力）',
            'manual_protector_name' => '保護者　名称',
            'manual_protector_age'  => '保護者　年齢',
            'manual_protector_birth'  => '保護者　生年月日',
            'center'                => 'センター',
            'tel'                   => '電話番号',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new RecipeQuery(get_called_class());
    }

    public function sold() // would be performed by client
    {
        if(! in_array($this->status, [self::STATUS_INIT, self::STATUS_PREINIT]))
            return false;

        $this->status = self::STATUS_SOLD;
        return $this->save(false,['status','update_date','staff_id']);
    }

    /**
     * 購入がキャンセルされた場合にステータスを変更する（試作）
     *
     **/
    public function unSold() // would be performed by client
    {
        if(! in_array($this->status, [self::STATUS_SOLD]))
            return false;

        if($this->manual_client_name)
            $this->status = self::STATUS_PREINIT;
        else
            $this->status = self::STATUS_INIT;

        return $this->save(false,['status','update_date','staff_id']);
    }

    public function cancel() // would be performed by client
    {
        if(self::STATUS_CANCEL <= $this->status)
            return true;

        $this->status = self::STATUS_CANCEL;
        return $this->save(false,['status','update_date','staff_id']);
    }

    public function expire() // would be performed by Homoeopath and Staff
    {
        if($this->isExpired())
            return true;

        $this->status = self::STATUS_VOID;
        return $this->save(false,['status','update_date','staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'client_id']);
    }

    /* @return string */
    public function getExpire_Date()
    {
    if(! in_array($this->status, [self::STATUS_INIT, self::STATUS_PREINIT]))
        return $this->update_date;

        $created = $this->isNewRecord ? time() : strtotime($this->create_date);

        return date('Y-m-d 23:59:59', $created + self::EXPIRE_AFTER);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHomoeopath()
    {
        return $this->hasOne(Customer::className(), ['customer_id' => 'homoeopath_id']);
    }

    /**
     * @return integer
     */
    public function getItemCount()
    {
        return array_sum(\yii\helpers\ArrayHelper::getColumn($this->items, 'quantity'));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDelivery()
    {
        return $this->getClient();
    }

    public function isCancelled()
    {
        return (self::STATUS_CANCEL <= $this->status);
    }

    public function isExpired()
    {
        return (((self::STATUS_EXPIRED <= $this->status) && (self::STATUS_PREINIT != $this->status)) || (strtotime($this->expire_date) <= time()));
    }

    public function isSold()
    {
        return (self::STATUS_SOLD == $this->status);
    }

    public function isVoid()
    {
        return (self::STATUS_VOID <= $this->status);
    }

    public function isActive()
    {
        if ('app-backend' == Yii::$app->id)
            $status = [Recipe::STATUS_INIT, Recipe::STATUS_PREINIT];
        else
            // 発行・購入・仮発行のみ取得対象とする
            $status = [Recipe::STATUS_INIT, Recipe::STATUS_SOLD, Recipe::STATUS_PREINIT];

        return in_array($this->status, $status);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(RecipeItem::className(), ['recipe_id' => 'recipe_id']);
    }

    public function getParentItems()
    {
        return $this->hasMany(RecipeItem::className(), ['recipe_id' => 'recipe_id'])->where(['parent' => null]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatusName()
    {
        switch ($this->status)
        {
        case Recipe::STATUS_INIT    : return "発行";    // 0
        case Recipe::STATUS_SOLD    : return "購入";    // 1
        case Recipe::STATUS_EXPIRED : return "期限切れ"; // 2
        case Recipe::STATUS_CANCEL  : return "キャンセル"; // 8
        case Recipe::STATUS_VOID    : return "無効";    // 9
        case Recipe::STATUS_PREINIT : return "仮発行";  // 10
        default                     : return null;
        }
    }

    public function getManualClientAgeDisp()
    {
        return $this->computeAge($this->manual_client_birth);
    }

    public function getManualProtectorAgeDisp()
    {
        return $this->computeAge($this->manual_protector_birth);
    }

    public function computeAge($date = null) {
        if($date == null)
            return null;
        $date = mb_convert_kana(trim($date), "ask");
        // 長さでチェック
        if (8 != strlen($date)) {
            return null;
        // 年月日として妥当かチェック
        } else if (!checkdate(substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4))) {
            return null;
        } else {
            // 妥当（正常）な場合の処理
            $age = floor((date('Ymd') - $date) / 10000);
            return $age;
        }
    }
}

class RecipeQuery extends \yii\db\ActiveQuery
{
    public function active($target = true)
    {
        // 発行・購入・仮発行のみ取得対象とする
        $status = [Recipe::STATUS_INIT, Recipe::STATUS_SOLD, Recipe::STATUS_PREINIT];

        if ($target)
            return $this->andWhere(['in', 'status', $status]);

        return $this->andWhere(['not', ['in', 'status', $status]]);
    }
}
