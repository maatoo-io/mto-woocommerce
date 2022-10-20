<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use \Exception;
use \GuzzleHttp\Client;
use \GuzzleHttp\Pool;
use \GuzzleHttp\Exception\RequestException;
use \GuzzleHttp\Promise\PromiseInterface;
use \GuzzleHttp\Psr7\Response;
use Maatoo\WooCommerce\Entity\MtoOrder;
use Maatoo\WooCommerce\Entity\MtoProduct;
use Maatoo\WooCommerce\Entity\MtoProductCategory;
use Maatoo\WooCommerce\Entity\MtoStore;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class MtoConnector
{
    private ?MtoUser $user;
    private Client $client;
    private static ?array $apiEndPionts = null;
    private ?bool $isCredentialsOk = null;

    private static $instance = null;

    public static function getInstance(MtoUser $user)
    {
        if (is_null(self::$instance) && !empty($user->getUrl())) {
            self::$instance = new self($user);
        }

        return self::$instance;
    }

    private function __construct(MtoUser $user)
    {
        $this->user = $user;
        $this->client = new Client(
          [
            'base_uri' => $this->user->getUrl(),
            'auth' => [
              $this->user->getUsername(),
              $this->user->getPassword(),
            ],
            'verify' => false,
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
        global $wpdb;

        if (is_null($this->isCredentialsOk)) {
            $store = MtoStoreManger::getStoreData();

            $lastFullSync = get_option('_mto_last_sync');
            if (function_exists("wp_date")) {
                $lastFullSyncDate =  wp_date('Y-m-d H:i:s', strtotime($lastFullSync));
            } else {
                $lastFullSyncDate =  date('Y-m-d H:i:s', strtotime($lastFullSync));
            }

            $hook = 'mto_sync_orders';
            $crons = get_option('cron');
            $nextFullSync = time();
            foreach ( $crons as $timestamp => $cron ) {
                if(isset( $cron[$hook])) {
                    $nextFullSync = $timestamp;
                    break;
                }
            }
            if (function_exists("wp_date")) {
                $nextFullSyncDate =  wp_date('Y-m-d H:i:s', $nextFullSync);
            } else {
                $nextFullSyncDate =  date('Y-m-d H:i:s', $nextFullSync);
            }

            $payload = [
                "store" => MTO_STORE_ID,
                "shortName" => $store ? $store->getShortName() : null,
                "available_categories" => sizeof(get_terms('product_cat')),
                "available_products" => MtoStoreManger::getAllProducts(false, 0, 0)->found_posts,
                "available_orders" => MtoStoreManger::getAllOrders(false, 0, 0)->found_posts,
                "available_order_lines" => $wpdb->get_row( "SELECT count(*) AS order_lines FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'line_item' " )->order_lines,
                "last_full_sync" => $lastFullSyncDate,
                "next_full_sync" => $nextFullSyncDate,
                "registered_customers" => count_users()['total_users'],
                "plugin_version" => MTO_PLUGIN_VERSION

            ];
            $response = $this->getResponseData(static::getApiEndPoint('healthCheck'), $payload);

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
    public function getResponseData($endpointConfig, $args = []): ?array
    {
        if (empty($endpointConfig)) {
            return [];
        }
        $responseData = null;
        try {
            $response = $this->client->request(
              $endpointConfig->method,
              $endpointConfig->route,
              ['form_params' => $args]
            );
            $responseData = (array)json_decode($response->getBody()->getContents(), 'true');
        } catch (\Exception $exception) {
            LogData::writeApiErrors($exception->getMessage());
        }

        return $responseData;
    }

    public function getResponseDataAsync($endpointConfig, $args = []): ?PromiseInterface
    {
        if (empty($endpointConfig)) {
            return null;
        }
        $responseData = null;
        try {
            $response = $this->client->requestAsync(
                $endpointConfig->method,
                $endpointConfig->route,
                ['form_params' => $args]
            );
            $responseData = $response->then(function ($response) {
                return $response;
            });
            $response->wait();
            return $responseData;
        } catch (\Exception $exception) {
            LogData::writeApiErrors($exception->getMessage());
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
            $this->createTag($response['store']['shortName'] ?? null);

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

            $requests = static function ($products, $endpoint) use ($client)
            {
                foreach ($products as $productId) {
                    $product = new MtoProduct($productId);
                    if (!$product) {
                        continue;
                    }

                    yield function () use ($client, $endpoint, $product)
                    {
                        $route = str_replace('{id}', $product->getId(), $endpoint->route);
                        return $client->requestAsync($endpoint->method, $route, ['form_params' => $product->toArray()]);
                    };
                }
            };

            $pool = new Pool(
              $client, $requests($products, $endpoint), [
                       'concurrency' => 5,
                       'fulfilled' => function (Response $response, $index)
                       {
                           $responseContent = $response->getBody()->getContents();
                           $responseDecoded = json_decode($responseContent, true);
                           if (!empty($responseDecoded['product'])) {
                               $id = $responseDecoded['product']['externalProductId'] ?? null;
                               if ($id && !empty($responseDecoded['product']['id'])) {
                                   update_post_meta((int)$id, '_mto_id', $responseDecoded['product']['id']);
                                   update_post_meta(
                                     (int)$id,
                                     '_mto_last_sync',
                                     $responseDecoded['product']['dateUpdated'] ?? $responseDecoded['product']['dateCreated']
                                   );
                               }
                           }
                       },
                       'rejected' => function (RequestException $reason, $index)
                       {
                           if($reason->getCode() == '404'){
                               $path = $reason->getRequest()->getUri()->getPath();
                               $this->cleanUpOutdateData($path, '/\/api\/products\/(.*)\//U');
                           } else {
                               LogData::writeApiErrors($reason->getMessage());
                           }
                       },
                     ]
            );
            $promise = $pool->promise();
            $promise->wait();
            return $promise->getState();
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    private function cleanUpOutdateData($path, $pattern){
        preg_match($pattern, $path, $matches);
        $mtoId = $matches[1] ?? null;
        LogData::writeApiErrors('404 response detected: data in the database for mto_id: ' . $mtoId . ' has been updated');
        if ($mtoId) {
            $posts = get_posts([
                'meta_query' => [
                    'key' => '_mto_id',
                    'value' => $mtoId,
                ]
            ]);
            if($posts[0]){
                delete_post_meta($posts[0], '_mto_id');
            }
        }
    }

    /**
     * Update Orders on maatoo service.
     *
     * @param array $orders
     *
     * @param $endpoint
     *
     * @return string
     */
    public function sendOrders(array $orders, $endpoint, $startOrderSyncTime = null)
    {
        try {
            $client = $this->client;
            $requests = function ($orders, $endpoint) use ($client)
            {
                foreach ($orders as $orderId) {
                    $order = new MtoOrder($orderId);
                    if (!$order || $order->getValue() === false) {
                        continue;
                    }
                    yield function () use ($client, $endpoint, $order)
                    {
                        $route = str_replace('{id}', $order->getId(), $endpoint->route);
                        if ($endpoint->method === 'PATCH') {
                            $formParam = ['form_params' => $order->toArrayPatch()];
                        } else {
                            $formParam = ['form_params' => $order->toArray()];
                        }
                        return $client->requestAsync($endpoint->method, $route, $formParam);
                    };
                }
            };
            $pool = new Pool(
              $client, $requests($orders, $endpoint), [
                       'concurrency' => 5,
                       'fulfilled' => function (Response $response, $index)
                       {
                           $responseContent = $response->getBody()->getContents();
                           $responseDecoded = json_decode($responseContent, true);
                           if (!empty($responseDecoded['order'])) {
                               $id = $responseDecoded['order']['externalOrderId'] ?? null;
                               if ($id && !empty($responseDecoded['order']['id'])) {
                                   update_post_meta((int)$id, '_mto_id', $responseDecoded['order']['id']);
                                   update_post_meta(
                                     (int)$id,
                                     '_mto_last_sync',
                                     $startOrderSyncTime ?? $responseDecoded['order']['dateUpdated'] ?? $responseDecoded['order']['dateCreated']
                                   );
                               }
                               update_option('mto_order_sync_status', 1);
                           }
                       },
                       'rejected' => function (RequestException $reason, $index)
                       {
                           update_option('mto_order_sync_status', 0);
                           LogData::writeApiErrors($reason->getMessage());
                       },
                     ]
            );
            $promise = $pool->promise();
            $promise->wait();

            return $promise->getState();
        } catch (Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    /**
     * @param array $orderLines
     * @param $endpoint
     * @return string
     */
    public function sendOrderLines(array $orderLines, $endpoint)
    {
        if (empty($orderLines)) {
            return 'Nothing to update';
        }
        $isReplacementRequired = $endpoint->method === 'PATCH' || $endpoint->method === 'DELETE';
        if ($isReplacementRequired) {
            $limit = 1;
        } else {
            $limit = MtoConnector::getApiEndPoint('orderLine')->limit ?? 199;
        }
        try {
            $client = $this->client;
            $requests = function ($orderLines, $endpoint) use ($client, $limit, $isReplacementRequired)
            {
                $length = count($orderLines);
                for ($i = 0; $i < $length; $i += $limit) {
                    if ($limit === 1 && !is_array($orderLines[array_key_first($orderLines)])) {
                        $arrayKeys = array_values($orderLines);
                        //make array format similar to all the rest
                        $orderLinesPart = [$arrayKeys[$i] => []];
                    } else {
                        $orderLinesPart = array_slice($orderLines, $i, $limit, true);
                    }
                    yield function () use ($client, $endpoint, $orderLinesPart, $isReplacementRequired)
                    {
                        $route = false;
                        if ($isReplacementRequired) {
                            $id = array_key_first($orderLinesPart);
                            $route = str_replace('{id}', $id, $endpoint->route);
                            $orderLinesPart = $orderLinesPart[$id];
                        }
                        return $client->requestAsync(
                          $endpoint->method,
                          $route ?: $endpoint->route,
                          ['form_params' => $orderLinesPart]
                        );
                    };
                }
            };

            $pool = new Pool(
              $client, $requests($orderLines, $endpoint), [
                       'concurrency' => 5,
                       'fulfilled' => function (Response $response, $index)
                       {
                           $responseContent = $response->getBody()->getContents();
                           $status = $response->getStatusCode() . "\n";
                       },
                       'rejected' => function (RequestException $reason, $index)
                       {
                           LogData::writeApiErrors($reason->getMessage());
                       },
                     ]
            );
            $promise = $pool->promise();
            $promise->wait();
            return $promise->getState();
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());

        }
    }

    /**
     * @param $contact
     * @param $emailId
     */
    public function saveConversion($contact, $emailId)
    {
        try {
            $endpoint = self::getApiEndPoint('conversion');
            $route = str_replace('{id1}', $emailId, $endpoint->route);
            $route = str_replace('{id2}', $contact, $route);
            $promise = $this->client->requestAsync($endpoint->method, $route);

            $promise->then(
              function (Response $res)
              {
                  $status = $res->getStatusCode() . "\n";
              },
              function (RequestException $e)
              {
                  LogData::writeTechErrors($e->getMessage());
              }
            );
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    /**
     * @param $shortName
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTag($shortName)
    {
        try {
            if (empty($shortName)) {
                return;
            }
            $resp = $this->getResponseData(
              self::getApiEndPoint('tag')->create,
              [
                'tag' => sprintf('%s-pending', $shortName),
              ]
            );
            if (!$resp) {
                LogData::writeApiErrors('Can\'t create tag: ' . $resp);
            } else {
                update_option('_mto_tag_id', $resp['tag']['tag'] ?? null);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    /**
     * @param $contact
     * @param $data
     * @return false
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateContact($contact, $data)
    {
        if (!$contact) {
            return false;
        }
        try {
            $endpoint = self::getApiEndPoint('contact')->update;
            $endpoint->route = str_replace('{id}', $contact, $endpoint->route);
            $resp = $this->getResponseData($endpoint, $data);
            if (!$resp) {
                LogData::writeApiErrors('Can\'t create tag: ' . $resp);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    /**
     * @param $endpoint
     * @param int $orderId - if id is set, it means that orderlines should be retrieved for specific orderId
     * @return array|false|void|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRemoteList($endpoint, int $orderId = 0, $mtoOrderID=null)
    {
        if (!isset($endpoint->list) || ($orderId && !isset($endpoint->retrieveOrderLines))) {
            return false;
        }
        if ($orderId || $mtoOrderID) {
            $mtoId = get_post_meta($orderId, '_mto_id', true);
            if(!$mtoId && is_null($mtoOrderID)){
                return false;
            }
            $mtoId = $mtoOrderID ?: $mtoId;

            $route = str_replace('{id}', $mtoId, $endpoint->retrieveOrderLines->route);
            $endpointNew = (object) [
                'route' => $route,
                'method' => $endpoint->retrieveOrderLines->method
            ];

        } else {
            $endpointNew = $endpoint->list;
        }
        try {
            return $this->getResponseData($endpointNew);
        } catch (\Exception $exception) {
            LogData::writeApiErrors($exception->getMessage());
        }
    }

    public function sendProductCategories(array $categories, $endpoint)
    {
        try {
            $client = $this->client;
            $requests = function ($categories, $endpoint) use ($client)
            {
                foreach ($categories as $categoryId) {
                    $category = new MtoProductCategory($categoryId);
                    if (!$category) {
                        continue;
                    }
                    yield function () use ($client, $endpoint, $category)
                    {
                        $route = str_replace('{id}', $category->getId(), $endpoint->route);
                        return $client->requestAsync($endpoint->method, $route, ['form_params' => $category->toArray()]
                        );
                    };
                }
            };
            $pool = new Pool(
              $client, $requests($categories, $endpoint), [
                       'concurrency' => 5,
                       'fulfilled' => function (Response $response, $index)
                       {
                           $responseDecoded = json_decode($response->getBody()->getContents(), true);
                           if (!empty($responseDecoded['category'])) {
                               $id = $responseDecoded['category']['externalCategoryId'] ?? null;
                               if ($id && !empty($responseDecoded['category']['id'])) {
                                   update_term_meta((int)$id, '_mto_id', $responseDecoded['category']['id']);
                                   update_term_meta(
                                     (int)$id,
                                     '_mto_last_sync',
                                     $responseDecoded['category']['dateUpdated'] ?? $responseDecoded['category']['dateCreated']
                                   );
                               }
                           }
                       },
                       'rejected' => function (RequestException $reason, $index)
                       {
                           LogData::writeApiErrors($reason->getMessage());
                       },
                     ]
            );
            $promise = $pool->promise();
            $promise->wait();
            return $promise->getState();
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }
}
