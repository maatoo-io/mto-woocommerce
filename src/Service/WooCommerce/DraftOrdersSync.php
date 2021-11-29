<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoOrderLine;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class DraftOrdersSync
{
    public function __invoke($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $this->syncOrder();
    }

    private function getCustomerID()
    {
        global $woocommerce;

        return $woocommerce->session->get_customer_unique_id();
    }

    /**
     * @return bool
     */
    private function isOrderCreated(): ?bool
    {
        if (empty($_COOKIE['mto_draft_order_id'])) {
            return false;
        }
        return true;
    }

    private function setDraftOrderId($id){
        wc_setcookie('mto_draft_order_id', $id);
    }

    private function getDraftOrderId(){
        return $_COOKIE['mto_draft_order_id'] ?? false;
    }

    private function getCartContent()
    {
        global $woocommerce;

        $cart = $woocommerce->cart;
        $data = [];

        $cartContent = $cart->get_cart_contents();
        foreach ($cartContent as $item) {
            $data[] = [
                'store' => MTO_STORE_ID,
                'product' => get_post_meta($item['data']->get_id(), '_mto_id', true),
                'order' => $this->getDraftOrderId(),
                'quantity' => $item['quantity'],
            ];
        }

        return $data;
    }

    private function getEndpoint(){
        $endpoint = !$this->isOrderCreated()
            ? MtoConnector::getApiEndPoint('order')->create
            : MtoConnector::getApiEndPoint('order')->edit;

        if($this->getDraftOrderId()){
            $endpoint->route = str_replace('{id}', $this->getDraftOrderId(), $endpoint->route);
        }
        return $endpoint;
    }

    private function syncOrderLines(MtoConnector $connector, $orderId){
        $cartContent = $this->getCartContent();
        $orderLinesRemote = $connector->getRemoteList($connector::getApiEndPoint('order'), $orderId)['orderLines'] ?? [];
        $formattedArray = []; // formatted array to contain remote order lines
        foreach ($orderLinesRemote as $id => $item) {
            $formattedArray[$id]['id'] = $id;
            $formattedArray[$id]['store'] = $item['store']['id'];
            $formattedArray[$id]['product'] = $item['product']['id'];
            $formattedArray[$id]['order'] = $item['order']['id'];
            $formattedArray[$id]['quantity'] = $item['quantity'];
        }

        foreach ($cartContent as $orderLine) {
            $products = array_column($formattedArray, 'product', 'id');
            if(in_array((int)$orderLine['product'], $products)){
                $data['update'][array_search((int)$orderLine['product'], $products)] = $orderLine;
            } else {
                $data['create'][] = $orderLine;
            }
        }

        if(!empty($data['update']) && count($data['update']) !== count($formattedArray)){
            //get list of items needs to be removed
            $toUpdateKeys = array_keys($data['update']) ?? [];
            $remoteKeys = array_keys($formattedArray) ?? [];
            $data['delete'] = array_diff($remoteKeys, $toUpdateKeys);
        }

        OrderHooks::launchOrderLineSync($data, $connector);
    }

    private function syncOrder()
    {
        global $woocommerce;
        $store = MtoStoreManger::getStoreData();
        $leadId = $_COOKIE['mtc_id'] ?? '2237';
        $orderRequestData = [
            'store' => $store->getId(),
            'externalOrderId' =>$this->getCustomerID(),
            'externalDateProcessed' => null, //what if order is not processed?
            'externalDateUpdated' => date('Y-m-d H:i:s', strtotime('now')),
            'externalDateCancelled'=> null,
            'value'=>$woocommerce->cart->get_totals()['total'],
            'url' => '', // what if order hasn't been placed yet?
            'status' => 'draft',
            'lead_id' => $leadId
        ];

        $connector = MtoConnector::getInstance(new MtoUser());
        $response = $connector->getResponseData($this->getEndpoint(), $orderRequestData);

        if (!empty($response['order'])) {
            $id = $response['order']['externalOrderId'] ?? null;
            if ($id && !empty($response['order']['id'])) {
              $this->setDraftOrderId($response['order']['id']);
            }

            $this->syncOrderLines($connector, $response['order']['id']);
        }
    }

}