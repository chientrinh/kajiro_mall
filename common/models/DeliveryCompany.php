<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/DeliveryCompany.php $
 * $Id: DeliveryCompany.php 3175 2020-09-28 06:16:14Z mori $
 *
 * This is the model class for table "mtb_delivery_company".
 *
 * @property integer $facility_id
 * @property integer $customer_id
 * @property string $name
 * @property string $title
 * @property string $summary
 * @property string $email
 * @property string $url
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 * @property string $fax01
 * @property string $fax02
 * @property string $fax03
 * @property string $pub_date
 * @property string $update_date
 *
 * @property DtbCustomer $customer
 * @property MtbPref $pref
 */
class DeliveryCompany extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_delivery_company';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            // 'update'=>[
            //     'class' => \yii\behaviors\AttributeBehavior::className(),
            //     'attributes' => [
            //         \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['update_date'],
            //         \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => ['update_date'],
            //     ],
            //     'value' => function ($event) {
            //         return new \yii\db\Expression('NOW()');
            //     },
            // ],
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
            [['delivery_company_id'], 'integer'],
            [['name', ], 'string', 'max' => 255],
            ['note', 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'delivery_company_id' => '配送会社ID',
            'name'        => '配送会社名',
            'note'        => '備考',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name'        => '配送会社の名前',
            'note'        => 'メモとして利用、他では表示されません',
        ];
    }


}
