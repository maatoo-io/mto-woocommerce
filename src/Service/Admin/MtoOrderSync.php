<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AbstractAjaxCallback;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

/**
 * Class MtoOrderSync
 *
 * @package Maatoo\WooCommerce\Service\Admin
 */
class MtoOrderSync extends AbstractAjaxCallback
{
    protected function responseCallback()
    {
        $provider = new MtoConnector(new MtoUser());

        $orders = MtoStoreManger::getAllOrders();
        //create orders
        $status = $provider->sendOrders($orders);

        //update order lines

        $status2 = $provider->batchOrderLines(MtoStoreManger::getOrdersLines($orders));
        $this->response->setResponseBody('Sync status: ' . $status);
    }
}