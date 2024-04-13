<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%dtb_product}}".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Product.php $
 * $Id: Product.php 2838 2016-08-12 07:17:17Z mori $
 *
 * @property integer $product_id
 * @property integer $category_id
 * @property string  $code
 * @property string  $name
 * @property integer $price
 * @property string  $start_date
 * @property string  $expire_date
 *
 * @property customerFavorite[] $customerFavorites
 * @property inventoryItem[] $inventoryItems
 * @property manufactureItem[] $manufactureItems
 * @property mMaterialInventoryItem[] $materialInventoryItems
 * @property category $category
 * @property productDiscount[] $productDiscounts
 * @property productJan[] $productJans
 * @property productPoint[] $productPoints
 * @property purchaseItem[] $purchaseItems
 * @property storageItem[] $ptorageItems
 * @property productMaterial[] $productMaterials
 */

class DateManagement extends \yii\db\ActiveRecord
{
    const DATETIME_MAX = '3000-12-31 00:00:00';

    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_date_management';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'date'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    // \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
                    // \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
                ],
                'value' => function ($event) {
                    return date('Y-m-d H:i:s');
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
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }

    /**------------------ GETTER METHODS ------------------**/

    /**
     * 日付管理マスタの時刻を取得する
     * @param string $table_name　最終実施の日付を取得する対象テーブル名
     * @param boolean $update 最終実施の日付更新処理用の場合はtrueを指定、それ以外は指定無し
     * @return false | object common\models\DateManagement 日付管理マスタのデータ | string 最終実施日時
     */
    public function getLastExecutedAt($table_name, $update=false)
    {
        $conditions = ['=', 'table_name', $table_name];

        $date = DateManagement::find()->andWhere($conditions)->one();

        if (! $date || !($date->hasAttribute('last_executed_at')) )
            return false;

        if ($update)
            return $date;

        return $date->last_executed_at;
    }

    /**
     * 日付管理マスタの更新
     * @param string $table_name 最終実施の日付を取得する対象テーブル名
     * @return booean 処理結果
     */
    public function updateExecutedAt($table_name)
    {
        $target = DateManagement::getLastExecutedAt($table_name, true);

        if (!$target)
            return false;

        $target->last_executed_at = date('Y-m-d H:i:s');
        return $target->save();
    }
}
