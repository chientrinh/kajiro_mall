<?php

namespace common\models\sodan;

use Yii;

/**
 * Client
 * 健康相談のクライアントを表現するための Model
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/Client.php $
 * $Id: Client.php 4145 2019-03-29 06:20:34Z kawai $
 */

class Client extends \yii\db\ActiveRecord
{
    public static function find()
    {
        return new ClientQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_client';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'log' => [
                'class'  => \common\models\ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
            'staff_id' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [],
                'value' => function ($event) {
                    if(! Yii::$app->get('user',false) || ! Yii::$app->user->identity instanceof \backend\models\Staff)
                        return null;

                    return Yii::$app->user->id;
                },
            ],
            'client' => [
                'class'     => FixClient::className(),
                'client_id' => $this->client_id,
            ],
            'date' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
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
            [['skype'], 'trim'],
            [['skype','note'], 'string', 'min'=>1],
            [['client_id', 'branch_id', 'animal_flg', 'homoeopath_id', 'ng_flg', 'parent_name'], 'safe'],
            [['client_id', 'animal_flg', 'ng_flg'], 'required']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'client_id' => '顧客ID',
            'skype'     => 'Skype名',
            'membership'=> '会員種別',
            'agreement' => '同意書',
            'questionnaires' => '質問票',
            'binaries'  => 'その他の資料',
            'note'      => '注記',
            'branch_id' => '所属拠点',
            'report'    => '事前報告書',
            'kana'      => 'かな',
            'name'      => '氏名',
            'animal_flg'=> '顧客区分',
            'homoeopath_id' => '担当ホメオパス',
            'ng_flg'    => '公開NGフラグ',
            'parent_name' => '保護者名',
            'photo'     => 'クライアント写真',
            'grade_id'  => 'ランク'
        ];
    }

    public function attributeHints() {
        return [
            'ng_flg' => '情報公開NGのクライアントの場合は「NG」を選択してください'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClient()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'client_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id']);
    }

    public function getAttribute($name)
    {
        if($this->customer->canGetProperty($name))
            return $this->customer->$name;

        return parent::getAttribute($name);
    }

    public function getAgreement()
    {
        return $this->hasMany(\common\models\BinaryStorage::className(), [
                      'pkey'     => 'client_id',
        ])->andWhere(['tbl_name' => $this->tableName(),
                      'property' => 'agreement',
        ]);
    }

    public function getBinaries()
    {
        return $this->hasMany(\common\models\BinaryStorage::className(),[
                      'pkey'     => 'client_id',
        ])->andWhere(['tbl_name' => $this->tableName()
        ])->andWhere(['not', ['property' => ['agreement','questionnaire', 'report']]
        ]);
    }

    public function getCustomer()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'client_id']);
    }

    public function getInterviews()
    {
        return $this->hasMany(Interview::className(), ['client_id' => 'client_id'])->andWhere(['IN', 'status_id', [1, 2, 3, 4]]);
    }

    public function getHomoeopath()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'homoeopath_id']);
    }

    public function getKana()
    {
        return $this->getAttribute('kana');
    }

    public function getMembership()
    {
        return $this->customer->getMemberships()->active()->toranoko()->one();
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function getQuestionnaires()
    {
        return $this->hasMany(\common\models\BinaryStorage::className(), [
                      'pkey'     => 'client_id',
        ])->andWhere(['tbl_name' => $this->tableName(),
                      'property' => 'questionnaire',
        ]);
    }

    public function getReport()
    {
        return $this->hasMany(\common\models\BinaryStorage::className(), [
                      'pkey'     => 'client_id',
        ])->andWhere(['tbl_name' => $this->tableName(),
                      'property' => 'report',
        ]);
    }

    public function getRecipes()
    {
        return $this->customer->getRecipes()->andWhere(['in', 'status', [0, 1]]);
    }

    public function getWaitlist()
    {
        return $this->hasMany(WaitList::className(),['client_id' => 'client_id'])
                    ->andWhere(['itv_id'=>null]);
    }

    public function getCouponLog()
    {
        return $this->hasMany(ClientCouponLog::className(), ['client_id' => 'client_id']);
    }

    /* @brief 動物相談を受けたことがあれば、動物と判定する
     * @return bool
     */
    public function isAnimal()
    {
        return $this->animal_flg;
    }

    public function isFemale()
    {
        if($sex = $this->getAttribute('sex'))
            if(\common\models\Sex::PKEY_FEMALE == $sex->sex_id)
                return true;

        return false;
    }

    public function isMale()
    {
        if($sex = $this->getAttribute('sex'))
            if(\common\models\Sex::PKEY_MALE == $sex->sex_id)
                return true;

        return false;
    }

    public function isValid()
    {
        if(! $this->agreement)
            $this->addError('agreement',"同意書が未回収です");

        if(! $this->questionnaires)
            $this->addError('questionnaires',"質問票が未回収です");

        if(! $this->membership)
            $this->addError('customer',"有効なとらのこ会員ではありません");

        if($this->hasErrors())
            return false;

        return true;
    }

    public function beforeSave($insert)
    {
        if (!$this->isNewRecord) {
            $this->updated_by = Yii::$app->user->identity->attributes['staff_id'];
            $this->update_date = date('Y/m/d H:i:s');
        }
        return parent::beforeSave($insert);
    }
}

class ClientQuery extends \yii\db\ActiveQuery
{
    public function init()
    {
        parent::init();
    }

    public function active($state = true)
    {
        $this->joinWith('customer');

        if($state)
            return $this->andWhere('NOW() <= dtb_customer.expire_date');
        else
            return $this->andWhere('dtb_customer.expire_date < NOW()');
    }
}

