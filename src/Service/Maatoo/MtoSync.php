<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;
use Maatoo\WooCommerce\Service\WooCommerce\ProductHooks;

/**
 * Class MtoSync
 *
 * @package Maatoo\WooCommerce\Service\Maatoo
 */
class MtoSync
{
    protected static function checkConnections()
    {
        try {
            $store = MtoStoreManger::getStoreData();

            if (!$store || !$store->getId()) {
                return false;
            }

            $connector = MtoConnector::getInstance(new MtoUser());

            if (!$connector->healthCheck()) {
                return false;
            }
        } catch (\Exception $ex) {
            LogData::writeTechErrors($ex->getMessage());
        }
        return true;
    }

    public static function runProductSync($start = 0, $limit = 30)
    {
        try {
            $state = self::checkConnections();
            if(!$state){
                update_option('_mto_sync_status_product', 'failed');
            }

            $products = MtoStoreManger::getAllProducts(false, $start, $limit);
            $statusProduct = ProductHooks::isProductsSynced($products->have_posts() ? $products->posts : []);
            $start = $start + $limit;
            if($products->found_posts > ($start + 1) && ! wp_next_scheduled( 'mto_sync_products', [$start, $limit])){
                wp_schedule_single_event(time() -1, 'mto_sync_products', [$start, $limit]);
            }
            update_option('_mto_last_sync_products', $statusProduct);
        } catch (\Exception $ex) {
            LogData::writeApiErrors($ex->getMessage());
        }
        self::updateLastSyncDate();
    }


    public static function runOrderSync($start = 0, $limit = 20)
    {
        try {
            $state = self::checkConnections();
            if(!$state){
                update_option('_mto_sync_status_order', 'failed');
            }
            $orders = MtoStoreManger::getAllOrders(false, $start, $limit);
            LogData::writeDebug('OrderHooks::isOrderSynced started. Orders found: ' . $orders->found_posts . PHP_EOL);
            $statusOrder = OrderHooks::isOrderSynced($orders->have_posts() ? $orders->posts : []);
            LogData::writeDebug('OrderHooks::isOrderSynced completed[offset: ' . $start . '; limit: ' . $limit . ';]' . PHP_EOL);
            $start = $start + $limit;
            if ($orders->found_posts >= $start && ! wp_next_scheduled( 'mto_sync_orders', [$start, $limit])) {
                wp_schedule_single_event(time() -1, 'mto_sync_orders', [$start, $limit]);
            }
            update_option('_mto_sync_status_order', $statusOrder);
        } catch (\Exception $ex) {
            LogData::writeApiErrors($ex->getMessage());
        }
        self::updateLastSyncDate();
    }

    protected static function updateLastSyncDate()
    {
        update_option('_mto_last_sync', date(DATE_W3C));
    }
}