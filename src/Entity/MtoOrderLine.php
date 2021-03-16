<?php

namespace Maatoo\WooCommerce\Entity;

class MtoOrderLine
{
    private array $items;

    public function __construct($orderId)
    {
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
            foreach ($orderData as $item){
                $productId = $item['data']->get_id() ?? null;
                if($productId){
                    $productsIds[] = $productId;
                }
            }
            return $productsIds;
        }

        return array_map(function ($item){return $item->get_product_id(); }, $this->items);
    }

    public function toArray()
    {
        if (empty($this->items)) {
            return [];
        }
        $itemLines = [];
        foreach ($this->items as $item) {
            $itemLines[] = [
                'store' => MTO_STORE_ID,
                'product' => $item->get_product_id(),
                'order' => $item->get_order_id(),
                'quantity' => $item->get_quantity(),
            ];
        }
        return $itemLines;
    }
}