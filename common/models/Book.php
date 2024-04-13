<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Book.php $
 * $Id: Book.php 2722 2016-07-15 08:38:22Z mori $
 */

namespace common\models;

use Yii;

/**
 * This is the model class for table "mtb_book".
 *
 * @property integer $product_id
 * @property integer $format_id
 * @property integer $page
 * @property string $pub_date
 * @property string $publisher
 * @property string $author
 * @property string $translator
 *
 * @property MtbBookFormat $format
 * @property DtbProduct $product
 */
class Book extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mtb_book';
    }

    public static function primaryKey()
    {
        return ['product_id'];
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

    // return primary key
    public function getP()
    {
        return $this->product_id;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['format_id', 'default', 'value'=> 1],
            [['product_id', 'format_id', 'pub_date', 'publisher', 'publisher'], 'required'],
            [['product_id', 'format_id', 'page'], 'integer'],
            [['pub_date', 'publisher', 'author', 'translator'], 'string', 'max' => 255],
            [['product_id'], 'unique'],
            [['isbn'],       'unique'],
            [['isbn'],       'string', 'length' => 13 ],
            [['isbn'],       'match',  'pattern'=>'/^9/','message'=>'先頭は9であるべきです'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id' => "Product ID",
            'format_id'  => "判型",
            'page'       => "ページ数",
            'pub_date'   => "発売日",
            'publisher'  => "出版社",
            'author'     => "著者",
            'translator' => "訳者",
            'isbn'       => "ISBN",
        ];
    }

    public function getActibook()
    {
        $path = sprintf('/actibook/%s/_SWF_Window.html', \yii\helpers\ArrayHelper::getValue($this,'product.code'));

        if(is_readable(Yii::getAlias('@webroot'.$path)))
            return '@web'.$path;

        return null;
    }

    public function getName()
    {
        if($this->product)
            return $this->product->name;

        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFormat()
    {
        return $this->hasOne(BookFormat::className(), ['format_id' => 'format_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['product_id' => 'product_id']);
    }

    public function getPrev()
    {
        return self::find()->where(['product_id' => $this->product_id - 1]);
    }

    public function getPubMonth()
    {
        if(preg_match('/^\d+-(\d+)/', $this->pub_date, $match))
            return $match[1];
    }

    public function getPubYear()
    {
        if(preg_match('/^(\d+)/', $this->pub_date, $match))
            return $match[1];
    }

    public function getNext()
    {
        return self::find()->where(['product_id' => $this->product_id + 1]);
    }

    /*---------- END OF GETTER METHODS -----------*/

    public static function findByBarcode($isbn)
    {
        return self::find()->where(['isbn' => $isbn])->one();
    }


}
