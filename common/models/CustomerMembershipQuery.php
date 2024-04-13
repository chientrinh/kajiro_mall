<?php
namespace common\models;

use Yii;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/CustomerMembershipQuery.php $
 * $Id: CustomerMembershipQuery.php 2794 2016-07-30 06:21:50Z mori $
 *
 * This is the query class for table "dtb_customer_membership".
 */

class CustomerMembershipQuery extends \yii\db\ActiveQuery
{
    public function active($state = true)
    {
        if($state)
            return $this->andWhere('NOW() >= dtb_customer_membership.start_date')
                        ->andWhere('NOW() <= dtb_customer_membership.expire_date');
        else
            return $this->andWhere(['or', ['dtb_customer_membership.expire_date < NOW()'],
                                          ['dtb_customer_membership.start_date  > NOW()']]);
    }

    public function toranoko($flag = true)
    {
        $id = [
            Membership::PKEY_TORANOKO_GENERIC,
            Membership::PKEY_TORANOKO_NETWORK,
            Membership::PKEY_TORANOKO_FAMILY,
            Membership::PKEY_TORANOKO_GENERIC_UK,
            Membership::PKEY_TORANOKO_NETWORK_UK,
        ];

        if($flag)
            return $this->andWhere(['dtb_customer_membership.membership_id' => $id]);
        else
            return $this->andWhere(['NOT',['dtb_customer_membership.membership_id' => $id]]);
    }

    public function member($id)
    {
        return $this->andWhere(['dtb_customer_membership.membership_id' => $id]);
    }
}
