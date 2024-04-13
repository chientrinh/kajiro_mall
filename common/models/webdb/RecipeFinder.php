<?php
namespace common\models\webdb;

use Yii;
use \yii\helpers\ArrayHelper;

/**
 * Recipe helper
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/webdb/RecipeFinder.php $
 * $Id: RecipeFinder.php 3284 2017-05-09 07:11:05Z kawai $
 */

class RecipeFinder extends \yii\base\Model
{
    public  $id;
    public  $pw;
    private $_model;

    public function rules()
    {
        return [
            [['id','pw'],'filter', 'filter' => function ($value) {
                return mb_convert_kana($value, 'r'); // r: zenkaku -> hankaku
            }],
            [['id','pw'],'trim'], // zenkaku spaces are now converted to ascii
            [['id','pw'],'required'],
            [['id','pw'],'string', 'min'=>4,'max'=>12],
            [['id','pw'],'integer','min'=>1],
        ];
    }

    public function get($params = [])
    {
        $this->id = ArrayHelper::getValue($params, 'id', $this->id);
        $this->pw = ArrayHelper::getValue($params, 'pw', $this->pw);
        $this->_model = null; // reset to null before search

        if($this->validate())
            $this->_model = $this->webdbSearch($this->id, $this->pw);

        return $this->_model;
    }

    public function getModel()
    {
        return $this->_model;
    }

    private function webdbSearch($id, $pw)
    {
        $row = Yii::$app->webdb18->createCommand(
            ' SELECT syoho2id AS id, passwd AS pw, syohoid_he FROM tblsyoho2 '
            .'WHERE syoho2id = :id AND passwd = :pw ')
            ->bindValues([':id'=>$id, ':pw'=>$pw])
            ->queryOne();

        if(! $row)
            return null;

        if(0 < $row['syohoid_he'])
            $items = self::findItemsW20($row['syohoid_he']);
        else
            $items = self::findItemsW18($row['id']);

        if(! $items)
            Yii::error(['could not find items of the recipe:', $row], self::className().'::'.__FUNCTION__);

        return self::buildModel($items);
    }

    private function buildModel($items)
    {
        $recipe_id = (0 - abs($this->id));
        $recipe = new \common\models\webdb\RecipeForm([
            'homoeopath_id' => null,
            'recipe_id'     => $recipe_id,
            'pw'            => $this->pw,
            'delivery'      => false,
        ]);

        $models = [];
        foreach($items as $item)
        {
            $stock = self::convertItemToRemedyStock($item);

            if(! $stock)
                $stock = self::mockupRemedyStock($item);

            $stock = self::adjustAttributes($stock, $item);

            $models[] = new \common\models\RecipeItemForm([
                'recipe_id' => $recipe_id,
                'quantity'  => $item->qty,
            
                'remedy_id' => $stock->remedy_id,
                'potency_id'=> $stock->potency_id,
                'vial_id'   => $stock->vial_id,
                'name'      => $stock->name,
                'model'     => $stock,
            ]);
        }

        $parent = 0;
        $seq    = 0;
        foreach($models as $model)
        {
            $model->seq = $seq++;
            if(\common\models\RemedyVial::DROP === $model->vial_id)
                $model->parent = $parent;
            else
                $parent = $model->seq;

            $recipe->items[] = $model;
        }
        if(! $recipe->save(false, null))
            Yii::error(['import webdb recipe has failed.',$recipe->attributes,'errors'=>$model->errors],self::className().'::'.__FUNCTION__);

        return $recipe;
    }

