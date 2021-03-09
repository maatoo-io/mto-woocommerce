<?php

namespace Maatoo\WooCommerce\Service\Store;

use Maatoo\WooCommerce\Entity\MtoStore;

class MtoStoreManger
{
    /**
     * Create.
     *
     * @return MtoStore|null
     */
    public static function getStoreData(): ?MtoStore
    {
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
            $currency = get_woocommerce_currency();
            $domain = get_home_url();
            $id = null;
        } else {
            $name = $storeOption['store']['name'] ?? null;
            $shortName = $storeOption['store']['shortName'] ?? null;
            $externalId = $storeOption['store']['externalStoreId'] ?? null;
            $currency = $storeOption['store']['currency'] ?? null;
            $domain = $storeOption['store']['domain'] ?? null;
            $id = null;
        }

        return MtoStore::toMtoStore($name, $shortName, $currency, $externalId, $domain, $id);
    }

    public static function getAllProducts(): array
    {

        return [];
    }
}