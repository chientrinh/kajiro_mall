<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Remedy.php $
 * $Id: Remedy.php 3262 2017-04-20 11:50:04Z kawai $
 *
 * This is the model class for table "mtb_remedy".
 *
 * @property integer $remedy_id
 * @property string $abbr
 * @property string $latin
 * @property string $ja
 * @property string $concept
 * @property string $advertise
 *
 * @property remedyStock[] $remedyStocks
 */
class Remedy extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_remedy';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
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
            [['abbr'], 'required'],
            [['abbr', 'latin', 'ja', 'concept', 'advertise'], 'filter', 'filter' => 'trim'],
            [['abbr', 'latin', 'ja', 'concept'], 'string', 'max' => 255, 'skipOnEmpty'=>true],
            [['abbr'], 'unique'],
            [['advertise'], 'string'],
            ['on_sale', 'default', 'value' => 1],
            ['on_sale', 'in',      'range' => [0, 1]],
            ['restrict_id', 'exist', 'targetClass' => ProductRestriction::className(), ],
            [['remedy_id', 'abbr', 'latin', 'ja', 'concept', 'advertise', 'on_sale'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'remedy_id' => 'Remedy ID',
            'abbr'      => "略称",
            'latin'     => "ラテン名",
            'ja'        => "日本語",
            'concept'   => "概要",
            'advertise' => "広告用の文言",
            'on_sale'   => "一般販売",
            'restrict_id'=> "公開区分",
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'abbr'       => '英数字のみで入力してください（ハイフン、スペースを含んでもいいです）',
            'on_sale'    => "そもそもこのレメディーは一般販売していいのかどうか、会社が下した判断を表わします。NGの場合、店頭には表示されなくなります",
            'concept'    => '店頭には表示されません。このレメディーの元物質についての情報などを入力できます',
            'restrict_id'=> "実際に販売する対象を定めます。制限をかけると店頭に表示されなくなります（ただし対象の顧客／店舗には表示されます）",
            'advertise'  => "商品紹介ページに掲載する文章です",
        ];
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public static function getCategory()
    {
        return Category::findOne(Category::REMEDY);
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public static function getCompany()
    {
        return Company::findOne(Company::PKEY_HJ);
    }

    /* @return string (might contain Html tags), stllen() up to 50  */
    public function getExcerpt($maxlen = 50)
    {
        $text = $this->advertise;

        if(! $text)
            return '';

        $text = trim(mb_convert_kana($text, "s")); // remove spaces
        $text = preg_replace('/<BR>/i', '', $text);

        if($maxlen < mb_strlen($text))
            $text = mb_substr($text, 0, $maxlen) . '...';

        return $text;
    }

    public function getImages()
    {
        $sku = sprintf('25%06d%%', $this->remedy_id);
        return ProductImage::find()->andWhere(['or',
                                               ['like', 'ean13', $sku, false],
                                               ['ean13' => RemedyStockJan::find()->where(['like','sku_id',$sku,false])
                                                                                 ->select('jan')
                                                                                 ->column()]])
                                   ->orderBy(['weight'=>SORT_DESC])
                                   ->all();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->abbr;
    }

    public function getNext()
    {
        return static::find()
            ->andWhere(['>','abbr', $this->abbr])
            ->orderBy('abbr ASC');
    }

    public function getPrev()
    {
        return static::find()
            ->andWhere(['<','abbr', $this->abbr])
            ->orderBy('abbr DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPotencies()
    {
        return $this->hasMany(RemedyStock::className(), ['remedy_id' => 'remedy_id'])->addGroupBy('potency_id');
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getRestriction()
    {
        return $this->hasOne(ProductRestriction::className(), ['restrict_id' => 'restrict_id']);
    }

    /**
     * get single selling remedies
     * @return RemedyStock[]
     */
    public function getProducts()
    {
        $stocks = $this->getStocks(false) // include drops
                       ->orderBy(['potency_id'=>SORT_ASC,'vial_id'=>SORT_ASC])
                       ->with('potency')
                       ->all();
        $models = [];
        $bottle = [];

        // remember ready-made bottles in $bottle
        foreach($stocks as $stock)
        {
            $key = $stock->potency_id;

            if(RemedyVial::DROP == $stock->vial_id)
                continue;

            if(! isset($bottle[$key]))
                $bottle[$key] = [];

            $bottle[$key][] = $stock->vial_id;
        }

        foreach($stocks as $stock)
        {
            $key = $stock->potency_id;

            if(RemedyVial::DROP == $stock->vial_id)
            {
                if(! $stock->in_stock) // Dropそのものが在庫切れ、滴下できない
                    continue;

                if(false === strpos($stock->potency->name,'LM')) // is not LM potency
                {
                    // プラ小瓶
                    if(! isset($bottle[$key]) || ! in_array(RemedyVial::SMALL_BOTTLE, $bottle[$key]))
                        $models[] = new RemedyStock([
                            'remedy_id'  => $stock->remedy_id,
                            'potency_id' => $stock->potency_id,
                            'prange_id'  => $stock->prange_id,
                            'restrict_id'=> $stock->restrict_id,
                            'vial_id'    => RemedyVial::SMALL_BOTTLE,
                        ]);

                    // プラ大瓶
                    if(! isset($bottle[$key]) || ! in_array(RemedyVial::LARGE_BOTTLE, $bottle[$key]))
                        $models[] = new RemedyStock([
                            'remedy_id'  => $stock->remedy_id,
                            'potency_id' => $stock->potency_id,
                            'prange_id'  => $stock->prange_id,
                            'restrict_id'=> $stock->restrict_id,
                            'vial_id'    => RemedyVial::LARGE_BOTTLE,
                        ]);
                }

                // アルポ(5ml)
                $models[] = new RemedyStock([
                    'remedy_id'  => $stock->remedy_id,
                    'potency_id' => $stock->potency_id,
                    'prange_id'  => $stock->prange_id,
                    'restrict_id'=> (0 == $stock->restrict_id) ? CustomerGrade::PKEY_AA : $stock->restrict_id,
                    'vial_id'    => RemedyVial::GLASS_5ML,
                ]);
            }
            else
                $models[] = $stock;
        }

        return $models;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStocks($filterDroppable=true)
    {
        $query = $this->hasMany(RemedyStock::className(), ['remedy_id' => 'remedy_id']);

        if($filterDroppable)
            $query->andOnCondition([ 'not', ['vial_id' => RemedyVial::DROP] ]);

        return $query;
    }

    public function getRemedyDescription()
    {
        return $this->hasMany(RemedyDescription::className(), ['remedy_id' => 'remedy_id']);
    }

    public function getRemedyAdDescription()
    {
        return RemedyDescription::getRemedyAdDescription($this);
    }

    public function getRemedyDescriptions()
    {
        return RemedyDescription::getRemedyDescriptions($this);
    }

    public function getDroppables($filterInStock=true,$filterConfidencial=true)
    {
        $query = $this->hasMany(RemedyStock::className(), [
            'remedy_id' => 'remedy_id',
        ])->onCondition([
            'vial_id'   => 10,
        ]);

        if($filterInStock)
            $query->andOnCondition([
                'not in', 'potency_id', \yii\helpers\ArrayHelper::getColumn($this->stocks, 'potency_id')
        ]);
        if($filterConfidencial)
            $query->andOnCondition([
                'between', 'potency_id', 1, 24 // everything except LM potencies
        ]);

        return $query;
    }

    public function getRemedyDescriptionForBack($category_id = null)
    {
        if (! $category_id)
            return $this->hasMany(RemedyDescription::className(), ['remedy_id' => 'remedy_id'])
                    ->addOrderBy(['remedy_category_id' => SORT_ASC, 'seq' => SORT_ASC, 'remedy_desc_id' => SORT_ASC]);

        return $this->hasMany(RemedyDescription::className(), ['remedy_id' => 'remedy_id'])
            ->andWhere(['remedy_category_id' => RemedyCategory::REMEDY_MT])
            ->addOrderBy(['remedy_category_id' => SORT_ASC, 'seq' => SORT_ASC, 'remedy_desc_id' => SORT_ASC])
            ->all();
    }

    public function isRestrictedTo($customer = null)
    {
        if(! $customer)
            return (0 < $this->restrict_id);

        if(($this->restrict_id < ProductRestriction::PKEY_INSTORE_ONLY) &&
            $customer->isAgencyOf(Company::PKEY_HJ))
                return false;

        return ($customer->getAttribute('grade_id') < $this->restrict_id);
    }

}
