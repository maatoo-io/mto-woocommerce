<?php

namespace Maatoo\WooCommerce\Service\Store;

use Maatoo\WooCommerce\Entity\MtoOrderLine;
use Maatoo\WooCommerce\Entity\MtoStore;

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
            $shortName = str_replace(
                    '-',
                    '',
                    explode('.', parse_url(get_home_url())['host'])[0]
                ) ?? 'untitledstore';
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
     * @return array|int[]
     */
    public static function getAllProducts($newOnly = true): array
    {
        return self::getAllItemsByCPT('product', $newOnly);
    }

    /**
     * Get All Orders.
     *
     * @return array|int[]
     */
    public static function getAllOrders($newOnly = true): array
    {
        return self::getAllItemsByCPT('shop_order', $newOnly);
    }

    /**
     * Get All Items By CPT.
     *
     * @param string $cpt
     *
     * @return array|int[]
     */
    private static function getAllItemsByCPT(string $cpt, $newOnly = true)
    {
        $args = [
            'post_type' => $cpt,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
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
        $items = new \WP_Query($args);

        if (!$items->have_posts()) {
            return [];
        }

        return $items->posts;
    }

    public static function getOrdersLines($items = null)
    {
        if (is_numeric($items)) {
            $orderLines = new MtoOrderLine($items);
            if ($orderLines) {
                return $orderLines->toArray();
            }
            return [];
        } elseif (is_array($items)) {
            $data = [];
            foreach ($items as $orderId) {
                $orderLines = new MtoOrderLine($orderId);
                if (!$orderLines) {
                    continue;
                }
                foreach ($orderLines->toArray() as $orderLine) {
                    $data[] = $orderLine;
                }
            }
            return $data;
        }
        return [];
    }
}