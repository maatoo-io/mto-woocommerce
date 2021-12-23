<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoDraftOrder;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class DraftOrdersSync
{
    public function __invoke($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        if (self::getCustomerID()) {
            static::syncOrder();
        }
    }

    public static function getCustomerID()
    {
        global $woocommerce;

        return $woocommerce->session->get_customer_unique_id();
    }

    /**
     * Method to collect data received from user and stare it for
     */
    public static function syncOrder()
    {
        $sessionKey = static::getCustomerID();
        if(!$sessionKey || !isset($_COOKIE['mtc_id'])){
            return;
        }
        $cart = static::getCartContent();
        $cartValue = static::getCartTotal();
        $dateModified = date('Y-m-d H:i:s', strtotime('now'));

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

    /**
     * Calculate cart total value
     * @return float|int
     */
    public static function getCartTotal()
    {
        $total = 0.0;
        $cartContent = static::getCartContent();
        foreach ($cartContent as $item) {
            $total += floatval($item['product_price']) * $item['quantity'];
        }
        return $total;
    }

    /**
     * get up-to-date cart content
     * @return array
     */
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

    /**
     * Schedule event handler
     * @param MtoDraftOrder $mtoDO
     */
    public static function runBackgroundSync(MtoDraftOrder $mtoDO)
    {
        if (!$mtoDO) {
            LogData::writeDebug('Incorrect input data: $mtoDO is empty');
        }
        $mtoDO->sync();
    }

    /**
     * Add products to the cart if user back by maatoo's link
     */
    public static function wakeupUserSession()
    {
        if (empty($_GET['mto']) || !empty($_COOKIE['mto_wakeup_session'])) {
            //don't wake cart up more than 1 time for session
            return;
        }
        $data = unserialize(base64_decode($_GET['mto']));
        $mtoDO = MtoDraftOrder::getById($data['draftOrderId']);
        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $key => $item) {
                if (strpos($key, 'wp_woocommerce_session_') !== false) {
                    $wcSessionData = explode('||', $item); // retrieve session info
                    $customerId = $wcSessionData[0] ?? '';
                    $mtoDO->setExternalId($customerId);
                    $mtoDO->save();
                }
            }
        }
        global $woocommerce;
        foreach ($mtoDO->getCart() as $item) {
            $woocommerce->cart->add_to_cart($item['product_id']);
        }
        wc_setcookie('mto_wakeup_session', '1');
    }
}