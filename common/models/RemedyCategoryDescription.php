<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyCategoryDescription.php $
 * $Id: $
 */

namespace common\models;

use Yii;
use common\models\RemedyCategory;
use backend\models\Staff;

/**
 * This is the model class for table "mtb_remedy_category_description".
 */
class RemedyCategoryDescription extends \yii\db\ActiveRecord
{
    const COMBINATION   = 1;
    const MT            = 2;

    // 説明表示・非表示制御
    const FLG_OFF       = 0; // 非表示
    const FLG_ON        = 1; // 表示

    // 説明区分
    const DIV_AD        = 1; // 広告
    const DIV_REPLETION = 2; // 補足

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_category_description';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'   => [
                'class' => \yii\behaviors\TimestampBehavior::className(),
                'createdAtAttribute' => 'create_date',
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['body', 'is_display', 'desc_division', 'remedy_category_id', 'seq'], 'required'],
            [['title'], 'required',
                'when' => function($model) {
                            return ($model->desc_division == self::DIV_REPLETION);
            }],
            [['title'], 'string', 'max' => 255],
            [['seq'], 'integer', 'min' => 0],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'remedyCategory.remedy_category_name' => 'カテゴリ―名',
            'remedy_category_id' => 'カテゴリー',
            'title'              => '見出し',
            'body'               => '本文',
            'desc_division'      => '説明区分',
            'seq'                => '表示順',
            'is_display'         => '表示/非表示',
            'create_by'          => '作成者',
            'create_date'        => '作成日時',
            'update_by'          => '更新者',
            'update_date'        => '更新日時',
            'is_display'         => '表示/非表示'
        ];
    }

    /**
     * フォームでの入力補助を指定する
     * viewファイル上でActiveForm::beginの引数'template'に
     * {hint}を追加することで表示される
     *
     * @see \yii\base\Model::attributeHints()
     */
    public function attributeHints()
    {
        return [
            'title'              => '補足説明に表示する見出し（「使用方法」、「注意事項」など）を入力してください。',
            'body'               => '補足事項に表示する内容を入力してください。特殊文字（リンクで用いるaタグなど）も利用可能です。',
            'seq'                => '補足事項の表示順を指定できます。',
            'is_display'         => 'フロント画面への表示・非表示を指定してください。'
        ];
    }
    
    /*************************************************************************************
     ****
     **** Relations
     ****
     *************************************************************************************/

    /**
     * リレーション    レメディーカテゴリー
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRemedyCategory()
    {
        return $this->hasOne(RemedyCategory::className(), ['remedy_category_id' => 'remedy_category_id']);
    }

    /**
     * リレーション    作成者
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'create_by']);
    }

    /**
     * リレーション    更新者
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdator()
    {
        return $this->hasOne(Staff::className(), ['staff_id' => 'update_by']);
    }

    /*************************************************************************************
     ****
     **** General Methods
     ****
     *************************************************************************************/

    public function getPrev()
    {
        return self::find()
        ->andWhere(['<', 'remedy_category_desc_id', $this->remedy_category_desc_id])
        ->max('remedy_category_desc_id');
    }

    public function getNext()
    {
        return self::find()
        ->andWhere(['>', 'remedy_category_desc_id', $this->remedy_category_desc_id])
        ->min('remedy_category_desc_id');
    }

    public function active($status = self::FLG_ON)
    {
        return $this->andWhere(['is_display' => $status]);
    }

    public function getDisplayName()
    {
        if ($this->is_display == self::FLG_ON )
            return '表示';

        return '非表示';
    }

    /**
     * 説明区分の表示名を返す
     *
     * 引数を条件に表示名を返す
     *     └指定なしの場合は配列で全名称を返す
     *     └指定あり、且つ、配列内にその値がない場合はnullを返す
     *     └指定あり、且つ、配列内にその値がある場合はその名称を返す
     *
     * @param string $desc_division 説明区分
     * @return array | null | string 表示名
     */
    public function getDivisionForView($desc_division = null)
    {
        $selection = [
                        self::DIV_AD => '広告',
                        self::DIV_REPLETION => '補足'
        ];

        // 説明区分指定なし
        if (! $desc_division) return $selection;

        // 説明区分がリスト内にないキーの場合
        if (! array_key_exists($desc_division, $selection)) return null;

        return $selection[$desc_division];
    }

    /**
     * モデル内の説明区分が広告用（1）か判定する
     * @return boolean
     */
    public function isAd()
    {
        return ($this->desc_division == self::DIV_AD);
    }

    public function isRepletion()
    {
        return ($this->desc_division == self::DIV_REPLETION);
    }

     /**
     * レメディー広告用文言取得
     *
     * @return object(yii\db\ActiveQuery)
     */
    public function getCategoryAd()
    {
        return RemedyCategoryDescription::getDescription(RemedyCategoryDescription::DIV_AD);
    }

    /**
     * レメディー補足説明取得
     * 
     * @return object(yii\db\ActiveQuery)
     */
    public function getCategoryDescriptions()
    {
        return RemedyCategoryDescription::getDescription(RemedyCategoryDescription::DIV_REPLETION);
    }

    // /** 
    //  * 商品説明取得
    //  * @param integer 説明区分
    //  * @return object(yii\db\ActiveQuery)
    //  */
    // public function getDescription($desc_div)
    // {
    //     $r_category = RemedyCategory::getRemedyCategory($this);

    //     // レメディーカテゴリーが取得できなかった場合、又はレメディーIDが空の場合はfalseを返す
    //     if (! $r_category || empty($this->remedy_id) || ! array_key_exists($desc_div, RemedyDescription::getDivisionForView()))
    //         return [];

    //     $condition = [
    //         'remedy_id'          => $this->remedy_id, 
    //         'is_display'         => RemedyDescription::FLG_ON, 
    //         'desc_division'      => $desc_div,
    //         'remedy_category_id' => $r_category
    //     ];           

    //     $query = RemedyDescription::findByCondition($condition)
    //               ->addOrderBy(['seq'=> SORT_ASC, 'remedy_desc_id' => SORT_ASC]);

    //     $query->multiple = true;

    //     return $query;
    // }

    /**
     * レメディーカテゴリーごとの補足説明
     * @return unknown
     * @todo 引数の$remedyの内容確認
     */
    public function getDescription($desc_div)
    {
        $r_category = RemedyCategory::getRemedyCategory($this);

        // レメディーカテゴリーが取得できなかった場合
        if (! $r_category || ! array_key_exists($desc_div, RemedyCategoryDescription::getDivisionForView()))
            return [];

        $condition = [
            'remedy_category_id' => $r_category, 
            'is_display' => RemedyCategoryDescription::FLG_ON,
            'desc_division'      => $desc_div
        ];

        $query = RemedyCategoryDescription::find()->andWhere($condition)
        ->addOrderBy(['seq'=> SORT_ASC, 'remedy_category_desc_id' => SORT_ASC]);

        $query->multiple = true;

        return $query;
    }
}