<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Maatoo\WooCommerce\Entity\MtoOrder;
use Maatoo\WooCommerce\Entity\MtoProduct;
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
    public static function getApiEndPoint($option)
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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
            $responseData = (array)json_decode($response->getBody()->getContents(), 'true');
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
     *
     * @return false|MtoStore
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function registerStore(MtoStore $store)
    {
        if ($store->getId()) {
            return $store;
        }

        $storeId = $this->isStoreRegistered($store);

        if ($storeId) {
            return $store->setId($storeId);
        }

        $formData = $store->toArray();
        $endpoint = static::getApiEndPoint('store')->create ?? null;
        $response = $this->getResponseData($endpoint, $formData);

        if (!empty($response['store'])) {
            return $store->setId($response['store']['id'] ?? null);
        }

        return false;
    }

    /**
     * Return store is if Store was registered for domain
     *
     * @param MtoStore $store
     *
     * @return false|mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function isStoreRegistered(MtoStore $store)
    {
        $endpoint = static::getApiEndPoint('store')->list ?? null;
        $response = $this->getResponseData($endpoint);

        if (empty($response['stores'])) {
            return false;
        }
        $keys = array_keys($response['stores']);
        $relKey = array_search($store->getDomain(), array_column($response['stores'], 'domain'));
        if ($relKey !== false) {
            return $keys[$relKey];
        }

        return false;
    }

    /**
     * Create Products on maatoo service.
     *
     * @param array $products
     *
     * @return string with state
     */
    public function sendProducts(array $products, $endpoint)
    {
        try {
            $client = $this->client;
            $requests = function ($products, $endpoint) use ($client) {
                foreach ($products as $productId) {
                    $product = new MtoProduct($productId);
                    if (!$product) {
                        continue;
                    }
                    yield function () use ($client, $endpoint, $product) {
                        $route = str_replace('{id}', $product->getId(), $endpoint->route);
                        return $client->requestAsync($endpoint->method, $route, ['form_params' => $product->toArray()]);
                    };
                }
            };
            $pool = new Pool(
                $client, $requests($products, $endpoint), [
                'concurrency' => 5,
                'fulfilled' => function (Response $response, $index) {
                    $responseDecoded = json_decode($response->getBody()->getContents(), true);
                    if (!empty($responseDecoded['product'])) {
                        $id = $responseDecoded['product']['externalProductId'] ?? null;
                        if ($id && !empty($responseDecoded['product']['id'])) {
                            update_post_meta((int)$id, '_mto_id', $responseDecoded['product']['id']);
                            update_post_meta((int)$id, '_mto_last_sync', $responseDecoded['product']['dateCreated']);
                        }
                    }
                },
                'rejected' => function (RequestException $reason, $index) {
                    //TODO Put message into log
                },
            ]
            );
            $promise = $pool->promise();
            $promise->wait();
            return $promise->getState();
        } catch (Exception $exception) {
            //TODO Put message into log
            return $exception->getMessage();
        }
    }

    /**
     * Create Orders.
     *
     * @param array $orders
     *
     * @return string
     */
    public function sendOrders(array $orders)
    {
        try {
            $endpoint = static::getApiEndPoint('order')->create ?? null;
            $client = $this->client;
            $requests = function ($orders, $endpoint) use ($client) {
                foreach ($orders as $orderId) {
                    $order = new MtoOrder($orderId);
                    if (!$order) {
                        continue;
                    }
                    yield function () use ($client, $endpoint, $order) {
                        $route = str_replace('{id}', $order->getId(), $endpoint->route);
                        return $client->requestAsync($endpoint->method, $route, ['form_params' => $order->toArray()]);
                    };
                }
            };
            $pool = new Pool(
                $client, $requests($orders, $endpoint), [
                'concurrency' => 5,
                'fulfilled' => function (Response $response, $index) {
                    $responseDecoded = json_decode($response->getBody()->getContents(), true);
                    if (!empty($responseDecoded['order'])) {
                        $id = $responseDecoded['order']['externalOrderId'] ?? null;
                        if ($id && !empty($responseDecoded['order']['id'])) {
                            update_post_meta((int)$id, '_mto_id', $responseDecoded['order']['id']);
                            update_post_meta((int)$id, '_mto_last_sync', $responseDecoded['order']['dateCreated']);
                        }
                    }
                },
                'rejected' => function (RequestException $reason, $index) {
                    //TODO Put message into log
                    $msg = $reason->getMessage();
                },
            ]
            );
            $promise = $pool->promise();
            $promise->wait();

            return $promise->getState();
        } catch (Exception $exception) {
            //TODO Put message into log
        }
    }

    public function batchOrderLines(array $orderLines)
    {
        try {
            $endpoint = static::getApiEndPoint('orderLine')->batch ?? null;
            return $this->getResponseData($endpoint, $orderLines);
        } catch (Exception $exception) {
            //TODO Put message into log
        }
    }
}