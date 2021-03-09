<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AbstractAjaxCallback;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

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
        $this->response->setResponseBody('test');
    }
}