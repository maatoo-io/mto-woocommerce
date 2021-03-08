<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use GuzzleHttp\Client;
use Maatoo\WooCommerce\Entity\MtoStore;
use Maatoo\WooCommerce\Entity\MtoUser;

class MtoConnector
{
    private ?MtoUser $user;
    private Client $client;
    private ?bool $isCredentialsOk;

    public function __construct(MtoUser $user)
    {
        $this->user = $user;
        $this->client = new Client(
            [
                'base_uri' => $this->user->getUrl(),
                'auth' => [
                    $this->user->getUsername(),
                    $this->user->getPassword(),
                ],
            ]
        );
    }

    /**
     * Auth.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isCredentialsOk()
    {
            try {
                $response = $this->client->request(
                    'GET'
                );
                $this->isCredentialsOk = strtoupper($response->getReasonPhrase()) === 'OK';
            } catch (\Exception $ex) {
                $this->isCredentialsOk = false;
            }

        return $this->isCredentialsOk;
    }

    /**
     * Check Configuration.
     */
    public function checkConfiguration()
    {
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
    public function syncProducts(array $products)
    {
        //TODO Push products to remote maatoo store
    }

    /**
     * Sync Orders.
     *
     * @param array $orders
     */
    public function syncOrders(array $orders)
    {
        //TODO Push orders to remote maatoo store
    }
}