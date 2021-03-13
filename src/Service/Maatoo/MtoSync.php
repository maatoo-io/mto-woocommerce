<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;
use Maatoo\WooCommerce\Service\WooCommerce\ProductHooks;

/**
 * Class MtoSync
 *
 * @package Maatoo\WooCommerce\Service\Maatoo
 */
class MtoSync
{
    public function __construct()
    {
        $this->runProductSync();
        $this->runOrderSync();
    }

    protected function runProductSync()
    {
        $products = MtoStoreManger::getAllProducts(false);
        $statusProduct = ProductHooks::isProductsSynced($products);
    }


    protected function runOrderSync()
    {
        $orders = MtoStoreManger::getAllOrders(false);
        $statusOrder = OrderHooks::isOrderSynced($orders);
    }
}