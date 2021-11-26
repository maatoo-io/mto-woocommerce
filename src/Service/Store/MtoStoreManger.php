<?php

namespace Maatoo\WooCommerce\Service\Store;

use Maatoo\WooCommerce\Entity\MtoOrderLine;
use Maatoo\WooCommerce\Entity\MtoStore;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\WooCommerce\ProductHooks;
use Simplon\Url\Url;

/**
 * Class MtoStoreManger
 *
 * @package Maatoo\WooCommerce\Service\Store
 */
class MtoStoreManger
{
    /**
     * Store object.
     *
     * @var MtoStore|null
     */
    private static ?MtoStore $store = null;

    /**
     * Create.
     *
     * @return MtoStore|null
     */
    public static function getStoreData(): ?MtoStore
    {
        if (!is_null(static::$store)) {
            return static::$store;
        }
        $storeOption = get_option('mto');

        if (empty($storeOption)) {
            return null;
        }
        if (empty($storeOption['store'])) {
            $name = get_bloginfo('name') ?: 'Untitled Store';
            $url = new Url(get_home_url());
            $shortName = str_replace(
                    '-',
                    '',
                    $url->getDomain());
            $externalId = substr(sha1(rand()), 0, 6);
            $currency = get_option('woocommerce_currency') ?: 'USD';
            $domain = get_home_url();
            $id = null;
        } else {
            $name = $storeOption['store']['name'] ?? null;
            $shortName = $storeOption['store']['shortName'] ?? null;
            $externalId = $storeOption['store']['externalStoreId'] ?? null;
            $currency = $storeOption['store']['currency'] ?? null;
            $domain = $storeOption['store']['domain'] ?? null;
            $id = $storeOption['store']['id'] ?? null;
        }

        return MtoStore::toMtoStore($name, $shortName, $currency, $externalId, $domain, $id);
    }

    /**
     * Get All Products.
     *
     * @return \WP_Query
     */
    public static function getAllProducts($newOnly = true, $start = 0, $limit = 50)
    {
        return self::getAllItemsByCPT('product', $newOnly, $start, $limit);
    }

    /**
     * Get All Orders.
     *
     * @return \WP_Query
     */
    public static function getAllOrders($newOnly = true, $start = 0, $limit = 50)
    {
        return self::getAllItemsByCPT('shop_order', $newOnly, $start, $limit);
    }

    /**
     * Get All Items By CPT.
     *
     * @param string $cpt
     * @param bool $newOnly
     * @param int $start
     * @param int $limit
     * @return \WP_Query
     */
    private static function getAllItemsByCPT(string $cpt, $newOnly = true, $start = 0, $limit = 50)
    {
        $args = [
            'post_type' => $cpt,
            'posts_per_page' => $limit,
            'offset' => $start,
            'fields' => 'ids',
            'post_status' => 'publish',
            'orderby' => ['date'=>'DESC']
        ];

        if($cpt !== 'product'){
            $args['post_status']='any';
        }

        if ($newOnly) {
            $args['meta_query'] = [
                [
                    'key' => '_mto_id',
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        return new \WP_Query($args);
    }

    public static function getOrdersLines($items = null)
    {

        $mtoConnector = MtoConnector::getInstance(new MtoUser());
        if (is_array($items)) {
            $items = array_unique($items);
            $data = [];
            foreach ($items as $orderId) {
                $orderLinesRemote = $mtoConnector->getRemoteList($mtoConnector::getApiEndPoint('order'), $orderId)['orderLines'] ?? [];
                $formattedArray = []; // formatted array to contain remote order lines
                foreach ($orderLinesRemote as $id => $item) {
                    $formattedArray[$id]['id'] = $id;
                    $formattedArray[$id]['store'] = $item['store']['id'];
                    $formattedArray[$id]['product'] = $item['product']['id'];
                    $formattedArray[$id]['order'] = $item['order']['id'];
                    $formattedArray[$id]['quantity'] = $item['quantity'];
                }


                $orderLines = new MtoOrderLine($orderId);
                self::isOrderLinesProductsSynced($orderLines);
                if (!$orderLines) {
                    continue;
                }

                foreach ($orderLines->toArray() as $orderLine) {
                    $products = array_column($formattedArray, 'product', 'id');
                    if(in_array((int)$orderLine['product'], $products)){
                        $data['update'][array_search((int)$orderLine['product'], $products)] = $orderLine;
                    } else {
                        $data['create'][] = $orderLine;
                    }
                }

                if(!empty($data['update']) && count($data['update']) !== count($formattedArray)){
                    //get list of items needs to be removed
                    $toUpdateKeys = array_keys($data['update']) ?? [];
                    $remoteKeys = array_keys($formattedArray) ?? [];
                    $data['delete'] = array_diff($remoteKeys, $toUpdateKeys);
                }
            }

            return $data;
        }
        return [];
    }

    /**
     * Create products if they are missed in maatoo, but present at order
     * @param MtoOrderLine $orderLine
     */
    public static function isOrderLinesProductsSynced(MtoOrderLine $orderLine){
        $toCreate = [];
        foreach ($orderLine->getItemsIds() as $item){
            $mtoId = get_post_meta($item, '_mto_id', true);
            if(!$mtoId){
                $toCreate[] = $item;
            }
        }

        if($toCreate){
            ProductHooks::isProductsSynced($toCreate);
        }
    }
}