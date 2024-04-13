<?php

namespace common\models\sodan;

use Yii;

/**
 * Homoeopath
 * 健康相談のホメオパスを表現するための Model
 *
 * @property integer $homoeopath_id
 * @property string $schedule
 *
 */
class Homoeopath extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_sodan_homoeopath';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['branch_id', 'del_flg', 'homoeopath_id'], 'required'],
            [['schedule'], 'string'],
            [['branch_id', 'branch_id2', 'branch_id3', 'branch_id4', 'branch_id5', 'homoeopath_id'], 'safe']
        ];
    }

    public function __get($name)
    {
        if(in_array($name, ['name','kana']))
            return ($model = $this->getModel()->one()) ? $model->__get($name) : null;

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'homoeopath_id' => 'ホメオパス',
            'branch_id'     => '所属拠点',
            'schedule'      => '担当日時',
            'del_flg'       => 'ステータス',
            'branch_id2'     => '所属拠点2',
            'branch_id3'     => '所属拠点3',
            'branch_id4'     => '所属拠点4',
            'branch_id5'     => '所属拠点5',
        ];
    }

    public function attributeHints()
    {
        return [
            'branch_id2' => '複数拠点で活動するホメオパスの場合、選択してください。',
            'branch_id3' => '複数拠点で活動するホメオパスの場合、選択してください。',
            'branch_id4' => '複数拠点で活動するホメオパスの場合、選択してください。',
            'branch_id5' => '複数拠点で活動するホメオパスの場合、選択してください。',
        ];
    }

    public static function find()
    {
        return new HomoeopathQuery(get_called_class());
    }

    // 削除フラグ値取得（0:有効、未削除 1:無効、削除）
    public static function getDelFlg()
    {
        return ['有効', '無効'];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClients()
    {
        return $this->hasMany(Client::className(), ['client_id' => 'client_id'])
                    ->viaTable(Interview::tableName(), ['homoeopath_id' => 'homoeopath_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHolidays()
    {
        return $this->hasMany(Holiday::className(), ['homoeopath_id' => 'homoeopath_id'])
            ->andWhere(['active'=>1]);
    }

    public function getOpenTime()
    {
        return $this->hasMany(Open::className(), ['homoeopath_id' => 'homoeopath_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInterviews()
    {
        return $this->hasMany(Interview::className(), ['homoeopath_id' => 'homoeopath_id'])->andWhere(['IN', 'status_id', [1, 2, 3, 4]]);
    }

    public function getCustomer()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'homoeopath_id']);
    }

    public function getBranch()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id']);
    }

    public function getBranch2()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id2']);
    }

    public function getBranch3()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id3']);
    }

    public function getBranch4()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id4']);
    }

    public function getBranch5()
    {
        return $this->hasOne(\common\models\Branch::className(), ['branch_id' => 'branch_id5']);
    }

    public function getMultiBranchName()
    {
        $multi_branch_name = '（所属無し）';

        if ($this->branch) {
            $multi_branch_name = $this->branch->name . "<br>";
        }

        if ($this->branch2) {
            $multi_branch_name .= $this->branch2->name . "<br>";
        }

        if ($this->branch3) {
            $multi_branch_name .= $this->branch3->name . "<br>";
        }

        if ($this->branch4) {
            $multi_branch_name .= $this->branch4->name . "<br>";
        }

        if ($this->branch5) {
            $multi_branch_name .= $this->branch5->name;
        }

        return $multi_branch_name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getModel()
    {
        return $this->hasOne(\common\models\Customer::className(), ['customer_id' => 'homoeopath_id']);
    }
}

class HomoeopathQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['del_flg' => 0]);
    }

    public function multibranch($branch_id)
    {
        if (!$branch_id) {
            return $this;
        }
        return $this->andWhere(['branch_id' => $branch_id])->orWhere(['branch_id2' => $branch_id])->orWhere(['branch_id3' => $branch_id])->orWhere(['branch_id4' => $branch_id])->orWhere(['branch_id5' => $branch_id]);
    }
}
