<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use Maatoo\WooCommerce\Entity\MtoStore;
use Maatoo\WooCommerce\Entity\MtoUser;

class MtoConnector
{
    private ?MtoUser $user;
    private ?string $contactId;

    public function __construct(MtoUser $user)
    {
        $this->user = $user;
    }

    /**
     * Auth.
     */
    private function auth(){
        //TODO Check credentials
    }

    /**
     * Check Configuration.
     */
    public function checkConfiguration(){
        //TODO check configuration and API endpoints
    }

    /**
     * Register Store.
     *
     * @param MtoStore $store
     */
    public function registerStore(MtoStore $store)
    {
        //TODO Register New Store
    }

    /**
     * Sync Products.
     *
     * @param array $products
     */
    public function syncProducts(array $products){
        //TODO Push products to remote maatoo store
    }

    /**
     * Sync Orders.
     *
     * @param array $orders
     */
    public function syncOrders(array $orders){
        //TODO Push orders to remote maatoo store
    }
}