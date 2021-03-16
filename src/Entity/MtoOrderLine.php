<?php

namespace Maatoo\WooCommerce\Entity;

class MtoOrderLine
{
    private array $items;
    private ?int $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $wcOrder = wc_get_order($orderId);
        if ($wcOrder) {
            $this->items = $wcOrder->get_items();
        } else {
            $this->items = [];
        }
    }

    /**
     * @return array|\WC_Order_Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get Items Ids.
     *
     * @return array|int[]
     */
    public function getItemsIds()
    {
        if (empty($this->items)) {
            global $woocommerce;
            $productsIds = [];
            $orderData = $woocommerce->cart->get_cart_contents();
            foreach ($orderData as $item) {
                $productId = $item['data']->get_id() ?? null;
                if ($productId) {
                    $productsIds[] = $productId;
                }
            }
            return $productsIds;
        }

        return array_map(
            function ($item) {
                return $item->get_product_id();
            },
            $this->items
        );
    }

    public function toArray()
    {
        if (!$this->orderId) {
            return [];
        }
        if($this->getItems()){
           return $this->toArrayOnUpdate();
        }
        global $woocommerce;

        $cartContent = $woocommerce->cart->get_cart_contents();
        if(empty($cartContent)){
            return [];
        }
        $orderLines = [];
        foreach ($cartContent as $item) {
            $orderLines[] = [
                'store' => MTO_STORE_ID,
                'product' => get_post_meta($item['data']->get_id(), '_mto_id', true),
                'order' => get_post_meta($this->orderId, '_mto_id', true),
                'quantity' => $item['quantity'],
            ];
        }
        return $orderLines;
    }

    public function toArrayOnUpdate()
    {
        if (empty($this->getItems())) {
            return [];
        }
        $itemLines = [];
        foreach ($this->getItems() as $item) {
            $itemLines[] = [
                'store' => MTO_STORE_ID,
                'product' => get_post_meta($item->get_product_id(), '_mto_id', true),
                'order' => get_post_meta($item->get_order_id(), '_mto_id', true),
                'quantity' => $item->get_quantity(),
            ];
        }
        return $itemLines;
    }
}