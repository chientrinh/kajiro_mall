<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/SearchCustomer.php $
 * $Id: SearchCustomer.php 1446 2015-09-02 10:18:15Z mori $
 */

namespace common\models\webdb20;

use Yii;

/**
 * 
 */
class SearchCustomer extends \yii\base\Model
{
    /**
     * @return a record of tblcutsomer in array
     */
    public static function findOne($id)
    {
        $columns = implode(',',[
            'c.customerid',
            'c.name',
            'c.kana',
            'c.sexid',
            'c.entrydate',
            'c.updatedate',
            'c.birth',
            'c.email',
            'c.sexid',
            'c.wireless',
            'a.postnum',
            'a.address1',
            'a.address2',
            'a.address3',
            'a.tel',
            'a.fax',
            'a.mobile',
            'a.email as email2',
        ]);

        $qstring = "SELECT "
                 . $columns
                 . " FROM tblcustomer c LEFT JOIN tbladdress a ON a.customerid = c.customerid WHERE "
                 . " c.name <> '' AND "
                 . " c.customerid = :cid";

        $db  = Yii::$app->webdb20;
        $cmd = $db->createCommand($qstring);
        $cmd->bindValues([':cid' => $id]);

        $row = $cmd->queryOne();
        if(! $row)
            return false;

        if('euc-jp' === $db->charset)
            foreach($row as $key => $column)
            {
                // convert EUC-WIN-JP to utf8
                $utf8 = mb_convert_encoding($column, 'UTF-8', 'CP51932');
                $row[$key] = $utf8;
            }

        $model = new CustomerForm();
        $model->load(['CustomerForm'=>$row]);

        return $model;
    }

    public static function findFromEmailAndPassword($email,$password)
    {
        $columns = implode(',',[
            'c.customerid',
            'c.name',
            'c.kana',
            'c.sexid',
            'c.entrydate',
            'c.updatedate',
            'c.birth',
            'c.email',
            'c.sexid',
            'c.wireless',
            'a.postnum',
            'a.address1',
            'a.address2',
            'a.address3',
            'a.tel',
            'a.fax',
            'a.mobile',
            'a.email as email2',
        ]);

        $qstring = "SELECT "
                 . $columns
                 . " FROM tblcustomer c LEFT JOIN tbladdress a ON a.customerid = c.customerid WHERE "
                 . " c.email = :email AND c.passwd = :passwd";

        $db  = Yii::$app->webdb20;
        $cmd = $db->createCommand($qstring);
        $cmd->bindValues([':email' => $email, ':passwd' => Security::generatePasswordHash($password)]);

        $row = $cmd->queryOne();
        if(! $row)
            return false;

        if('euc-jp' === $db->charset)
            foreach($row as $key => $column)
            {
                // convert EUC-WIN-JP to utf8
                $utf8 = mb_convert_encoding($column, 'UTF-8', 'CP51932');
                $row[$key] = $utf8;
            }

        $model = new CustomerForm();
        $model->load(['CustomerForm'=>$row]);

        return $model;
    }

}
