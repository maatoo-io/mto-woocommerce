<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use Exception;
use GuzzleHttp\Client;
use Maatoo\WooCommerce\Entity\MtoStore;
use Maatoo\WooCommerce\Entity\MtoUser;

class MtoConnector
{
    private ?MtoUser $user;
    private Client $client;
    private static ?array $apiEndPionts = null;
    private ?bool $isCredentialsOk = null;

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
     * Get Api End Point.
     *
     * @param $option
     *
     * @return array|null
     */
    protected static function getApiEndPoint($option)
    {
        if (is_null(static::$apiEndPionts)) {
            clearstatcache();
            $file = MTO_PLUGIN_DIR . 'api-config.json';
            if (!file_exists($file)) {
                return null;
            }
            $config = file_get_contents(MTO_PLUGIN_DIR . 'api-config.json');

            if (empty($config)) {
                return null;
            }

            static::$apiEndPionts = (array)json_decode($config);
        }

        return array_key_exists($option, static::$apiEndPionts) ? static::$apiEndPionts[$option] : null;
    }

    /**
     * Health Check.
     *
     * @return bool|null
     */
    public function healthCheck()
    {
        if (is_null($this->isCredentialsOk)) {
            $response = $this->getResponseData(static::getApiEndPoint('healthCheck'));

            if (isset($response['status'])) {
                $this->isCredentialsOk = strtoupper($response['status']) === 'OK';
            } else {
                $this->isCredentialsOk = false;
            }
        }

        return $this->isCredentialsOk;
    }

    /**
     * Get Response Data.
     *
     * @param $endpointConfig
     *
     * @return array|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getResponseData($endpointConfig, $args = []): ?array
    {
        if (empty($endpointConfig)) {
            return [];
        }
        try {
            $response = $this->client->request(
                $endpointConfig->method,
                $endpointConfig->route,
                ['form_params' => $args]
            );
            $responseData = (array)json_decode($response->getBody()->getContents());
        } catch (Exception $exception) {
            //TODO Add to log
            return null;
        }

        return $responseData;
    }

    /**
     * Register Store.
     *
     * @param MtoStore $store
     */
    public function registerStore(MtoStore $store)
    {
        $formData = $store->toArray();
        $endpoint = static::getApiEndPoint('store')->create ?? null;
        $response = $this->getResponseData($endpoint, $formData);

        if (!empty($response['store'])) {
            return $store->setId($response['store']->id ?? null);
        }

        return false;
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