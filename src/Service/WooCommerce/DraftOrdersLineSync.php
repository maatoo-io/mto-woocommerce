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
            //DraftOrdersSync::runBackgroundSync($sessionKey);
            $args = [$sessionKey];
            if(!as_next_scheduled_action('mto_background_draft_order_sync', $args)){
                as_schedule_single_action(time() + 60, 'mto_background_draft_order_sync', $args); // run in 60 seconds
            }
        }
        return $cartUpdated;
    }

    public static function removeItemFromCart($cart_item_key, $that){
        $sessionKey = DraftOrdersSync::getCustomerID();
        $mtoDO = new MtoDraftOrder($sessionKey);
        $args = [$sessionKey];
        if($mtoDO->getExternalId() && !as_next_scheduled_action('mto_background_draft_order_sync', $args)){
            as_schedule_single_action(time() + 60, 'mto_background_draft_order_sync', $args); // run in 2 seconds
        }
    }

    public static function runBackgroundSync(MtoDraftOrder $mtoDO){
        $mtoDO->syncOrderLines();
    }
}