    private static function adjustAttributes(\common\models\webdb\RemedyStock $model, $param)
    {
        if(0 === $model->remedy_id)
        {
            $model->potency_id = null;
            $model->prange_id  = \common\models\RemedyPriceRange::PKEY_COMPOSE_BASE;
        }

        if(\common\models\RemedyVial::DROP == $model->vial_id)
            $model->prange_id = \common\models\RemedyPriceRange::PKEY_COMPOSE_BASE;

        if($model->remedy_id)
        {
            $remedy = \common\models\Remedy::findOne($model->remedy_id);

            if((\common\models\RemedyPotency::MT === $model->potency_id) &&
               (0 === (int) $remedy->getStocks()
                                   ->where(['potency_id'=>\common\models\RemedyPotency::MT])
                                   ->count()))
            {
                $model->potency_id = \common\models\RemedyPotency::find()
                                   ->select('potency_id')
                                   ->where(['name'=>'combination'])
                                   ->scalar();
            }

            if(! $model->vial_id)
                 $model->vial_id = $remedy->getStocks()
                                ->select('vial_id')
                                ->andWhere(['potency_id'=>$model->potency_id])
                                ->andWhere(['NOT',['vial_id'=>\common\models\RemedyVial::MICRO_BOTTLE]])
                                ->orderBy('vial_id ASC')
                                ->scalar();
        }
        if(! $model->vial_id)
             $model->vial_id = \common\models\RemedyVial::SMALL_BOTTLE;

        return $model;
    }

    private static function mockupRemedyStock($item)
    {
        $remedy_id = 0;

        return new \common\models\webdb\RemedyStock([
            'price'      => $item->price,
            'name'       => $item->abbr,
            'potency_id' => $item->potency_id,
            'vial_id'    => $item->vial_id,
            'remedy_id'  => $remedy_id,
            'price'      => $item->price,
            'prange_id'  => null,
        ]);
    }

    private static function convertItemToRemedyStock($item)
    {
        if(null === ($remedy = \common\models\Remedy::findOne(['abbr'=>$item->abbr])))
            return null;

        $model = new \common\models\webdb\RemedyStock([
            'price'      => $item->price,
            'potency_id' => $item->potency_id,
            'vial_id'    => $item->vial_id,
            'remedy_id'  => $remedy->remedy_id,
            'price'      => $item->price,
            'prange_id'  => null,
        ]);
        $model->name = $model->getName();

        return $model;
    }

    private static function findItemsW18($pkey)
    {
        $items = Yii::$app->webdb18->createCommand(
            ' SELECT
 dsp_num       as seq,
 fukuyo_count  as qty,
 fukuyo_coment as note,
 d_item_1_syohin_nameid as remedy_id,
 d_item_4_syohin_nameid as potency_id
 FROM tblfukuyo2 '
            .'WHERE syoho2id = :id '
            .'ORDER BY dsp_num '
        )
               ->bindValues([':id' => $pkey])
        ->queryAll();

        if($items)
            return self::translateItems(Yii::$app->webdb18, $items);

        return [];
    }

    private static function findItemsW20($pkey)
    {
        $items = Yii::$app->webdb20->createCommand(
            ' SELECT
 dsp_num       as seq,
 fukuyo_count  as qty,
 fukuyo_coment as note,
 d_item_1_syohin_nameid as remedy_id,
 d_item_4_syohin_nameid as potency_id
 FROM tblfukuyo '
            .'WHERE syohoid = :id '
            .'ORDER BY dsp_num '
        )
               ->bindValues([':id' => $pkey])
               ->queryAll();

        if($items)
            return self::translateItems(Yii::$app->webdb20, $items);

        return [];
    }
        
    private static function translateItems($db, $items)
    {
        $rows = [];

        foreach($items as $item)
            $rows[] = self::translateItem($db, $item);

        return $rows;
    }

