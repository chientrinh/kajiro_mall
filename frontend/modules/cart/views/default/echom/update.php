<?php
/**
 * @link    $URL: https://tarax.toyouke.com/svn/MALL/frontend/modules/cart/views/default/update.php $
 * @version $Id: update.php 2834 2016-08-11 03:53:10Z mori $
 */

$this->params['body_id']       = 'Cart';
$this->params['breadcrumbs'][] = ['label' => "カート", 'url' => ['/cart']];
?>

<div class="cart-default-update">

<?php
if('addrbook' == $target)
    echo $this->render('_addrbook', ['model'=>$model,'cart_idx'=>$cart_idx]);

elseif('address' == $target)
    echo $this->render('_addr', ['model'=>$model,'cart_idx'=>$cart_idx]);

elseif('guest-signup' == $target)
    echo $this->render('../../guest/signup', ['model'=>$model,'cart_idx'=>$cart_idx]);

elseif('gift' == $target)
    echo $this->render('_gift',['cart'=>$cart,'cart_idx'=>$cart_idx]);

elseif('payment' == $target)
    echo $this->render('_payment',['model'=>$model,'cart'=>$cart,'cart_idx'=>$cart_idx]);

elseif('point' == $target)
    echo $this->render('_point',['model'=>$model]);

elseif('date' == $target)
    echo $this->render('_datetime',['model'=>$model,'cart'=>$cart,'cart_idx'=>$cart_idx]);

else
    echo 'error, not implemented: ', $target;
?>
</div>
