<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoDraftOrder;

class DraftOrdersLineSync
{
    public function __invoke($cartUpdated)
    {
        $sessionKey = DraftOrdersSync::getCustomerID();
        $mtoDO = new MtoDraftOrder($sessionKey);
        if ($cartUpdated && $mtoDO->getExternalId()) {
            //update cart data before proceed
            $mtoDO->setCart(DraftOrdersSync::getCartContent())->setCartValue(DraftOrdersSync::getCartTotal())->save();
            //DraftOrdersSync::runBackgroundSync($sessionKey); // uncomment to debug without delay
            as_schedule_single_action(time(), 'mto_background_draft_order_sync', [$sessionKey]); // run in 10 seconds
        }
        return $cartUpdated;
    }

    public static function removeItemFromCart($cart_item_key, $that)
    {
        $sessionKey = DraftOrdersSync::getCustomerID();
        $mtoDO = new MtoDraftOrder($sessionKey);
        $mtoDO->setCartValue(DraftOrdersSync::getCartTotal())
            ->setCart(DraftOrdersSync::getCartContent())
            ->save();
        //DraftOrdersSync::runBackgroundSync($sessionKey); // uncomment to debug without delay
        if ($mtoDO->getExternalId()) {
            as_schedule_single_action(time(), 'mto_background_draft_order_sync', [$sessionKey]); // run in 60 seconds
        }
    }

    public static function runBackgroundSync(MtoDraftOrder $mtoDO)
    {
        $mtoDO->syncOrderLines();
    }
}