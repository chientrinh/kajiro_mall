<?php
namespace common\models;

use Yii;
use DateTime;

/**
 * This is the model class for table "dtb_customer".
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/Customer.php $
 * $Id: Customer.php 4238 2020-03-12 04:40:39Z kawai $
 *
 * @property integer $customer_id
 * @property string $name01
 * @property string $name02
 * @property string $kana01
 * @property string $kana02
 * @property integer $sex_id
 * @property string $birth
 * @property string $email
 * @property string $zip01
 * @property string $zip02
 * @property integer $pref_id
 * @property string $addr01
 * @property string $addr02
 * @property string $tel01
 * @property string $tel02
 * @property string $tel03
 *
 * @property MtbSex $sex
 * @property MtbPref $pref
 * @property DtbCustomerAddrbook[] $dtbCustomerAddrbooks
 * @property DtbCustomerFavorite[] $dtbCustomerFavorites
 * @property DtbPurchase[] $dtbPurchases
 */
class Customer extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    const EAN13_PREFIX      = 28;
    const DATETIME_MAX      = '3000-12-31 00:00:00';
    const SCENARIO_ZIP2ADDR = 'zip2addr';
    const SCENARIO_CHILDMEMBER = 'child';
    const SCENARIO_EMERGENCY   = 'emergency';

    private $_checkdigit;
    public $is_agency;
    public $agencies;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dtb_customer';
    }

    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'update'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['create_date','update_date'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'update_date',
                ],
                'value' => function ($event) {
                    return new \yii\db\Expression('NOW()');
                },
            ],
            'entry'=>[
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['entry_date'],
                ],
                'value' => function ($event) {
                    return $this->entry_date ? $this->entry_date : new \yii\db\Expression('NOW()');
                },
            ],
            'membercode'=>[
                'class' => FixMembercode::className(),
                'owner' => $this,
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
        $basics = ['name01','name02','kana01','kana02','zip01','zip02','pref_id','addr01','addr02','tel01','tel02','tel03'];

        return [
            [$basics, 'filter', 'filter' => 'trim'],
            ['name01', 'default', 'value' => function($model) { return $model->kana01; } ],
            ['name02', 'default', 'value' => function($model) { return $model->kana02; } ],
            [$basics, 'required', 'except'=> [self::SCENARIO_CHILDMEMBER] ],
            [['name01','name02','kana01','kana02'], 'required', 'on'=> [self::SCENARIO_CHILDMEMBER] ],
            [['email','zip01','zip02','addr01','addr02','tel01','tel02','tel03','password_hash'], 'default','value'=>''],
            [['kana01','kana02'],'filter','filter'=>function($value) { return \common\components\Romaji2Kana::translate($value,'hiragana'); }],
            ['email', 'required', 'when' => function($model){
                return ('app-frontend' == Yii::$app->id) &&
                       ($user = Yii::$app->get('user', false)) &&
                       ($user->isGuest || ($user->id == $model->customer_id));
            } ],
            ['email', 'email', 'when' => function($model){
                return ('app-frontend' == Yii::$app->id) &&
                       ($user = Yii::$app->get('user', false)) &&
                       ($user->isGuest || ($user->id == $model->customer_id));
            }],
            ['email', 'unique',
             'targetClass' => Customer::className(),
             'filter'      => ['>=', 'expire_date', new \yii\db\Expression('NOW()')],
             'message'     => "入力されたメールアドレスはすでに登録されています。",
            ],
            [['sex_id', 'pref_id'], 'integer'],
            ['sex_id',   'default', 'value'=> 0       ],
            ['sex_id',   'in',      'range'=> [0,1,2,9]],
            ['pref_id', 'exist', 'targetClass' => Pref::className() ],
            ['grade_id', 'default', 'value'=> CustomerGrade::PKEY_AA ],
            ['grade_id', 'exist',   'targetClass'=> CustomerGrade::className() ],
            [['birth'],  'validateBirth', 'skipOnEmpty'=> true],
            [['name01', 'name02', 'kana01', 'kana02', 'homoeopath_name'], 'string', 'max' => 128],
            [['email', 'addr01', 'addr02'], 'string', 'max' => 255],
            [['zip01'],          'string', 'max' => 3],
            [['zip02'],          'string', 'max' => 4],
            [['tel01'],          'string', 'max' => 5],
            [['tel02', 'tel03'], 'string', 'min' => 1, 'max' => 6],
            [['zip01','zip02','tel01','tel02','tel03'],  'integer'],
            ['pref_id',  'integer', 'min'=>1, 'max'=>48, 'message'=>"選択してください"],
            ['subscribe', 'integer','min'=>0,'max'=>3],
            [['is_agency', 'agencies'] ,'safe'],
//            [['tel01', 'tel02', 'tel03'], 'validateTel', 'skipOnEmpty' => true]
        ];
    }

    public function scenarios()
    {
        return array_merge(parent::scenarios(),[
            self::SCENARIO_ZIP2ADDR    => ['zip01','zip02'],
            self::SCENARIO_CHILDMEMBER => self::attributes(),
            self::SCENARIO_EMERGENCY   => ['kana01','kana02','tel01','tel02','tel03'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'customer_id'=> '顧客ID',
            'code'       => "会員証NO",
            'point'      => "ポイント保有",
            'name'       => "お名前",
            'kana'       => "かな",
            'sex'        => "性別",
            'age'        => "年齢",
            'zip'        => "郵便番号",
            'pref'       => "都道府県",
            'addr'       => "住所",
            'fulladdress'=> "住所",
            'grade'      => "大区分",
            'membership' => "属性",
            'tel'        => "電話",
            'name01'     => "姓",
            'name02'     => "名",
            'kana01'     => "せい",
            'kana02'     => "めい",
            'sex'        => "性別",
            'pref_id'    => "都道府県",
            'email'      => "メールアドレス",
            'password'   => "パスワード",
            'birth'      => "生年月日",
            'addr01'     => "市区町村名",
            'addr02'     => "番地・ビル名",
            'tel01'      => "市外局番",
            'tel02'      => "市内局番",
            'tel03'      => "枝番",
            'subscribe'  => "DM・メルマガ送付について",
            'create_date'=> "登録日",
            'expire_date'=> "有効期限",
            'is_agency'  => "代理店",
            'agencies'   => "代理店所属",
            'homoeopath_name' => 'ホメオパス活動名',
            'campaign_code' => 'キャンペーンコード',
        ];
    }

    public function attributeHints()
    {
        return [
            'addr02'  => '自宅住所をご登録ください。自宅住所をご事情により登録できない方は、勤務先等、他の住所でも結構です。商品お届け先は、ご注文時に指定できます。',
            'birth'   => 'お酒（マザーチンクチャーなど）の購入において必須となります。',
            'homoeopath_name' => 'ホメオパスとして活動する顧客の場合、その活動名を記入してください。',
        ];
    }

    /************ GETTER METHODS *************/
    /**
     * @return string
     */
    public function getAddr()
    {
        if($this->parent)
            return $this->parent->addr;

        return sprintf('%s %s %s',
                       ($this->pref ? $this->pref->name : ''),
                       $this->addr01,
                       $this->addr02);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddrbooks()
    {
        return $this->hasMany(CustomerAddrbook::className(), ['customer_id' => 'customer_id']);
    }

    /* @return integer */
    public function getAge($compare_date=null)
    {
        if(! $this->birth || preg_match('/0000/', $this->birth))
            return null;
        #$birth = strtotime(date('Y-m-d',strtotime($this->birth)));
        #$now   = ($compare_date) ? strtotime($compare_date) : strtotime(date('Y-m-d'));
        $birth = date('Ymd',strtotime($this->birth));
        $now   = ($compare_date) ? date('Ymd',strtotime($compare_date)) : date('Ymd');

        // (今日の日付-誕生日)/10000の小数点以下切捨て に改修する。YYYYmmdd形式の文字列を引いて１００００で除算して切り捨て。これが一番シンプル
        $age = intval(($now - $birth) / 10000);
        // 子供判定は年度を基準にする
        if ($compare_date && $age === 12 && ($this->getFiscalYear(new \DateTime($now)) - $this->getFiscalYear(new \DateTime($this->birth)) == 13)) {
            $age = 13;
        }
        return $age;
    }

    /**
    * 年度開始日との差分を取得
    * ex.) 2016年04月01日 -(引く) 2016年04月01日(年度開始日) = 差分0日 (invert = 0
    * ex.) 2016年01月01日 -(引く) 2016年04月01日(年度開始日) = マイナス3ヶ月 (invert = 1
    */
    private function getFiscalYear(\DateTime $theDate, $startDay = '04-02') {
        $diff = (new DateTime($theDate->format('Y') . '-' . $startDay))->diff($theDate);
        if ( $diff->invert ) { // 引き算の結果がマイナスだった場合には早生まれ状態
            return intval($theDate->format('Y')) - 1;
        } else { // そのまま
            return intval($theDate->format('Y'));
        }
    }

    public function getAgencyRating($company_id)
    {
        return $this->hasOne(AgencyRating::className(),['customer_id' => 'customer_id'])
                    ->andWhere(['company_id' => $company_id])
                    ->andWhere('start_date <= NOW()')
                    ->andWhere('NOW() <= end_date')
                    ->orderBy('end_date DESC')
                    ->one();
    }

    public function getAgencyRatings()
    {
        return $this->hasMany(AgencyRating::className(),['customer_id' => 'customer_id'])
                    ->orderBy('end_date DESC');
    }

    public function getAgencies()
    {
        $hj = false;
        $he = false;
        $hp = false;

        $agencies = 99;


        if($this->isMemberOf([\common\models\Membership::PKEY_AGENCY_HJ_A,
                                       \common\models\Membership::PKEY_AGENCY_HJ_B]))
            $hj = true;

        if($this->isMemberOf(\common\models\Membership::PKEY_AGENCY_HE))

            $he = true;

        if($this->isMemberOf(\common\models\Membership::PKEY_AGENCY_HP))

            $hp = true;

        if($hj) {
            if($he) {
                if($hp) {
                    return 6;
                }
                return 3;
            }

            if($hp) {
                return 4;
            }
            return  0;
        }
        if($he) {
            if($hp) {
                return 5;
            }
            return 1;
        }
        if($hp) {
            return 2;
        }


        return $agencies;
    }



    /**
     * @return string
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function getBarcode()
    {
        if($this->membercode)
            return $this->membercode->barcode;

        return null;
    }

    public function getCheckdigit()
    {
        if(isset($this->_checkdigit))
            return $this->_checkdigit;

        $src = self::EAN13_PREFIX . $this->code;
        $cd = new \common\components\ean13\CheckDigit();
        if(false === ($this->_checkdigit = $cd->generate($src)))
            Yii::error(sprintf('CheckDigit was not generate from code(%s)', $this->code), $this->className()."::".__FUNCTION__);

        return $this->_checkdigit;
    }

    public function getChild()
    {
        return $this->hasMany(self::className(), ['customer_id' => 'child_id'])
            ->from(self::tableName(). ' child')
            ->viaTable(CustomerFamily::tableName(), ['parent_id' => 'customer_id']);
    }
    public function getChildren($active=true)
    {
        $query = $this->hasMany(self::className(), ['customer_id' => 'child_id'])
            ->from(self::tableName(). ' children')
            ->viaTable(CustomerFamily::tableName(), ['parent_id' => 'customer_id'])
            ->orderBy('children.birth ASC');

        if($active)
            $query->where('children.expire_date > NOW()');

        return $query;
    }

    /* @return string | null */
    public function getCode()
    {
        if($this->parent)
            return $this->parent->code;

        if($this->membercode)
            return $this->membercode->code;

        return null;
    }

    public function getCompanies()
    {
        return $this->hasMany(Membership::className(), ['membership_id' => 'membership_id'])
               ->viaTable('dtb_customer_membership',['customer_id'=>'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCommissions()
    {
        return $this->hasMany(Commission::className(), ['customer_id' => 'customer_id']);
    }

    /* @return bool */
    public function getExpired()
    {
        return (strtotime($this->expire_date) <= time());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavorites()
    {
        return $this->hasMany(CustomerFavorite::className(), ['customer_id' => 'customer_id']);
    }

    public function getFullAddress()
    {
        return sprintf('〒%s %s', $this->zip, $this->addr);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrade()
    {
        return $this->hasOne(CustomerGrade::className(), ['grade_id' => 'grade_id']);
    }

    /**
     * @brief required to implement UserInterface
     * @inheritdoc
     */
    public function getId()
    {
        return $this->customer_id;
    }

    /**
     * @return ActiveQuery
     */
    public function getInvoices($activeOnly=true)
    {
        $query = $this->hasMany(Invoice::className(), ['customer_id' => 'customer_id'])->inverseOf('customer');

        if($activeOnly)
            $query->andWhere(['status' => InvoiceStatus::PKEY_ACTIVE]);

        return $query;
    }

    /**
     * @return string
     */
    public function getKana()
    {
        if(! $this->kana01 && ! $this->kana02)
            return $this->code ? $this->code : '(未指定)';

        return sprintf('%s %s', $this->kana01, $this->kana02);
    }

    /* @return \yii\db\ActiveQuery */
    public function getMails()
    {
        return $this->hasMany(MailLog::className(), ['pkey' => 'customer_id'])
                    ->andWhere(['tbl' => $this->tableName()]);
    }

    /* @return \yii\db\ActiveQuery */
    public function getMembercode()
    {
        return $this->hasOne(Membercode::className(), ['customer_id' => 'customer_id'])->andWhere(['status'=>0])->inverseOf('customer');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMemberships($expired=false)
    {
        $query = $this->hasMany(CustomerMembership::className(), ['customer_id' => 'customer_id']);

        if(false == $expired)
            $query->active();

        return $query;
    }

    /* @return \yii\db\ActiveQuery */
    public function getHjAgencyRank()
    {
        return $this->hasMany(CustomerAgencyRank::className(), ['customer_id' => 'customer_id'])->orderBy('expire_date DESC');
    }

    /* @return \yii\db\ActiveQuery */
    public function getActiveAgencyRank()
    {
        return $this->hasOne(CustomerAgencyRank::className(), ['customer_id' => 'customer_id'])->active()->orderBy('expire_date DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMemberTypes()
    {
        return $this->hasMany(Membership::className(), ['membership_id' => 'membership_id'])
                    ->viaTable(CustomerMembership::tableName(), ['customer_id' => 'customer_id']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        if(! $this->name01 && ! $this->name02)
            return $this->kana;

        return sprintf('%s %s', $this->name01, $this->name02);
    }

    public function getHomoeopathName()
    {
        if(! $this->homoeopath_name)
            return sprintf('%s %s', $this->name01, $this->name02);

        return $this->homoeopath_name;
    }

    public function getOffice()
    {
        return $this->hasOne(AgencyOffice::className(),['customer_id' => 'customer_id']);
    }

    public function getParent()
    {
        return $this->hasOne(self::className(), ['customer_id' => 'parent_id'])
            ->from(self::tableName() . ' parent')
            ->viaTable(CustomerFamily::tableName(), ['child_id' => 'customer_id'])
            ->inverseOf('children');
    }

    public function getFamily()
    {
        return $this->hasMany(CustomerFamily::className(), ['parent_id' => 'customer_id'])
                    ->from(CustomerFamily::tableName() . ' family');
    }

    public function getPasswordResetToken()
    {
        return $this->hasOne(\common\models\PasswordResetToken::className(), ['email' => 'email']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPointings($issuedBySelf=false)
    {
        if($issuedBySelf)
            return $this->hasMany(Pointing::className(), ['seller_id' => 'customer_id'])->inverseOf('customer');

        return $this->hasMany(Pointing::className(), ['customer_id' => 'customer_id'])->inverseOf('customer');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPref()
    {
        if(! $this->pref_id && $this->parent)
            return $this->parent->pref;

        if(! $this->pref_id)
            return new NullPref();

        return $this->hasOne(Pref::className(), ['pref_id' => 'pref_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchases()
    {
        return $this->hasMany(Purchase::className(), ['customer_id' => 'customer_id'])->inverseOf('customer');
    }

    public function getRating()
    {
        return $this->hasMany(AgencyRating::className(),['customer_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecipes($issuedBySelf=false)
    {
        if($issuedBySelf)
            return $this->hasMany(Recipe::className(), ['homoeopath_id' => 'customer_id'])->inverseOf('homoeopath');

        return $this->hasMany(Recipe::className(), ['client_id' => 'customer_id'])->inverseOf('client');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSex()
    {
        return $this->hasOne(Sex::className(), ['sex_id' => 'sex_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSodans()
    {
        return $this->hasMany(sodan\Interview::className(), ['client_id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscription()
    {
        return $this->hasOne(Subscribe::className(), ['subscribe_id' => 'subscribe']);
    }

    /**
     * @return string
     */
    public function getTel()
    {
        $tel = sprintf('%s-%s-%s', $this->tel01, $this->tel02, $this->tel03);

        if(2 == strlen($tel))
        {
            if($this->parent)
                return $this->parent->tel;

            else $tel = '';
        }

        return $tel;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->email;
    }

    public function getYsdAccount()
    {
        return $this->hasOne(ysd\Account::className(),['customer_id' => 'customer_id']);
    }

    /**
     * @return string
     */
    public function getZip()
    {
        if($this->parent)
            return $this->parent->zip;

        return sprintf('%s-%s', $this->zip01, $this->zip02);
    }

    /************ SPECIFIC CLASS METHODS *************/
    /**
     * @return bool
     */
    public function activate()
    {
        if(! $this->isExpired())
            return true;

        $this->expire_date = self::DATETIME_MAX;
        return $this->save(false);
    }

    /**
     * @return bool
     */
    public function belongsTo($company_id)
    {
        if($this->isExpired())
            return false;

        if($company_id == Company::PKEY_TY) // everyone belongs to TY by default
            return true;

        if(0 == count($this->memberships)) // has no membership
            return false;

        foreach($this->memberships as $membership)
        {
            if($membership->isExpired())
                continue;

            if($company_id == $membership->company->company_id)
                return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function expire()
    {
        if($this->isExpired())
            return true;

        $this->email         = '';
        $this->password_hash = '';
        $this->expire_date   = date('Y-m-d H:i:s');
        $this->subscribe = 0;
        return $this->save(false);
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new CustomerQuery(get_called_class());
    }

    /**
     * Finds user by barcode
     *
     * @param string $email
     * @return static|null
     */
    public static function findByBarcode($ean13, $strict = false)
    {
        $attr = static::parseBarcode($ean13);
        if(empty($attr))
            return null;

        $model = static::find()->innerJoinWith('membercode')->andWhere(['mtb_membercode.code' => $attr['code']])->one();
        if($model && ($model->membercode->checkdigit != $attr['checkdigit']))
        {
            Yii::warning(sprintf("supplied ean13 with wrong checkdigit(%s != %s)", $ean13, $model->barcode),self::className().'::'.__FUNCTION__);

            if($strict)
                return null;
        }

        return $model;
    }

    protected static function parseBarcode($ean13)
    {
        $pattern = sprintf('/^(%02d|)([0-9]{10})([0-9])?$/', self::EAN13_PREFIX);

        if(! preg_match($pattern, $ean13, $match))
            return [];

        return [
            'code'       => $match[2],
            'checkdigit' => isset($match[3]) ? $match[3] : 0,
        ];
    }

    /**
     * Finds user by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        $now = new \yii\db\Expression('NOW()');
        return static::find()->andWhere(['>', $now, 'expire_date'])->andWhere(['email' => $email])->one();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['customer_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function findByPasswordResetToken($token)
    {
        $model = \common\models\PasswordResetToken::find()
               ->where(['token'=>$token])
               ->active()
               ->one();

        if($model)
            $customer = self::findByEmail($model->email);

        return isset($customer) ? $customer : null;
    }

    public function generatePasswordResetToken()
    {
        $transaction = Yii::$app->db->beginTransaction();

        if($this->passwordResetToken)
        {
            $model = $this->passwordResetToken;
        }
        else
        {
            $model = new \common\models\PasswordResetToken();
            $model->email = $this->email;
        }

        if($model->save())
        {
            $transaction->commit();
        }
        else
        {
            Yii::error("failed in PasswordResetToken::save()");

            $transaction->rollBack();
            return false;
        }

        return $model->token;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        if($this->isNewRecord)
            return false;

        return (strtotime($this->expire_date) <= time());
    }

    public function isAgency()
    {
        return $this->isMemberOf([Membership::PKEY_AGENCY_HE   ,
                                  Membership::PKEY_AGENCY_HJ_A ,
                                  Membership::PKEY_AGENCY_HJ_B ,
                                  Membership::PKEY_AGENCY_HP   ]);
    }

    public function getIsAgency()
    {
        return $this->isAgency();
    }

    /**
     * 社員か判定する
     */
    public function isStaff()
    {
        return $this->isMemberOf([
            Membership::PKEY_OF_TY,
            Membership::PKEY_OF_HE,
            Membership::PKEY_OF_HP,
            Membership::PKEY_OF_HJ,
            Membership::PKEY_RE_TY,
            Membership::PKEY_RE_HE,
            Membership::PKEY_RE_HP,
            Membership::PKEY_RE_HJ,
            Membership::PKEY_CE_TY,
            Membership::PKEY_CE_HE,
            Membership::PKEY_CE_HP,
            Membership::PKEY_CE_HJ,
            Membership::PKEY_PT_TY,
            Membership::PKEY_PT_HE,
            Membership::PKEY_PT_HP,
            Membership::PKEY_PT_HJ,
                                     ]);
    }

    public function getIsStaff()
    {
        return $this->isStaff();
    }    


    /**
     * 提携施設に登録できる資格を有するか確認する 
     **/
    public function isFacility()
    {
        return $this->isMemberOf([Membership::PKEY_HOMOEOPATHY_CENTER ,
                                  Membership::PKEY_AGENCY_HE ,
                                  Membership::PKEY_HOMOEOPATH,
                                  Membership::PKEY_JPHMA_IC   ]);
    }

    public function getIsFacility()
    {
        return $this->isFacility();
    }


    public function isAgencyOf($company)
    {
        $id = null;
        if($company instanceof Company)
            $id = $company->company_id;
        elseif(is_numeric($company))
            $id = (int) $company;

        if($id === Company::PKEY_HE)
            return $this->isMemberOf(Membership::PKEY_AGENCY_HE);

        if($id === Company::PKEY_HJ)
            return ($this->isMemberOf([Membership::PKEY_AGENCY_HJ_A,
                                       Membership::PKEY_AGENCY_HJ_B]));

        if($id === Company::PKEY_HP)
            return $this->isMemberOf(Membership::PKEY_AGENCY_HP);

        return false;
    }

    /**
     * 酒類を購入できる顧客かどうか判定する @see ticket:428
     * @return bool
     */
    public function isAdult()
    {
        if(Sex::PKEY_LEGAL == $this->sex_id)
            return true;

        if(20 <= $this->age)
            return true;

        if($this->isAgency())
            return true;

        return false;
    }

    /**
     * @return bool
     */
    public function isHomoeopath()
    {
        return $this->isMemberOf(Membership::PKEY_HOMOEOPATH);
    }

    public function isJphmatechnical()
    {
        return $this->isMemberOf(Membership::PKEY_JPHMA_TECHNICAL);
    }

    /**
     * @return bool
     */
    public function isMemberOf($membership_id)
    {
        return $this->getMemberships(false)
                    ->andWhere(['membership_id' => $membership_id])
                    ->andWhere(['not',['>', 'start_date',  new \yii\db\Expression('NOW()')]])
                    ->andWhere(['not',['<', 'expire_date', new \yii\db\Expression('NOW()')]])
                    ->exists();
    }

    /**
     * @return bool
     */
    public function wasMemberOf($membership_id)
    {
        return $this->getMemberships(true)
                    ->andWhere(['membership_id' => $membership_id])
                    ->andWhere(['<','expire_date',date('Y-m-d H:i:s')])
                    ->exists();
    }

    /**
     * @return bool
     */
    public function isStudent()
    {
        return $this->isMemberOf([Membership::PKEY_STUDENT_INTEGRATE     ,
                                  Membership::PKEY_STUDENT_TECH_COMMUTE  ,
                                  Membership::PKEY_STUDENT_TECH_ELECTRIC ,
                                  Membership::PKEY_STUDENT_FH            ,
                                  Membership::PKEY_STUDENT_IC            ,
                                  Membership::PKEY_STUDENT_PH_ELECTRIC]);
    }

    /**
     * @return bool
     */
    public function isToranoko()
    {
        return $this->isMemberOf([Membership::PKEY_TORANOKO_GENERIC,
                                  Membership::PKEY_TORANOKO_GENERIC_UK,
                                  Membership::PKEY_TORANOKO_NETWORK,
                                  Membership::PKEY_TORANOKO_NETWORK_UK,
                                  Membership::PKEY_TORANOKO_FAMILY]);
    }

    /**
     * @return bool
     */
    public function wasToranoko()
    {
        return $this->wasMemberOf([Membership::PKEY_TORANOKO_GENERIC,
                                   Membership::PKEY_TORANOKO_GENERIC_UK,
                                   Membership::PKEY_TORANOKO_NETWORK,
                                   Membership::PKEY_TORANOKO_NETWORK_UK,
                                   Membership::PKEY_TORANOKO_FAMILY]);
    }

    public function hasLiquorLicense()
    {
        return $this->isMemberOf(Membership::PKEY_LIQUOR_LICENSE);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function statusOf($membership)
    {
        return $membership->hasMany(CustomerMembership::className(), [
            'membership_id' => 'membership_id',
            'customer_id' => $this->customer_id])
            ->addOrderBy(['membership_id' => SORT_ASC, 'start_date' => SORT_DESC]);
    }

    public function removePasswordResetToken()
    {
        $row = \common\models\PasswordResetToken::findOne(['email'=> $this->email]);
        if($row)
            $row->delete();
    }

    public function setAsToranokoGenericMember()
    {
        return CustomerMembership::setAsToranokoGenericMember($this);
    }

    public function setAsToranokoNetworkMember()
    {
        return CustomerMembership::setAsToranokoNetworkMember($this);
    }

    public function setAsToranokoFamilyMember($parent)
    {
        return CustomerMembership::setAsToranokoFamilyMember($this, $parent);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * 親子を入れ替える、または独立していた顧客を養子にする
     * @return void
     */
    public function adapt($child)
    {
        if($this->parent && $this->parent->equals($child)) // 親子を入れ替え
            return self::swap($this->customer_id, $child->customer_id);

        $this->unlink('children', $child, true);// もし繋っていれば手放す
        $this->link('children', $child);// 親になる

        if($child->children)
            foreach($child->children as $grandson)
            {
                $child->unlink('children',$grandson, true);
                $this->link('children', $grandson);
            }
    }

    /**
     * @brief swap parent and child
     */
    public static function swap($parent_id/* to be child*/, $child_id/* to be parent*/)
    {
        if(! $child = self::findOne($child_id))
            return false;

        if(! $child->parent || $child->isExpired() ||  ($parent_id != $child->parent->customer_id))
            return false;

        $parent = $child->parent;

        foreach(['email','password_hash','auth_key','zip01','zip02','pref_id','addr01','addr02','tel01','tel02','tel03','subscribe'] as $attr)
        {
            $child->$attr  = $parent->$attr;
            $parent->$attr = '';
        }
        $child->point     = $parent->point;
        $parent->point    = 0;
        $parent->auth_key = Yii::$app->getSecurity()->generateRandomString();

        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            if(! $parent->save(false))
                throw new \yii\db\Exception('failed to save parent');

            if(! $child->save(false))
                throw new \yii\db\Exception('failed to save child');

            $db = Yii::$app->db;

            if(0 == $db->createCommand('update dtb_customer_family set parent_id = :new where parent_id = :old')
                       ->bindValues([':new'=>$child_id,':old'=>$parent_id])
                       ->execute())
                throw new \yii\db\Exception('failed to swap family position');

            if(0 == $db->createCommand('update dtb_customer_family set child_id = :new where child_id = :old and child_id = parent_id')
                       ->bindValues([':new'=>$parent_id,':old'=>$child_id])
                       ->execute())
                throw new \yii\db\Exception('could not update child_id in family relationship');

            if($parent->isToranoko())
            {
                if(0 == $db->createCommand(sprintf('update dtb_customer_membership set customer_id = :new where customer_id = :old and membership_id in (%s)', implode(',', [
                    Membership::PKEY_TORANOKO_GENERIC,
                    Membership::PKEY_TORANOKO_GENERIC_UK,
                    Membership::PKEY_TORANOKO_NETWORK,
                    Membership::PKEY_TORANOKO_NETWORK_UK,
                ])))->bindValues([
                    ':new' => $child_id,
                    ':old' => $parent_id,
                ])->execute())
                    throw new \yii\db\Exception('failed to swap customer_id in dtb_customer_membership');

                if(0 == $db->createCommand('update dtb_customer_membership set customer_id = :new where customer_id = :old and membership_id = :mship')
                           ->bindValues([
                               ':new'  => $parent_id,
                               ':old'  => $child_id,
                               ':mship'=> Membership::PKEY_TORANOKO_FAMILY,
                           ])->execute())
                               throw new \yii\db\Exception('failed to swap customer_id in dtb_customer_membership');
            }

            $db->createCommand('update dtb_purchase set customer_id = :new where customer_id = :old')
               ->bindValues([
                   ':new'  => $child_id,
                   ':old'  => $parent_id,
               ])->execute();

            $db->createCommand('update dtb_pointing set customer_id = :new where customer_id = :old')
               ->bindValues([
                   ':new'  => $child_id,
                   ':old'  => $parent_id,
               ])->execute();

            // swap membercode
            $code1 = $parent->membercode;
            $code2 = $child->membercode;

            $code1->detachBehaviors();
            $code2->detachBehaviors();

            $code1->delete();
            $code2->delete();

            $scalar1 = $code1->code;
            $scalar2 = $code2->code;
            $code1->code = $scalar2;
            $code2->code = $scalar1;

            $scalar1 = $code1->pw;
            $scalar2 = $code2->pw;
            $code1->pw = $scalar2;
            $code2->pw = $scalar1;

            $code1->save();
            $code2->save();
        }
        catch(\yii\db\Exception $e)
        {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    /**
     * calculate Customer's Grade
     *
     * @param  $customer_id
     * @return integer (current grade_id)
     */
    public static function currentGrade($customer_id)
    {

$time_start = microtime(true);
        // 本人および有効な(無効ではない)家族が持つ、有効な(期限切れではない)属性(membership_id)を抽出する
        $subQuery1 = CustomerFamily::find()->select('parent_id')
                                           ->where(['child_id'  => $customer_id]);
        $subQuery2 = CustomerFamily::find()->select('child_id')
                                           ->where(['parent_id' => $customer_id]);
        $query     = CustomerMembership::find()
               ->active()
               ->andWhere(['OR',
                           ['c.customer_id' => $customer_id],
                           ['c.customer_id' => $subQuery1],
                           ['c.customer_id' => $subQuery2],
               ])
               ->innerJoin('dtb_customer c','c.customer_id = dtb_customer_membership.customer_id')
               ->andWhere('c.expire_date > NOW()')
               ->select('membership_id')
               ->distinct();

        $arr = $query->column();

        $customer_query = Customer::find()
               ->active()
               ->andWhere(['OR',
                           ['customer_id' => $customer_id],
                           ['customer_id' => $subQuery1],
                           ['customer_id' => $subQuery2],
               ])
               ->select('customer_id')
               ->distinct();

        $customer_arr = $customer_query->column();

        // PKEY_NA（プレミアムプラス）
        if(in_array(Membership::PKEY_HOMOEOPATH, $arr) ||
           in_array(Membership::PKEY_JPHMA_TECHNICAL, $arr))
        {
            return CustomerGrade::PKEY_NA;
        }

        // PKEY_TA（プレミアム）
        if(in_array(Membership::PKEY_STUDENT_INTEGRATE     , $arr) ||
           in_array(Membership::PKEY_STUDENT_TECH_COMMUTE  , $arr) ||
           in_array(Membership::PKEY_STUDENT_TECH_ELECTRIC , $arr) ||
           in_array(Membership::PKEY_STUDENT_PH_ELECTRIC   , $arr))
        {
            return CustomerGrade::PKEY_TA;
        }

        // PKEY_SA（スペシャルプラス）
        if(in_array(Membership::PKEY_JPHMA_IC  , $arr) ||
           in_array(Membership::PKEY_JPHMA_FH  , $arr) ||
           in_array(Membership::PKEY_STUDENT_FH, $arr) ||
           in_array(Membership::PKEY_STUDENT_IC, $arr) ||
           in_array(Membership::PKEY_JPHF_SP_PHYTO, $arr) ||
           in_array(Membership::PKEY_STUDENT_FLOWER, $arr) ||
           in_array(Membership::PKEY_JPHF_FLOWER, $arr) ||
           in_array(Membership::PKEY_STUDENT_FLOWER_ELECTRIC, $arr) ||
           in_array(Membership::PKEY_STUDENT_FH_ELECTRIC, $arr) ||
           in_array(Membership::PKEY_STUDENT_IC_ELECTRIC, $arr) ||
           in_array(Membership::PKEY_STUDENT_SP_PHYTO, $arr)
          )
        {
            return CustomerGrade::PKEY_SA;
        }

        // 2020/01/06 スペシャルプラスランクアップに所属している会員はスペシャルプラス会員にする
        $pointing_customer = CustomerMembership::find()->active()
                            ->andWhere(['in', 'customer_id', $customer_arr])
                            ->andWhere(['membership_id' => Membership::PKEY_SPECIALPLUS_RANKUP])
                            ->asArray()->all();

        if ($pointing_customer) {
            return CustomerGrade::PKEY_SA;
        }


        // １年以内に会員登録し、スペシャルになっている会員は据え置く
        $new_customer = Customer::find()
                                ->andWhere(['in', 'customer_id', $customer_arr])
                                ->andWhere(['grade_id' => 2])
                                ->andWhere('create_date > (NOW() - INTERVAL 1 YEAR)')
                                ->asArray()->one();
        if($new_customer) {
            return CustomerGrade::PKEY_KA;
        }

        // 過去１年以内に買い物をした顧客をスペシャルとする（ただしレストランのみの伝票は認めない）
        if (time() >= strtotime('2019-06-01 00:00:00')) {

            // レストランカテゴリを除外するためにはレメディーも一緒だと難しいので分割する
            $oneYear_purchases = Purchase::find()
                        ->leftJoin(['pi' => \common\models\PurchaseItem::tableName()], Purchase::tableName().'.purchase_id=pi.purchase_id')
                        ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.product_id=pi.product_id')
                        // ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.ean13=pi.code')
                ->where(['in', Purchase::tableName().'.customer_id', $customer_arr])
                ->andWhere(Purchase::tableName().'.create_date > (NOW() - INTERVAL 1 YEAR)')
                ->andWhere(Purchase::tableName().'.status IN (' . PurchaseStatus::PKEY_INIT . ',' . PurchaseStatus::PKEY_PAYING . ','  . PurchaseStatus::PKEY_SHIPPED . ',' . PurchaseStatus::PKEY_SHIPPING . ',' . PurchaseStatus::PKEY_PREORDER . ','. PurchaseStatus::PKEY_DONE . ')')
                ->andWhere(['<>', 'm.category_id', 5])
                ->select([Purchase::tableName().'.purchase_id'])
                ->limit(1);
                // var_dump($oneYear_purchases->createCommand()->rawSql);
            $oneYear_purchases = $oneYear_purchases->asArray()->one();

            // レメディーについて検索
            $oneYear_remedy_purchases = Purchase::find()
                ->leftJoin(['pi' => \common\models\PurchaseItem::tableName()], Purchase::tableName().'.purchase_id=pi.purchase_id')
                ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.remedy_id=pi.remedy_id')
                ->where(['in', Purchase::tableName().'.customer_id', $customer_arr])
                ->andWhere(Purchase::tableName().'.create_date > (NOW() - INTERVAL 1 YEAR)')
                ->andWhere(Purchase::tableName().'.status IN (' . PurchaseStatus::PKEY_INIT . ',' . PurchaseStatus::PKEY_PAYING . ','  . PurchaseStatus::PKEY_SHIPPED . ',' . PurchaseStatus::PKEY_SHIPPING . ',' . PurchaseStatus::PKEY_PREORDER . ','. PurchaseStatus::PKEY_DONE . ')')
                ->select([Purchase::tableName().'.purchase_id'])
                ->limit(1);

            $oneYear_remedy_purchases = $oneYear_remedy_purchases->asArray()
                ->one();

            // 伝票がヒットすればスペシャルとする
            // grade_id >= 2（スペシャル）な人（プレミアムだった人が資格喪失でここまで落ちてきた、など）
            $customer = Customer::find()->where(['customer_id' => $customer_id])->asArray()->one();

            if($customer['grade_id'] >= CustomerGrade::PKEY_KA && ($oneYear_purchases || $oneYear_remedy_purchases)) {

//$time = microtime(true) - $time_start;
//var_dump($time." 秒");
                return CustomerGrade::PKEY_KA;
            }

            // 買い物は専用売上入力（代理店レジ）によるdtb_pointingレコードも考える必要がある
            $oneYear_pointings = Pointing::find()
                        ->leftJoin(['pi' => \common\models\PointingItem::tableName()], Pointing::tableName().'.pointing_id=pi.pointing_id')
                        // ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.ean13=pi.code')
                        ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.product_id=pi.product_id')
                ->where(['in', Pointing::tableName().'.customer_id', $customer_arr])
                ->andWhere(Pointing::tableName().'.create_date > (NOW() - INTERVAL 1 YEAR)')
                ->andWhere(Pointing::tableName().'.status IN (' . Pointing::STATUS_SOLD . ')')
                ->andWhere(Pointing::tableName().'.subtotal > 0')
                ->select([Pointing::tableName().'.pointing_id'])
                ->limit(1);

            $oneYear_pointings = $oneYear_pointings->asArray()
                ->one();

            $oneYear_remedy_pointings = Pointing::find()
                    ->leftJoin(['pi' => \common\models\PointingItem::tableName()], Pointing::tableName().'.pointing_id=pi.pointing_id')
                    // ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.ean13=pi.code')
                    ->leftJoin(['m' => \common\models\ProductMaster::tableName()], 'm.remedy_id=pi.remedy_id')
                    ->where(['in', Pointing::tableName().'.customer_id', $customer_arr])
                    ->andWhere(Pointing::tableName().'.create_date > (NOW() - INTERVAL 1 YEAR)')
                    ->andWhere(Pointing::tableName().'.status IN (' . Pointing::STATUS_SOLD . ')')
                    ->andWhere(Pointing::tableName().'.subtotal > 0')
                    ->select([Pointing::tableName().'.pointing_id'])
                    ->limit(1);

            $oneYear_remedy_pointings = $oneYear_remedy_pointings->asArray()
                    ->one();
            // 現時点レコードでスペシャル以上の人 かつ 一年以内の代理店レジ伝票があればスペシャル
            if($customer['grade_id'] >= CustomerGrade::PKEY_KA && ($oneYear_pointings || $oneYear_remedy_pointings)) {
                return CustomerGrade::PKEY_KA;
            }
        }

        // 過去1年以内に適用書を作成した顧客をスペシャルとする
        $recipes = Recipe::find()
                ->where(['in', 'client_id', $customer_arr])
                ->andWhere('create_date > (NOW() - INTERVAL 1 YEAR)')
                ->andWhere('status IN (' . Recipe::STATUS_INIT . ',' . Recipe::STATUS_SOLD . ')')
                ->asArray()->all();

        // 過去1年以内に買い物をした顧客がスペシャルであり続ける条件
        $purchases = Purchase::find()
                ->where(['in', 'customer_id', $customer_arr])
                ->andWhere('create_date > (NOW() - INTERVAL 1 YEAR)')
                ->andWhere('status IN (' . PurchaseStatus::PKEY_INIT . ',' . PurchaseStatus::PKEY_PAYING . ','  . PurchaseStatus::PKEY_SHIPPED . ',' . PurchaseStatus::PKEY_SHIPPING . ',' . PurchaseStatus::PKEY_PREORDER . ','. PurchaseStatus::PKEY_DONE . ')')
                ->asArray()->all();

        // 仮発行の適用書を購入した顧客を拾えるようにする
        $purchase_recipe = \common\models\LtbPurchaseRecipe::find()
                ->andwhere(['purchase_id' =>
                           \common\models\Purchase::find()
                               ->andwhere(['in', 'customer_id', $customer_arr])
                               ->andWhere('create_date > (NOW() - INTERVAL 1 YEAR)')
                               ->select('purchase_id')])->all();

        // PKEY_KA（スペシャル） ※1年間適用書作成も伝票もなければスタンダード
        if ($recipes || $purchase_recipe) {
            if (!$recipes && !$purchases && !$purchase_recipe) {
                return CustomerGrade::PKEY_AA;
            }
            return CustomerGrade::PKEY_KA;
        }

        // 2019/02/26, 2019/03/13, 2019/05/22 一時的に特定の顧客をスペシャル会員にする
        if (in_array('26284', $customer_arr) || in_array('29202', $customer_arr) || in_array('8198', $customer_arr)) {
            return CustomerGrade::PKEY_KA;
        }

        // 2019/03/26 スペシャルランクアップに所属している会員はスペシャル会員にする
        $pointing_customer = CustomerMembership::find()->active()
                            ->andWhere(['in', 'customer_id', $customer_arr])
                            ->andWhere(['membership_id' => Membership::PKEY_SPECIAL_RANKUP])
                            ->asArray()->all();

        if ($pointing_customer) {
            return CustomerGrade::PKEY_KA;
        }

        // PKEY_AA（スタンダード）
        return CustomerGrade::PKEY_AA;
    }

    /**
     * calculate Customer's Point
     *
     * @param string $datetime current datetime
     * @return integer
     */
    public function currentPoint($datetime = null)
    {

        if(null === $datetime)
            $datetime = date('Y-m-d H:i:s');

        // SUM(point_given)
        $cur_point = $this->db->createCommand('SELECT SUM(point_given) FROM dtb_purchase WHERE customer_id = :cid AND status NOT IN (:cancel, :void) AND (shipped = 1 OR shipped = 9 OR paid = 1) AND create_date <= :date')
                  ->bindValues([
                      ':cid'   => $this->customer_id,
                      ':date'  => $datetime,
                      ':cancel'=> PurchaseStatus::PKEY_CANCEL,
                      ':void'  => PurchaseStatus::PKEY_VOID,
                  ])
                  ->queryScalar();

        // SUM(point_consume)
        $cur_point -= $this->db->createCommand('SELECT SUM(point_consume) FROM dtb_purchase WHERE customer_id = :cid AND status NOT IN (:cancel, :void) AND create_date <= :date')
                  ->bindValues([
                      ':cid'   => $this->customer_id,
                      ':date'  => $datetime,
                      ':cancel'=> PurchaseStatus::PKEY_CANCEL,
                      ':void'  => PurchaseStatus::PKEY_VOID,
                  ])
                  ->queryScalar();

        // SUM(dtb_purchase.point_given)
        $cur_point += $this->db->createCommand('SELECT SUM(point_given) - SUM(point_consume) FROM dtb_pointing WHERE customer_id = :cid AND status = :status AND create_date <= :date')
                  ->bindValues([
                      ':cid'    => $this->customer_id,
                      ':status' => Pointing::STATUS_SOLD,
                      ':date'   => $datetime,
                  ])
                  ->queryScalar();

        return $cur_point;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validateBirth($attr, $params)
    {
        if(! $this->birth)
            $this->addError($attr, "生年月日が未指定です");

        $birth = strtotime($this->birth);
        $y200  = (60 * 60 * 24) * 365 * 200;

        if(time() < $birth)
            $this->addError($attr, "生年月日に未来の日付が指定されています");

        elseif($birth < time() - $y200)
            $this->addError($attr, "生年月日に200年以上過去の日付が指定されています");

        return $this->hasErrors($attr);
    }

    public function validateTel($attr) {
        $tel = Customer::find()
                ->active()
                ->andWhere(['tel01' => $this->tel01, 'tel02' => $this->tel02, 'tel03' => $this->tel03]);
        if ($this->customer_id) {
            $tel->andWhere("customer_id <> {$this->customer_id}");
        }
        $check = $tel->count();

        if ($check) {
            $this->addError($attr, "入力された電話番号は既に使われています。");
        }
        return $this->hasErrors($attr);
    }

    /**
     * search address from zip code
     * @return false or array of address
     */
    public function zip2addr()
    {
        $candidate = \common\models\Zip::zip2addr($this->zip01, $this->zip02);
        if(! $candidate)
        {
            $this->addError('zip02', "郵便番号に一致する住所が検索できませんでした");
            return false;
        }

        // apply the first address to self
        $this->pref_id = $candidate->pref_id;
        $this->addr01  = $candidate->addr01[0];

        return $candidate->addr01;
    }

    /************ INHERITED METHODS *************/

    public function afterFind()
    {
        $this->point    = $this->currentPoint();
        $this->grade_id = self::currentGrade($this->customer_id);

        if($this->getParent()->exists())
            $this->scenario = self::SCENARIO_CHILDMEMBER;

        parent::afterFind();
    }

    /**
     * @return boolean
     */
    public function beforeValidate()
    {
        if('0000-00-00' === $this->birth){ $this->birth = null; }

        $mode = 'Hcas'; // 英数字スペースは「半角」、「半／全カナ」は「全角かな」に
        $this->kana01 = mb_convert_kana($this->kana01, $mode);
        $this->kana02 = mb_convert_kana($this->kana02, $mode);

        return parent::beforeValidate();
    }

    /**
     * @return void
     */
    public function afterValidate()
    {
        if(! $this->hasErrors() && ('zip2addr' == $this->scenario))
        {
            $this->zip2addr();
        }

        parent::afterValidate();
    }

    /**
     * @return boolean: whether allow save() or not
     */
    public function beforeSave($insert)
    {
        if(in_array($this->scenario, ['zip2addr']))
           return false;

        if (parent::beforeSave($insert))
        {
            if (true == $insert)
            {
                $this->auth_key    = Yii::$app->getSecurity()->generateRandomString();
                $this->expire_date = self::DATETIME_MAX;
            }
            elseif(strtotime($this->expire_date) < time())
            {
                $this->addError('expire_date', "有効期限を過ぎたアカウントは編集できません");
                return false;
            }

            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // CustomerAddrBookの該当レコードを更新する
        // 会員証NOが更新されている場合を考えて、紐づくすべての会員証NOを取得して処理する
        $codes = \common\models\Membercode::find()->where(['customer_id' => $this->customer_id])->all();
        foreach ($codes as $code) {
            $addrBooks = \common\models\CustomerAddrbook::find()->where(['code' => $code->code])->all();
            foreach ($addrBooks as $addrBook) {
                $addrBook->code2addr();
                // 旧会員証NOを現会員証NOで上書きする
                $addrBook->code = $this->code;
                $addrBook->save();
            }
        }
    }

}

class CustomerQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere('NOW() <= dtb_customer.expire_date');
        else
            return $this->andWhere('dtb_customer.expire_date < NOW()');
    }

    public function child($state = true)
    {
        if($state)
            $operator = 'in';
        else
            $operator = 'not in';

        return $this->andWhere([$operator,
                                'dtb_customer.customer_id',
                                CustomerFamily::find()->select('child_id')]);
    }

    public function parent($state = true)
    {
        if($state)
            $operator = 'in';
        else
            $operator = 'not in';

        return $this->andWhere([$operator,
                                'dtb_customer.customer_id',
                                CustomerFamily::find()->select('parent_id')]);
    }

    /*
     * @param $id integer || Array
     */
    public function member($id)
    {
        return $this->innerJoinWith([
            'memberships' => function(CustomerMembershipQuery $q) use($id) {
                $q->active()
                  ->member($id);
            },
        ]);
    }
}

class AddMembership extends \yii\base\Behavior
{
    public $customer_id;
    public $membership_id;
    public $start_date;
    public $expire_date;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
        ];
    }

    public function afterInsert($event)
    {
        $profile = new CustomerMembership([
            'customer_id'   => $this->customer_id,
            'membership_id' => $this->membership_id,
            'start_date'    => $this->start_date,
            'expire_date'   => $this->expire_date,
        ]);

        $profile->save();
    }

}

class FixMembercode extends \yii\base\Behavior
{
    public function events()
    {
        return [
            Customer::EVENT_AFTER_INSERT => 'afterInsert',
        ];
    }

    /* @return void */
    public function afterInsert($event)
    {
        if($this->owner instanceof Customer)
            $cid = $this->owner->customer_id;
        elseif($sender instanceof Customer)
            $cid = $sender->customer_id;

        if(! isset($cid))
            // i don't know how to create membercode without customer_id
            return;

        $this->createMembercode($cid);
    }

    /* @return void */
    private function createMembercode($customer_id)
    {
        $mcode = new Membercode([
            'customer_id' => $customer_id,
        ]);

        if(! $mcode->save())
            Yii::error([
                sprintf('saving Membercode failed for customer_id(%d)', $customer_id),
                $mcode->errors,
            ], self::className().'::'.__FUNCTION__);
    }

}
