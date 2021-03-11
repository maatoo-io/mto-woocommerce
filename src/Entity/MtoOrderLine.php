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


    public function toArray()
    {
        if (empty($this->items)) {
            return [];
        }
        $itemLines = [];
        foreach ($this->items as $item) {
            $itemLines[] = [
                'store' => MTO_STORE_ID,
                'product' => $item->get_id(),
                'order' => $item->get_order_id(),
                'quantity' => $item->get_quantity(),
            ];
        }
        return $itemLines;
    }
}