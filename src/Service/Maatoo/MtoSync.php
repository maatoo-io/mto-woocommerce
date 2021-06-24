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

    public static function runProductSync()
    {
        try {
            $state = self::checkConnections();
            if(!$state){
                update_option('_mto_sync_status_product', 'failed');
            }
            $products = MtoStoreManger::getAllProducts(false);
            $statusProduct = ProductHooks::isProductsSynced($products);
            update_option('_mto_last_sync_products', $statusProduct);
        } catch (\Exception $ex) {
            LogData::writeApiErrors($ex->getMessage());
        }
        self::updateLastSyncDate();
    }


    public static function runOrderSync()
    {
        try {
            $state = self::checkConnections();
            if(!$state){
                update_option('_mto_sync_status_order', 'failed');
            }
            $orders = MtoStoreManger::getAllOrders(false);
            $statusOrder = OrderHooks::isOrderSynced($orders);
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