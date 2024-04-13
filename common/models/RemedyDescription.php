<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/RemedyDescription.php $
 * $Id: $
 */

namespace common\models;

use Yii;
use common\models\RemedyCategory;
use backend\models\Staff;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "mtb_remedy_description".
 *
 * @property integer $potency_id
 * @property string $name
 * @property integer $weight
 */
class RemedyDescription extends \yii\db\ActiveRecord
{
    const COMBINATION   = 1;
    const MT           = 2;

    // 説明表示・非表示制御
    const FLG_OFF       = 0; // 非表示
    const FLG_ON        = 1; // 表示

    // 説明区分
    const DIV_AD        = 1; // 広告
    const DIV_REPLETION = 2; // 補足


    // シナリオ
    const SCENARIO_CASE_DESC_IS_REPLETION = 'caseDescIsRepletion';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy_description';
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
            [['remedy_category_id', 'seq'], 'default', 'value' => '0']
//             ['title', 'unique',
//             'targetClass' => RemedyDescription::className(),
//             'filter'      => ['=', 'remedy_category_id', $this->remedy_category_id],
//             'message'     => "入力されたレメディーカテゴリーと見出しの組み合わせはすでに登録されています。",
//             ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'remedy_id'          => 'レメディー名',
            'remedyCategory.remedy_category_name' => 'カテゴリ―',
            'remedy_category_id' => 'カテゴリー',
            'title'              => '見出し',
            'body'               => '本文',
            'seq'                => '表示順',
            'desc_division'      => '説明区分',
            'is_display'         => '表示/非表示',
            'create_date'        => '作成日時',
            'update_date'        => '更新日時'
        ];
    }

    public function attributeHints()
    {
        return [
            // 'remedy_category_id' => '説明区分明区分が「補足」の場合、カテゴリー指定は必須です。',
            'title'              => '補足説明に表示する見出し（「使用方法」、「注意事項」など）を入力してください。',
            'body'               => '商品説明に表示する内容を入力してください。特殊文字（リンクで用いるaタグなど）も利用可能です。',
            'seq'                => '商品説明の表示順を指定できます。',
            'is_display'         => 'フロント画面への表示・非表示を指定してください。'
        ];
    }

    /*************************************************************************************
     ****
     **** Scenarios
     ****
     *************************************************************************************/


    public function scenarios()
    {
        return array_merge(parent::scenarios(),[
                self::SCENARIO_CASE_DESC_IS_REPLETION => ['desc_division'],
                ]);
    }


    /*************************************************************************************
     ****
     **** Relations
     ****
     *************************************************************************************/

    /**
     * リレーション    レメディー
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRemedy()
    {
        return $this->hasOne(Remedy::className(), ['remedy_id' => 'remedy_id']);
    }

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
        ->andWhere(['<', 'remedy_desc_id', $this->remedy_desc_id])
        ->max('remedy_desc_id');
    }

    public function getNext()
    {
        return self::find()
        ->andWhere(['>', 'remedy_desc_id', $this->remedy_desc_id])
        ->min('remedy_desc_id');
    }

    public function active($status = self::FLG_ON)
    {
        return $this->andWhere(['is_display' => $status]);
    }

    public function getDisplayName($pulldown = false)
    {
        $array_display = [self::FLG_ON => '表示', self::FLG_OFF => '非表示'];

        if (!$pulldown)
            return $array_display[$this->is_display];

        return $array_display;
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
     * レメディー広告用文言取得
     *
     * @return object(yii\db\ActiveQuery)
     */
    public function getRemedyAdDescription()
    {
        return RemedyDescription::getDescription(RemedyDescription::DIV_AD);
    }

    /**
     * レメディー補足説明取得
     *
     * @return object(yii\db\ActiveQuery)
     */
    public function getRemedyDescriptions()
    {
        return RemedyDescription::getDescription(RemedyDescription::DIV_REPLETION);
    }

    /**
     * 商品説明取得
     * @param integer 説明区分
     * @return object(yii\db\ActiveQuery)
     */
    public function getDescription($desc_div)
    {
        $r_category = RemedyCategory::getRemedyCategory($this);

        // レメディーカテゴリーが取得できなかった場合、又はレメディーIDが空の場合はfalseを返す
        if (! $r_category || empty($this->remedy_id) || ! array_key_exists($desc_div, RemedyDescription::getDivisionForView()))
            return [];

        $condition = [
            'remedy_id'          => $this->remedy_id,
            'is_display'         => RemedyDescription::FLG_ON,
            'desc_division'      => $desc_div,
            'remedy_category_id' => $r_category
        ];

        $query = RemedyDescription::findByCondition($condition)
                  ->addOrderBy(['seq'=> SORT_ASC, 'remedy_desc_id' => SORT_ASC]);

        $query->multiple = true;

        return $query;
    }

    public function search($params)
    {
        $query = self::find();//->addOrderBy('remedy_desc_id');;

        $dataProvider = new ActiveDataProvider([
                'query' => $query,
                ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->remedy_id)
            $query->joinWith('remedy')->andFilterWhere(['like', 'abbr', $this->remedy_id]);

        $query->andFilterWhere(['=', 'remedy_category_id', $this->remedy_category_id]);
        $query->andFilterWhere(['=', 'desc_division', $this->desc_division]);
        $query->andFilterWhere(['=', 'is_display', $this->is_display]);
        $query->andFilterWhere(['like', 'title', $this->title]);
        $query->andFilterWhere(['like', 'body', $this->body]);

        return $dataProvider;
    }
}