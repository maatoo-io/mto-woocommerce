<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

class DraftOrdersLineSync
{
    public function __invoke($cartUpdated)
    {
        $mtoId = DraftOrdersSync::getDraftOrderId();
        if($cartUpdated && $mtoId){
            DraftOrdersSync::syncOrder();
        }
        return $cartUpdated;
    }

    public static function syncOrderLines($mtoOrderID){
        $connector = MtoConnector::getInstance(new MtoUser());
        $orderLinesRemote = $connector->getRemoteList($connector::getApiEndPoint('order'), 0, $mtoOrderID)['orderLines'] ?? [];
        $formattedArray = []; // formatted array to contain remote order lines
        foreach ($orderLinesRemote as $id => $item) {
            $formattedArray[$id]['id'] = $id;
            $formattedArray[$id]['store'] = $item['store']['id'];
            $formattedArray[$id]['product'] = $item['product']['id'];
            $formattedArray[$id]['order'] = $item['order']['id'];
            $formattedArray[$id]['quantity'] = $item['quantity'];
        }
        $cartContent = static::getCartContent();
        foreach ($cartContent as $orderLine) {
            $products = array_column($formattedArray, 'product', 'id');
            if(in_array((int)$orderLine['product'], $products)){
                $data['update'][array_search((int)$orderLine['product'], $products)] = $orderLine;
            } else {
                $data['create'][] = $orderLine;
            }
        }

        if(!empty($data['update'])){
            //get list of items needs to be removed
            $toUpdateKeys = array_keys($data['update']) ?? [];
            $remoteKeys = array_keys($formattedArray) ?? [];
            $data['delete'] = array_diff($remoteKeys, $toUpdateKeys);
        }

        OrderHooks::launchOrderLineSync($data, $connector);
    }

    private static function getCartContent()
    {
        global $woocommerce;

        $cart = $woocommerce->cart;
        $data = [];

        $cartContent = $cart->get_cart_contents();
        foreach ($cartContent as $item) {
            $data[] = [
                'store' => MTO_STORE_ID,
                'product' => get_post_meta($item['data']->get_id(), '_mto_id', true),
                'order' => DraftOrdersSync::getDraftOrderId(),
                'quantity' => $item['quantity'],
            ];
        }
        return $data;
    }
}