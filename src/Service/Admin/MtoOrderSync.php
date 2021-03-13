<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Automattic\WooCommerce\Admin\Overrides\Order;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AbstractAjaxCallback;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;

/**
 * Class MtoOrderSync
 *
 * @package Maatoo\WooCommerce\Service\Admin
 */
class MtoOrderSync extends AbstractAjaxCallback
{
    protected function responseCallback()
    {
        $provider = MtoConnector::getInstance(new MtoUser());

        $orders = MtoStoreManger::getAllOrders();
        //create orders
        $status = OrderHooks::isOrderSynced($orders);

        //update order lines

        $status2 = $provider->batchOrderLines(MtoStoreManger::getOrdersLines($orders));
        $this->response->setResponseBody('Sync status: ' . $status);
    }
}