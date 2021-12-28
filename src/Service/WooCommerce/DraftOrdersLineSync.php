<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoDraftOrder;

class DraftOrdersLineSync
{
    public function __invoke($cartUpdated)
    {
        $sessionKey = DraftOrdersSync::getCustomerID();
        $mtoDO = new MtoDraftOrder($sessionKey);
        if($cartUpdated && $mtoDO->getExternalId()){
            //update cart data before proceed
            $mtoDO->setCart(DraftOrdersSync::getCartContent())->setCartValue(DraftOrdersSync::getCartTotal())->save();
            //DraftOrdersSync::runBackgroundSync($mtoDO);
            wp_schedule_single_event(time() + 60, 'mto_background_draft_order_sync', [$mtoDO]); // run in 60 seconds
        }
        return $cartUpdated;
    }

    public static function removeItemFromCart($cart_item_key, $that){
        $sessionKey = DraftOrdersSync::getCustomerID();
        $mtoDO = new MtoDraftOrder($sessionKey);
        if($mtoDO->getExternalId()){
            wp_schedule_single_event(time() + 60, 'mto_background_draft_order_sync', [$mtoDO]); // run in 60 seconds
        }
    }

    public static function runBackgroundSync(MtoDraftOrder $mtoDO){
        $mtoDO->syncOrderLines();
    }
}