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
            static::runBackgroundSync($mtoDO);
            //wp_schedule_single_event(time() + 60, 'mto_background_draft_order_sync', [$mtoDO]);
        }
        return $cartUpdated;
    }

    public static function removeItemFromCart($cart_item_key, $that){
        DraftOrdersSync::syncOrder();
    }

    public static function runBackgroundSync(MtoDraftOrder $mtoDO){
        $mtoDO->syncOrderLines();
    }
}