<?php

namespace Maatoo\WooCommerce\Service\Store;

use Maatoo\WooCommerce\Entity\MtoStore;
use WP_Query;

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
            $name = get_bloginfo('name') ? : 'Untitled Store';
            $shortName = str_replace(
                    '-',
                    '',
                    explode('.', parse_url($storeOption['url'])['host'])[0]
                ) ?? 'untitledstore';
            $externalId = substr(sha1(rand()), 0, 6);
            $currency = get_option('woocommerce_currency') ? : 'USD';
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
    public static function getAllProducts(): array
    {
        return self::getAllItemsByCPT('product');
    }

    /**
     * Get All Orders.
     *
     * @return array|int[]
     */
    public static function getAllOrders(): array
    {
        return self::getAllItemsByCPT('order');
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
        $items = new WP_Query(
            [
                'post_type' => $cpt,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => '_mto_id',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]
        );

        if (!$items->have_posts()) {
            return [];
        }

        return $items->posts;
    }
}