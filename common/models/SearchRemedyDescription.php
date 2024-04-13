<?php
/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/SearchRemedyDescription.php $
 * $Id: $
 */

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RemedyPotency;
use common\models\RemedyCategoryDescription;

/**
 * SearchRemedyDescription represents the model behind the search form about `common\models\RemedyDescription`.
 */
class SearchRemedyDescription extends RemedyDescription
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['remedy_id',
                 'remedy_category_id',
                 'title', 'body',
                 'desc_division',
                 'is_display'
                ],
                'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

}
