<?php
namespace common\models\sodan;

use Yii;

/**
 * This is the model class for table "dtb_sodan_wait_list".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/sodan/WaitList.php $
 * $Id: WaitList.php 3851 2018-04-24 09:07:27Z mori $
 *
 * @property integer $client_id
 * @property integer $branch_id
 * @property integer $homoeopath_id
 * @property string $note
 * @property integer $itv_id
 * @property string $expire_date
 * @property string $create_date
 * @property string $update_date
 * @property integer $created_by
 * @property integer $updated_by
 *
 * @property DtbSodanInterview $itv
 * @property MtbBranch $branch
 * @property DtbCustomer $client
 * @property MtbStaff $createdBy
 * @property DtbCustomer $homoeopath
 * @property MtbStaff $updatedBy
 */
class WaitList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_wait_list';
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
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_by','updated_by'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_by'],
                ],
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
            'homoeopath' => [
                'class'         => FixHomoeopath::className(),
                'homoeopath_id' => $this->homoeopath_id,
            ],
            'update' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
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
            [['client_id', 'expire_date'], 'required'],
            [['client_id', 'branch_id', 'homoeopath_id', 'itv_id', 'created_by', 'updated_by'], 'integer'],
            [['expire_date', 'create_date', 'update_date'], 'safe'],
            [['client_id','homoeopath_id'],'exist','targetClass'=>\common\models\Customer::className(),'targetAttribute'=>'customer_id'],
            ['client_id','uniqueClient'],
            [['created_by','updated_by'],'exist','targetClass'=>\backend\models\Staff::className(),'targetAttribute'=>'staff_id'],
            ['branch_id','exist','targetClass'=>\common\models\Branch::className()],
            [['note'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'wait_id'        => '待ちID',
            'branch_id'      => '拠点',
            'homoeopath_id'  => 'ホメオパス',
            'client_id'      => 'クライアント',
            'expire_date'    => 'キャンセル待ち期限',
            'itv_id'         => '相談会ID',
            'note'           => '備考',
            'create_date'    => '起票日',
            'update_date'    => '更新日',
            'created_by'     => '起票者',
            'updated_by'     => '更新者',
        ];
    }

    public function attributeHints()
    {
        return [
            'homoeopath_id'  => 'どのホメオパスに相談したいか、特に希望がある場合に指定します',
        ];
    }

    public function cancelate()
    {
        if($this->isExpired())
            return true;

        $this->expire_date = new \yii\db\Expression('NOW()');
        return $this->save();
    }

    /**
     * @inheritdoc
     * @return DtbSodanWaitListQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WaitListQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id']);
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
    public function getCreator()
    {
        return $this->hasOne(\backend\models\Staff::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHomoeopath()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'homoeopath_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInterview()
    {
        return $this->hasOne(Interview::className(), ['itv_id' => 'itv_id']);
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
        return(strtotime($this->expire_date) < time());
    }

    /**
     * validation: client_id must be unique
     */
    public function uniqueClient($attr, $params)
    {
        $q = static::find()->active()
                           ->andWhere(['client_id'=>$this->client_id]);

        if(! $this->isNewRecord)
            $q->andWhere(['not',['wait_id'=>$this->wait_id]]);

        if(! $q->exists())
            return true;

        $this->addError($attr, "クライアントはキャンセル待ち済みです");
        return false;
    }
}

class WaitListQuery extends \yii\db\ActiveQuery
{
    public function active($stat=true)
    {
        if($stat)
            return $this->andWhere('NOW() < expire_date')
                        ->andWhere(['itv_id'=>null]);
        else
            return $this->andWhere(['not',['itv_id'=>null]]);
    }
}
