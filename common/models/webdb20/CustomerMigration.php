<?php

namespace common\models\webdb20;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb20/CustomerMigration.php $
 * $Id: CustomerMigration.php 1406 2015-08-29 12:12:49Z mori $
 */

use Yii;

class CustomerMigration extends \yii\base\Model
{
    public $directive;
    public $migrate_id;

    public function rules()
    {
        return [
            [['directive','migrate_id'], 'required'],
            ['directive', 'compare', 'compareValue'=>'webdb20','operator'=>'==='],
            ['migrate_id', 'exist','targetClass' => \common\models\Membercode::className(), 'targetAttribute'=>'migrate_id', 'skipOnError'=>true],
            ['migrate_id', 'validateCustomer','message'=>'migrate_id is not valid','skipOnError'=>true,'skipOnEmpty'=>true],
        ];
    }

    public function validateCustomer($attribute, $params)
    {
        return Yii::$app->webdb20
            ->createCommand('select customerid from tblcustomer where customerid = :cid',[':cid'=>$this->$attribute])
            ->queryColumn();
    }

    private function getMembercode()
    {
        $code = \common\models\Membercode::find()->where([
            'directive'  => $this->directive,
            'migrate_id' => $this->migrate_id,
        ])->one();

        if(! $code)
            Yii::warning([
                'failed Membercode::find()',
                'directive'  => $this->directive,
                'migrate_id' => $this->migrate_id
            ],
            self::className().'::'.__FUNCTION__);

        return $code;
    }

    /* @return bool */
    public function migrate()
    {
        if(! $this->validate())
        {
            Yii::warning($this->errors, self::className().'::'.__FUNCTION__);
            return false;
        }
            
        if(null === ($membercode = $this->getMembercode()))
           return false;

        if(false === ($customer = \common\models\webdb20\SearchCustomer::findOne($this->migrate_id)))
            return false;

        if(! $customer->isToranoko())
            $customer->toranokoParams;

        // get Toranoko attributes
        $row = Yii::$app->webdb20
             ->createCommand('SELECT dateofadmission AS create_date, dateofcontinuation AS update_date WHERE customerid = :cid', [':cid'=>$this->migrate_id])
             ->queryRow();

        if('euc-jp' === Yii::$app->webdb20->charset)
            foreach($row as $key => $column)
            {
                // convert EUC-WIN-JP to utf8
                $utf8 = mb_convert_encoding($column, 'UTF-8', 'CP51932');
                $row[$key] = $utf8;
            }
    }
}
