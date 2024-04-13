<?php
namespace common\models;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/FileForm.php $
 * $Id: FileForm.php 1777 2015-11-08 17:36:20Z mori $
 */

use Yii;
use yii\web\UploadedFile;

class FileForm extends \yii\base\Model
{
    /**
     * @var UploadedFile
     */
    public $tgtFile;

    public function rules()
    {
        return [
            [['tgtFile'], 'required'],
            [['tgtFile'], 'file',
             'extensions' => ['txt', 'doc', 'pdf', 'html', 'xml', 'gif', 'png', 'jpg', 'jpeg'],
             'maxSize'    => 100000000, // 100 MB
            ],
        ];
    }

}
