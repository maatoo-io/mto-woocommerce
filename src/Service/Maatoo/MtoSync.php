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

    public static function runProductSync($start = 0, $limit = 20)
    {
        try {
            $state = self::checkConnections();
            if(!$state || !function_exists('as_schedule_single_action')){
                update_option('_mto_sync_status_product', 'failed');
            }

            $products = MtoStoreManger::getAllProducts(false, $start, $limit);
            $statusProduct = ProductHooks::isProductsSynced($products->have_posts() ? $products->posts : []);
            if(!$statusProduct){
                throw new \Exception('Product Sync failed[offset: ' . $start . '; limit: ' . $limit . ';]');
            }
            $start = $start + $limit;
            if($products->found_posts >= $start && ! as_next_scheduled_action( 'mto_sync_products', [$start, $limit])){
                as_schedule_single_action(time() -1, 'mto_sync_products', [$start, $limit]);
            }
            update_option('_mto_last_sync_products', $statusProduct);
        } catch (\Exception $ex) {
            LogData::writeApiErrors($ex->getMessage());
            return as_schedule_single_action(time() - 1, 'mto_sync_products', [$start, $limit]);
        }
        self::updateLastSyncDate();
    }


    public static function runOrderSync($start = 0, $limit = 20)
    {
        try {
            $state = self::checkConnections();
            if(!$state || !function_exists('as_schedule_single_action')){
                update_option('_mto_sync_status_order', 'failed');
            }
            $orders = MtoStoreManger::getAllOrders(false, $start, $limit);
            $statusOrder = OrderHooks::isOrderSynced($orders->have_posts() ? $orders->posts : []);
            if(!$statusOrder){
                throw new \Exception('Order Sync is failed[offset: ' . $start . '; limit: ' . $limit . ';]');
            }
            LogData::writeDebug('OrderHooks::isOrderSynced completed[offset: ' . $start . '; limit: ' . $limit . ';]' . PHP_EOL);
            $start = $start + $limit;
            if ($orders->found_posts >= $start && ! as_next_scheduled_action( 'mto_sync_orders', [$start, $limit])) {
                as_schedule_single_action(time() - 1, 'mto_sync_orders', [$start, $limit]);
            }
            update_option('_mto_sync_status_order', $statusOrder);
        } catch (\Exception $ex) {
            LogData::writeApiErrors($ex->getMessage());
            return as_schedule_single_action(time() - 1, 'mto_sync_orders', [$start, $limit]);
        }
        self::updateLastSyncDate();
    }

    protected static function updateLastSyncDate()
    {
        update_option('_mto_last_sync', date(DATE_W3C));
    }
}