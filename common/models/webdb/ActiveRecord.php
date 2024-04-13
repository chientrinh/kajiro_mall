<?php

namespace common\models\webdb;

use Yii;

/**
 * This is the abstract class for webdb{18,20} models
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/ActiveRecord.php $
 * $Id: ActiveRecord.php 1637 2015-10-11 11:12:30Z mori $
 *
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        if('euc-jp' == $this->db->charset)
            foreach($this->attributes as $attr => $value)
                if(mb_detect_encoding($value, ['CP51932'])) // is value EUC-WIN-JP ?
                    $this->$attr = mb_convert_encoding($value, 'UTF-8', 'CP51932');// convert to utf8

        if(strpos($this->db->dsn, 'pgsql') !== false)
            foreach($this->attributes as $attr => $value)
                if(is_numeric($this->$attr))
                    $this->$attr = (int) $this->$attr; // PostgreSQL needs integers to be integer
                elseif('' === $this->$attr)
                    $this->$attr = null;

        return parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if('euc-jp' == $this->db->charset)
            foreach($this->attributes as $attr => $value)
                if(mb_detect_encoding($value, ['UTF-8'])) // is value UTF-8?
                    $this->$attr = mb_convert_encoding($value, 'CP51932', 'UTF-8');// convert back to EUC-WIN-JP

        return parent::beforeSave($insert);
    }
}
