<?php 
namespace common\models;

/**
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/PurchaseInterface.php $
 * $Id: PurchaseInterface.php 1769 2015-11-05 09:55:16Z mori $
 *
 */

interface PurchaseInterface
{
    public function getBranch();
    public function getCustomer();
    public function getCustomer_id();
    public function getDelivery();
    public function getPayment();
}
