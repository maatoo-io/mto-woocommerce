<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class DraftOrdersSync
{
    public function __invoke($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        static::syncOrder();
    }

    public static function getCustomerID()
    {
        global $woocommerce;

        return $woocommerce->session->get_customer_unique_id();
    }

    /**
     * @return bool
     */
    public static function isOrderCreated(): ?bool
    {
        if (empty($_COOKIE['mto_draft_order_id'])) {
            return false;
        }
        return true;
    }

    public static function setDraftOrderId($id)
    {
        wc_setcookie('mto_draft_order_id', $id);
    }

    public static function getDraftOrderId()
    {
        return $_COOKIE['mto_draft_order_id'] ?? false;
    }

    private static function getEndpoint()
    {
        $endpoint = !static::isOrderCreated()
            ? MtoConnector::getApiEndPoint('order')->create
            : MtoConnector::getApiEndPoint('order')->edit;

        if (static::getDraftOrderId()) {
            $endpoint->route = str_replace('{id}', static::getDraftOrderId(), $endpoint->route);
        }
        return $endpoint;
    }

    public static function syncOrder()
    {
        $store = MtoStoreManger::getStoreData();
        $leadId = $_COOKIE['mtc_id'] ?? null;
        $orderRequestData = [
            'store' => $store->getId(),
            'externalOrderId' => static::getDraftOrderId() ?: static::getCustomerID(),
            'externalDateProcessed' => null, //what if order is not processed?
            'externalDateUpdated' => date('Y-m-d H:i:s', strtotime('now')),
            'externalDateCancelled' => null,
            'value' => static::getCartTotal(),
            'url' => wc_get_cart_url(), // what if order hasn't been placed yet?
            'status' => 'draft',
            'lead_id' => $leadId
        ];

        $connector = MtoConnector::getInstance(new MtoUser());
        $response = $connector->getResponseData(static::getEndpoint(), $orderRequestData);

        if (!empty($response['order'])) {
            $id = $response['order']['externalOrderId'] ?? null;
            if ($id && !empty($response['order']['id'])) {
                static::setDraftOrderId($response['order']['id']);
                DraftOrdersLineSync::syncOrderLines($response['order']['id']);
            }
        }
    }

    public static function getCartTotal()
    {
        global $woocommerce;
        $total = 0.0;
        $cartContent = $woocommerce->cart->get_cart_contents();
        foreach ($cartContent as $item) {
            $product = $item['data'] ?? null;
            $qnt = $item['quantity'] ?? 1;
            $total += floatval($product->get_price()) * $qnt;
        }
        return $total;
    }
}