    private static function translateItem($db, $item)
    {
        $item = (object) $item;

        $row = $db->createCommand(
            ' SELECT 
 i1.d_item_1_syohin_name as abbr
,i4.d_item_4_syohin_name as potency
,m.syo_mas_std_tanka     as price
,m.syo_mas_num           as code
 FROM tmd_item_1_syohin_name i1
 JOIN tblsyo_mas m ON m.d_item_1_syohin_nameid = i1.d_item_1_syohin_nameid AND
                      m.d_item_4_syohin_nameid = :potency_id
 LEFT JOIN tmd_item_4_syohin_name i4
                   ON m.d_item_4_syohin_nameid = i4.d_item_4_syohin_nameid
 WHERE i1.d_item_1_syohin_nameid = :remedy_id'
        )
                ->bindValues([
                    ':potency_id' => $item->potency_id,
                    ':remedy_id'  => $item->remedy_id,
                ])
                ->queryOne();

        if(!$row)
            $row = [
                'abbr'   => null,
                'potency'=> null,
                'price'  => null,
                'code'   => null,
            ];
        if('euc-jp' === $db->charset)
        {
            foreach($row as $k => $v)
                if(mb_detect_encoding($v, ['CP51932'])) // is value EUC-WIN-JP ?
                    $row[$k] = mb_convert_encoding($v, 'UTF-8', 'CP51932');
        }
        $row = (object) $row;

        $remedy = (object) [
            'tmd1_id'    => $item->remedy_id,
            'abbr'       => self::translateAbbr($row->abbr),
            'vial_id'    => self::translateVial($row->abbr),
            'potency_id' => self::translatePotency($row->potency),
            'code'       => mb_convert_kana($row->code,'r'),
            'qty'        => $item->qty,
            'price'      => $row->price,
            'note'       => $item->note,
            'seq'        => $item->seq,
        ];

        return $remedy;
    }

    private static function translateAbbr($abbr)
    {
        $abbr = mb_convert_kana($abbr,'r');
        $abbr = trim($abbr);
        $abbr = preg_replace('/\.$/','',$abbr);
        $abbr = preg_replace('/^[+@]/','',$abbr);

        if($model = \common\models\Remedy::findOne(['abbr'=>$abbr]))
            return $model->abbr;

        /**
         * convert webdb prod_name into Ebisu term
         * e.g. 'オリジナルMT)Alf Aut.' will become  'MT)Alf-aut'
         */
        $abbr = mb_convert_kana($abbr, 'rnsK');
        $abbr = trim($abbr);
        $abbr = strtr($abbr,' ','-');
        $abbr = preg_replace('/（/','(', $abbr);
        $abbr = preg_replace('/）/',')', $abbr);
        $abbr = preg_replace('/\(.+\)$/', '', $abbr);
        $abbr = preg_replace('/【.+】/', '', $abbr);
        $abbr = preg_replace('/\.$/','', $abbr);
        $abbr = preg_replace('/オリジナル/','', $abbr);
        $abbr = preg_replace('/サポートφ/','MT)S-', $abbr);
        $abbr = preg_replace('/サポート/','S-', $abbr);
        $abbr = preg_replace('/Berb-v/','Berb',$abbr);
        $abbr = preg_replace('/Ciner/','Cine',$abbr);
        if($model = \common\models\Remedy::findOne(['abbr'=>$abbr]))
            return $model->abbr;

        $abbr = preg_replace('/^MT\)/','', $abbr);
        if($model = \common\models\Remedy::findOne(['abbr'=>$abbr]))
            return $model->abbr;

        $abbr = self::translateKana2Abbr($abbr);
        if($model = \common\models\Remedy::findOne(['abbr'=>$abbr]))
            return $model->abbr;

        if($model = \common\models\Remedy::findOne(['ja'=>$abbr]))
            return $model->abbr;

        return $abbr;
    }

    private static function translatePotency($potency)
    {
        if(preg_match('/φ/', $potency))
            $potency = 'Φ';

        else
        $potency = preg_replace_callback(
            '/LM[_]?([0-9]+)/',
            function($m) {
                return "LM".sprintf('%02d', $m[1]);
            },
            $potency);

        if($model = \common\models\RemedyPotency::findOne(['name'=> trim($potency) ]))
            return $model->potency_id;

        // self::adjustAttributes() にて調整されるので MTかCOMBINATIONか、よくわからなかったら 初期値 MT を返す
        return \common\models\RemedyPotency::MT;
    }

