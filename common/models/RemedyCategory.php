<?php

namespace common\models;

use Yii;
use \common\components\ean13\CheckDigit;
use common\models\Branch;
use backend\models\Staff;
use common\components\cart\MixedCart;
use yii\helpers\ArrayHelper;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Stock.php $
 * $Id: $
 *
 * This is the model class for table "dtb_stock".
 *
 * @property integer $veg_id
 * @property string $name
 * @property string $kana
 * @property string $create_date
 * @property string $update_date
 */
class RemedyCategory extends \yii\db\ActiveRecord
{
    const REMEDY_WHOLE = '0'; // レメディー全般
    const REMEDY_SUGER = 1; // レメディー(砂糖玉)
    const REMEDY_MT    = 2; // マザーティンクチャ―
    const REMEDY_FE    = 3; // フラワーエッセンス

    /**
     * テーブル名指定
     *
     * @return string テーブル名
     */
    public static function tableName()
    {
        return 'mtb_remedy_category';
    }

    /**
     * テーブル更新時の振る舞い
     *
     * @return array 設定内容
     */
    public function behaviors()
    {
        return [
            'update'   => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'updatedAtAttribute' => 'update_date',
                'value' => function ($event) { return new \yii\db\Expression('NOW()'); },
            ],
            'log' => [
                'class'  => ChangeLogger::className(),
                'owner'  => $this,
                'user'   => Yii::$app->has('user') ? Yii::$app->user : null,
            ],
        ];
    }

    public function optimisticLock(){}

    /**
     * 入力規則
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /*
     * 項目ラベル表示設定
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * リレーション    拠点
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(Branch::className(), ['branch_id' => 'branch_id']);
    }

    /**
     * リレーション    更新者
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * リレーション    商品
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['product_id' => 'product_id']);
    }

    public static function getRemedyCategoryPulldown()
    {
        $remedy_categories = self::find()->addOrderBy(['remedy_category_id' => SORT_ASC])->asArray()->all();
        return ArrayHelper::map($remedy_categories, 'remedy_category_id','remedy_category_name');
    }

    /**
     * レメディーのカテゴリーを判定し、カテゴリー値を返す
     * 3:           砂糖玉
     * 2:           マザーティンクチャー
     * 1:           フラワーエッセンス
     * それ以外：   false
     * @return boolean|number
     */
    public function getRemedyCategory($remedy)
    {

        if ( ($remedy->hasAttribute('category_id') && Category::REMEDY == $remedy->category_id) || ! $remedy->hasAttribute('vial_id'))
            return false;

        // 砂糖玉
        if (in_array($remedy->vial_id, RemedyVial::isRemedySuger()))
            return RemedyCategory::REMEDY_SUGER;

        // フラワーエッセンス
        if (in_array($remedy->vial_id, RemedyVial::isRemedyFE()) && ((preg_match("/^FE\)/", $remedy->name) || preg_match("/^FE2/", $remedy->name))))
            return RemedyCategory::REMEDY_FE;

        // マザーティンクチャー
        if (in_array($remedy->vial_id, RemedyVial::isRemedyMT()))
            return RemedyCategory::REMEDY_MT;

        return false;
    }

}
