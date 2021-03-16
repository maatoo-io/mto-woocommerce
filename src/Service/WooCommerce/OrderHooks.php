<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoOrder;
use Maatoo\WooCommerce\Entity\MtoOrderLine;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class OrderHooks
{
    /**
     * Connector.
     *
     * @var MtoConnector|null
     */
    private static ?MtoConnector $connector = null;

    /**
     * Get Connector.
     *
     * @return MtoConnector|null
     */
    protected static function getConnector()
    {
        if (is_null(self::$connector)) {
            self::$connector = MtoConnector::getInstance(new MtoUser());
        }

        return self::$connector;
    }

    public function __construct()
    {
        add_action('woocommerce_thankyou', [$this, 'orderPublished']);
        add_action('save_post_shop_order', [$this, 'saveOrder']);
    }

    public function orderPublished($orderId)
    {
        if (!self::getConnector()) {
            return;
        }
        $orderLines = new MtoOrderLine($orderId);
        $isReadyToSync = ProductHooks::isProductsSynced($orderLines->getItemsIds());

        if (!$isReadyToSync) {
            //TODO put message to log
            return;
        }

        $mtoConnector = self::getConnector();
        $status = $mtoConnector->batchOrderLines($orderLines->toArray());
    }

    public static function isOrderSynced(array $orderIds): bool
    {
        if (empty($orderIds) || !self::getConnector()) {
            return true;
        }
        $toUpdate = [];
        $toCreate = [];
        $toDelete = [];
        $f = false;

        foreach ($orderIds as $orderId) {
            $order = new MtoOrder($orderId);
            if (!$order) {
                $toDelete[] = $orderId;
                $f = true;
                continue;
            }

            if (!$order->getLastSyncDate()) {
                $toCreate[] = $orderId;
                $f = true;
                continue;
            }

            if ($order->isSyncRequired()) {
                $toUpdate[] = $orderId;
                $f = true;
                continue;
            }
        }

        if (!$f) {
            return true;
        }

        $mtoConnector = self::getConnector();
        $isCreatedStatus = $isUpdatedStatus = $isDelStatus = $statusOrderLines = true;
        if (!empty($toCreate)) {
            $isCreatedStatus = $mtoConnector->sendOrders($toCreate, MtoConnector::getApiEndPoint('order')->create);
        }

        if (!empty($toUpdate)) {
            $isUpdatedStatus = $mtoConnector->sendOrders($toUpdate, MtoConnector::getApiEndPoint('order')->edit);
        }

        if (!empty($toUpdate)) {
            $isDelStatus = $mtoConnector->sendOrders($toDelete, MtoConnector::getApiEndPoint('order')->delete);
        }

        $orderLines = MtoStoreManger::getOrdersLines($orderIds);
        $statusOrderLines = $mtoConnector->batchOrderLines($orderLines);

        if ($isCreatedStatus && $isUpdatedStatus && $isDelStatus && $statusOrderLines) {
            return true;
        }

        return false;
    }

    public function saveOrder($orderId)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || get_post_status($orderId) === 'trash' || is_null(
                self::getConnector()
            )) {
            return;
        }

        $isSubscribed = (bool)$_POST['mto_email_subscription'] ?? false;
        $contact = $_COOKIE['mtc_id'];

        if($isSubscribed){
            self::getConnector()->saveSubscription($contact, $orderId);
        }

        $f = self::isOrderSynced([$orderId]);

        if(!$f){
            //TODO put data to log
        }
    }

}