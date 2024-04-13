<?php

namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/ProductImage.php $
 * $Id: ProductImage.php 2957 2016-10-14 00:20:17Z mori $
 *
 * This is the model class for table "dtb_product_image".
 *
 * @property integer $img_id
 * @property string $ean13
 * @property string $ext
 * @property resource $content
 * @property string $caption
 * @property integer $weight
 * @property integer $created_at
 * @property integer $created_by
 */
class ProductImage extends \yii\db\ActiveRecord
{
    private $basePath = '@webroot/assets/images/';
    private $baseUrl  = '@web/assets/images/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if($manager = Yii::$app->get('assetManager'))
        {
            $this->basePath = $manager->basePath . '/images/';
            $this->baseUrl  = $manager->baseUrl  . '/images/';
        }
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_product_image';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ean13', 'ext', 'content', 'created_at', 'created_by'], 'required'],
            [['content'], 'string'],
            [['weight', 'created_at', 'created_by'], 'integer'],
            [['ean13'], 'string', 'max' => 13],
            [['ext', 'caption'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'touch' => [
                'class' => UpdateProduct::className(),
                'owner' => $this,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'img_id' => 'Img ID',
            'ean13' => 'Ean13',
            'ext' => 'Ext',
            'content' => 'Content',
            'caption' => 'Caption',
            'weight' => 'Weight',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
        ];
    }

    public function afterFind()
    {
        parent::afterFind();

        $path = Yii::getAlias($this->basePath . $this->basename);
        if(is_file($path) &&
           ($this->created_at < filemtime($path))
        )
            return;

        if(! is_dir($this->basePath))
            \yii\helpers\FileHelper::createDirectory($this->basePath, 0755, true);

        if(! $fp = fopen($path, 'w'))
            Yii::error("could not open '$path' for write");

        fwrite($fp, $this->content);
        fclose($fp);
    }

    public function getBasename()
    {
        return sprintf('%s_%s.%s', $this->ean13, $this->img_id, $this->ext);
    }

    public function getUrl()
    {
        return \yii\helpers\Url::to($this->baseUrl . $this->basename);
    }

    /* @return bool */
    public function exportContent($filename, $maxWidth=300, $overwrite=false)
    {
        $filename = Yii::getAlias($filename);

        if(! $overwrite && is_file($filename) && ($this->created_at < filemtime($filename)))
            return true;

        $tmp = tempnam("/tmp", 'IMG');
        $fp  = fopen($tmp, "w");
        fwrite($fp, $this->content);
        fclose($fp);

        $dir = dirname($filename);
        if(is_dir($dir))
        {
            chdir(dirname($filename));
            system("/usr/bin/convert -resize {$maxWidth}x{$maxWidth}\> {$tmp} {$filename}");
        }
        unlink($tmp);

        return is_file($filename);
    }

    /* @return bool */
    public function importContent($filename)
    {
        if(! $filename || ! is_file($filename) || ! is_readable($filename))
            return false;

        $fp = fopen($filename, 'r');
        $this->content = fread($fp, filesize($filename));
        fclose($fp);

        return $this->content ? true : false;
    }

}

/**
 * 画像を編集しただけではページキャッシュが更新されない、なのでProductMaster.update_dateを更新する
 */
class UpdateProduct extends \yii\base\Behavior
{
    public function events()
    {
        return [
            ProductImage::EVENT_AFTER_INSERT => 'touchProduct',
            ProductImage::EVENT_AFTER_UPDATE => 'touchProduct',
            ProductImage::EVENT_AFTER_DELETE => 'touchProduct',
        ];
    }

    public function touchProduct($event)
    {
        $ean13 = $this->owner->ean13;

        if($model = ProductMaster::findOne(['ean13'=>$ean13])
        )
            $model->save(false, ['update_date']);
   }
}
