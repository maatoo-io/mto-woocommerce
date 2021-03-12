<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AbstractAjaxCallback;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

/**
 * Class MtoProductsSync
 *
 * @package Maatoo\WooCommerce\Service\Admin
 */
class MtoProductsSync extends AbstractAjaxCallback
{
    protected function responseCallback()
    {
        $provider = new MtoConnector(new MtoUser());
        $status = $provider->sendProducts(MtoStoreManger::getAllProducts(), MtoConnector::getApiEndPoint('product')->create);
        $this->response->setResponseBody('Sync status: ' . $status);
    }
}