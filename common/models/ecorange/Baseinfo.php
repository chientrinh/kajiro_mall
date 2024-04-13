<?php

namespace common\models\ecorange;

use Yii;

/**
 * This is the model class for table "{{%dtb_baseinfo}}".
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/common/models/ecorange/Baseinfo.php $
 * @version $Id: Baseinfo.php 1023 2015-05-20 10:16:53Z mori $
 *
 * @property string $company_name
 * @property string $company_kana
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 * @property string $fax01
 * @property string $fax02
 * @property string $fax03
 * @property string $business_hour
 * @property string $law_company
 * @property string $law_manager
 * @property string $law_zip01
 * @property string $law_zip02
 * @property integer $law_pref
 * @property string $law_addr01
 * @property string $law_addr02
 * @property string $law_tel01
 * @property string $law_tel02
 * @property string $law_tel03
 * @property string $law_fax01
 * @property string $law_fax02
 * @property string $law_fax03
 * @property string $law_email
 * @property string $law_url
 * @property string $law_term01
 * @property string $law_term02
 * @property string $law_term03
 * @property string $law_term04
 * @property string $law_term05
 * @property string $law_term06
 * @property string $law_term07
 * @property string $law_term08
 * @property string $law_term09
 * @property string $law_term10
 * @property string $tax
 * @property integer $tax_rule
 * @property string $email01
 * @property string $email02
 * @property string $email03
 * @property string $email04
 * @property string $email05
 * @property string $free_rule
 * @property string $shop_name
 * @property string $shop_kana
 * @property string $pdf_logo
 * @property string $point_rate
 * @property string $welcome_point
 * @property string $update_date
 * @property string $top_tpl
 * @property string $product_tpl
 * @property string $detail_tpl
 * @property string $mypage_tpl
 * @property string $good_traded
 * @property string $message
 * @property string $regular_holiday_ids
 * @property integer $shop_id
 */
class Baseinfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%baseinfo}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('ecOrange');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_name', 'company_kana', 'zip01', 'zip02', 'addr01', 'addr02', 'tel01', 'tel02', 'tel03', 'fax01', 'fax02', 'fax03', 'business_hour', 'law_company', 'law_manager', 'law_zip01', 'law_zip02', 'law_addr01', 'law_addr02', 'law_tel01', 'law_tel02', 'law_tel03', 'law_fax01', 'law_fax02', 'law_fax03', 'law_email', 'law_url', 'law_term01', 'law_term02', 'law_term03', 'law_term04', 'law_term05', 'law_term06', 'law_term07', 'law_term08', 'law_term09', 'law_term10', 'email01', 'email02', 'email03', 'email04', 'email05', 'shop_name', 'shop_kana', 'pdf_logo', 'top_tpl', 'product_tpl', 'detail_tpl', 'mypage_tpl', 'good_traded', 'message', 'regular_holiday_ids'], 'string'],
            [['pref', 'law_pref', 'tax_rule', 'shop_id'], 'integer'],
            [['tax', 'free_rule', 'point_rate', 'welcome_point'], 'number'],
            [['update_date'], 'safe'],
            [['shop_id'], 'required']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_name' => 'Company Name',
            'company_kana' => 'Company Kana',
            'zip01' => 'Zip01',
            'zip02' => 'Zip02',
            'pref' => 'Pref',
            'addr01' => 'Addr01',
            'addr02' => 'Addr02',
            'tel01' => 'Tel01',
            'tel02' => 'Tel02',
            'tel03' => 'Tel03',
            'fax01' => 'Fax01',
            'fax02' => 'Fax02',
            'fax03' => 'Fax03',
            'business_hour' => 'Business Hour',
            'law_company' => 'Law Company',
            'law_manager' => 'Law Manager',
            'law_zip01' => 'Law Zip01',
            'law_zip02' => 'Law Zip02',
            'law_pref' => 'Law Pref',
            'law_addr01' => 'Law Addr01',
            'law_addr02' => 'Law Addr02',
            'law_tel01' => 'Law Tel01',
            'law_tel02' => 'Law Tel02',
            'law_tel03' => 'Law Tel03',
            'law_fax01' => 'Law Fax01',
            'law_fax02' => 'Law Fax02',
            'law_fax03' => 'Law Fax03',
            'law_email' => 'Law Email',
            'law_url' => 'Law Url',
            'law_term01' => 'Law Term01',
            'law_term02' => 'Law Term02',
            'law_term03' => 'Law Term03',
            'law_term04' => 'Law Term04',
            'law_term05' => 'Law Term05',
            'law_term06' => 'Law Term06',
            'law_term07' => 'Law Term07',
            'law_term08' => 'Law Term08',
            'law_term09' => 'Law Term09',
            'law_term10' => 'Law Term10',
            'tax' => 'Tax',
            'tax_rule' => 'Tax Rule',
            'email01' => 'Email01',
            'email02' => 'Email02',
            'email03' => 'Email03',
            'email04' => 'Email04',
            'email05' => 'Email05',
            'free_rule' => 'Free Rule',
            'shop_name' => 'Shop Name',
            'shop_kana' => 'Shop Kana',
            'pdf_logo' => 'Pdf Logo',
            'point_rate' => 'Point Rate',
            'welcome_point' => 'Welcome Point',
            'update_date' => 'Update Date',
            'top_tpl' => 'Top Tpl',
            'product_tpl' => 'Product Tpl',
            'detail_tpl' => 'Detail Tpl',
            'mypage_tpl' => 'Mypage Tpl',
            'good_traded' => 'Good Traded',
            'message' => 'Message',
            'regular_holiday_ids' => 'Regular Holiday Ids',
            'shop_id' => 'Shop ID',
        ];
    }

    public function getName()
    {
        return $this->shop_name;
    }
}