    private static function translateVial($abbr)
    {
        if(preg_match('/^[@+]/', $abbr))
            return \common\models\RemedyVial::DROP;

        if('オリジナル小' == $abbr)
            return \common\models\RemedyVial::SMALL_BOTTLE;

        if('オリジナル大' == $abbr)
            return \common\models\RemedyVial::LARGE_BOTTLE;

        if('オリジナルアルポ（5ml）' == $abbr)
            return \common\models\RemedyVial::GLASS_5ML;

        if(preg_match('/^オリジナルMT/', $abbr))
            return \common\models\RemedyVial::GLASS_20ML;

        if(preg_match('/^オリジナルサポートφ/', $abbr))
            return \common\models\RemedyVial::GLASS_20ML;

        return null;
    }

    /**
     * provided for debug purpose, to ensure all remedy items are translatable
     * @return string : error messages if found for any abbr
     *               
     * this function is used by @backend/controllers/SiteController.php
     */
    public static function validateAllRemedies($db, $ignores)
    {
        $abbrs = $db->createCommand("
SELECT DISTINCT remedy_name FROM tmd_item_1_syohin_name
 WHERE remedy_name LIKE 'MT%%' OR remedy_name LIKE '@%' OR remedy_name LIKE '+%'
 ORDER BY remedy_name"
        )->queryColumn();

        $msg = '';
        foreach($abbrs as $k => $v)
        {
            $orig = $v;
            $abbr = self::translateAbbr($orig);

            if(! \common\models\Remedy::findOne(['abbr'=>$abbr]))
            {
                if(in_array($abbr, $ignores))
                   continue;

                $msg .= sprintf("not found: %s\n",$abbr);
            }
        }

        return nl2br($msg);
    }

    private static function translateKana2Abbr($abbr)
    {
        $list = self::getKanaList();
        if(array_key_exists($abbr, $list))
            return $list[$abbr];

        return $abbr; // failed translation
    }

    private static function getKanaList()
    {
        return [
            'アーティカ-プラットJ' => 'Urt-p',
            'アートメジアJ'       => 'Art-i',
            'アブシンシューム'     => 'Absin',
            'アラリアJ'          => 'Aral',
            'アヴィナサティーバJ'  => 'Aven',
            'エキネシアJ' => 'Echi',
            'エクィシータムJ' => 'Equis',
            'エリオボトリアJ' => 'Eriob',
            'カーディアスマリアナスJ' => 'Card-m',
            'カレンデュラJ' => 'Calen',
            'カレンデュラJ【ゴールド】' => 'Calen',
            'カレンデュラJ【スプレー＆大】セット' => 'Calen',
            'グリンデリア' => 'Grin',
            'ササJ' => 'Sasa',
            'シネラリアJ' => 'Cine',
            'ジンジバーJ' => 'Zing',
            'スーヤJ' => 'Thuj',
            'セラストラス' => 'Celas',
            'ソリデイゴJ' => 'Solid',
            'タラクシカムJ' => 'Tarax',
            'チコリューム' => 'Chicory',
            'バーバスカム' => 'Verb',
            'バレリアナJ' => 'Valer',
            'ファゴファイラムJ' => 'Fago',
            'プランターゴJ' => 'Plan',
            'ベリスペレニスJ' => 'Bell-p',
            'ボラーゴJ' => 'Borago',
            'ミュルフォリュームJ' => 'Mill',
            'モラスJ' => 'Morus',
            'ヤマブドウJ' => 'Yamab',
            'ユーパトリューム' => 'Eup-per',
            'ラパ' => 'Lappa',
            'ルータJ' => 'Ruta',
            'ルメックスJ' => 'Rumx',
            'サポートKeiga-V-C' => 'S-Keiga-V-C',
            'サポートNorito' => 'S-Norito',
            'サポートShingyo' => 'S-Shingyo',
       ];
    }

}

