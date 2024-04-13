<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_information".
 *
 * @property integer $info_id
 * @property integer $company_id
 * @property string $content
 * @property string $url
 * @property string $pub_date
 * @property string $update_date
 * @property integer $created_by
 * @property integer $updated_by
 *
 * @property MtbCompany $company
 * @property MtbStaff $createdBy
 * @property MtbStaff $updatedBy
 */
class Information extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_information';
    }

    /* @inheritdoc */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by','updated_by'],
                ],
                'value' => function ($event) {
                    try {
                        return Yii::$app->user->id;
                    }
                    catch (Exception $e) {
                        echo 'you need to control from \yii\web\Application';
                        throw $e;
                    }
                },
            ],
            [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url','content'],'trim'],
            [['company_id', 'content', 'pub_date'], 'required'],
            [['company_id', 'created_by', 'updated_by'], 'integer'],
            ['company_id', 'exist', 'targetClass'=>'\common\models\Company', 'targetAttribute'=>'company_id'],
            [['created_by','updated_by'], 'exist', 'targetClass'=>'\backend\models\Staff', 'targetAttribute'=>'staff_id'],
            [['pub_date', 'expire_date', 'update_date'], 'safe'],
            [['content'], 'string'],
            [['url'], 'string', 'max' => 255],
            ['url','url'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_id'  => '会社',
            'content'     => '本文',
            'url'         => 'URL',
            'pub_date'    => '掲載日',
            'expire_date' => '失効日',
            'update_date' => '更新日',
            'created_by'  => '作成者',
            'updated_by'  => '更新者',
        ];
    }

    public function attributeHints()
    {
        return [
            'company_id'  => 'このお知らせを発表する会社',
            'content'     => 'お知らせ本文',
            'url'         => '本文全体を修飾するURL、省略可',
            'pub_date'    => '掲載が始まる日',
            'expire_date' => '掲載を止める日、省略可',
            'update_date' => 'このお知らせを更新した日',
            'created_by'  => 'このお知らせを登録した従業員',
            'updated_by'  => 'このお知らせを更新した従業員',
        ];
    }

    public static function find()
    {
        return new ActiveQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'updated_by']);
    }

    public function isExpired()
    {
        if(! $this->expire_date)
            return false;

        if(time() < strtotime($this->expire_date))
           return false;

        return true;
    }

    public function renderContent()
    {
        $content = $this->content;
        if($this->url)
            $content = \yii\helpers\Html::a($this->content, $this->url);

        $content .= '<br>' . \yii\helpers\Html::tag('small',$this->company->name);
        return $content;
    }
}

class ActiveQuery extends \yii\db\ActiveQuery
{
    public function expired($status=true)
    {
        if($status)
            $this->andWhere('expire_date <= NOW()');
        else
            $this->andWhere('NOW() < expire_date OR expire_date IS NULL');

        return $this;
    }
}
