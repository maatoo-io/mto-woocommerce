<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoDraftOrder;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class DraftOrdersSync
{
    public function __invoke($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        if(self::getCustomerID()){
            static::syncOrder();
        }
    }

    public static function getCustomerID()
    {
        global $woocommerce;

        return $woocommerce->session->get_customer_unique_id();
    }

    public static function syncOrder()
    {
        $cart = static::getCartContent();
        $cartValue = static::getCartTotal();
        $dateModified = date('Y-m-d H:i:s', strtotime('now'));
        $sessionKey = static::getCustomerID();
        $mtoDO = new MtoDraftOrder($sessionKey);
        if (!$mtoDO->getExternalId()) {
            $store = MtoStoreManger::getStoreData();
            $mtoDO = MtoDraftOrder::toMtoDraftOrder(
                $store->getId(),
                $sessionKey,
                $_COOKIE['mtc_id'] ?? null,
                $cart,
                $cartValue,
                $dateModified
            );
        } else {
            $mtoDO->setCart($cart)->setCartValue($cartValue)->setDateModified($dateModified);
        }
        //update record in DB
        $mtoDO->save();
        //static::runBackgroundSync($mtoDO);
        wp_schedule_single_event(time() + 60, 'mto_background_draft_order_sync', [$mtoDO]);
    }

    public static function getCartTotal()
    {
        $total = 0.0;
        $cartContent = static::getCartContent();
        foreach ($cartContent as $item) {
            $total += floatval($item['product_price']) * $item['quantity'];
        }
        return $total;
    }

    public static function getCartContent()
    {
        global $woocommerce;

        $cart = $woocommerce->cart;
        $data = [];

        $cartContent = $cart->get_cart_contents();
        foreach ($cartContent as $item) {
            $data[] = [
                'store' => MTO_STORE_ID,
                'mto_product_id' => get_post_meta($item['data']->get_id(), '_mto_id', true),
                'product_id' => $item['data']->get_id(),
                'quantity' => $item['quantity'] ?? 1,
                'product_price' => $item['data']->get_price(),
            ];
        }
        return $data;
    }

    public static function runBackgroundSync(MtoDraftOrder $mtoDO)
    {
        if (!$mtoDO) {
            LogData::writeDebug('Incorrect input data: $mtoDO is empty');
        }
        $mtoDO->sync();
    }

    public static function wakeupUserSession(){
        if(empty($_GET['mto'])){
            return;
        }
        $data =unserialize(base64_decode($_GET['mto']));
        $mtoDO = new MtoDraftOrder($data['sessionKey']);
        if(!$mtoDO->getExternalId()){
            $mtoDO = $mtoDO->getById($data['draftOrderId']);
        }
        $_COOKIE['test'] = 'test';
        global $woocommerce;
        foreach ($mtoDO->getCart() as $item){
            $woocommerce->cart->add_to_cart( $item['product_id'] );
        }
    }
}