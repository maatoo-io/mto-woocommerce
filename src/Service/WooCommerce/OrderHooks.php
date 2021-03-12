<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoOrderLine;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

class OrderHooks
{
    public function __invoke()
    {
        add_action('woocommerce_thankyou', [$this, 'newOrder']);
    }

    public function newOrder($orderId)
    {
        $orderLines = new MtoOrderLine($orderId);
        $isReadyToSync = ProductHooks::isProductsSynced($orderLines->getItemsIds());

        if(!$isReadyToSync){
            //TODO put message to log
            return;
        }

        $mtoConnector = new MtoConnector(new MtoUser());
        $state = $mtoConnector->sendOrders([$orderId]);
    }
}