<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoOrder;
use Maatoo\WooCommerce\Entity\MtoOrderLine;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use mysql_xdevapi\Exception;

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
        add_action('save_post_shop_order', [$this, 'saveOrder']);
    }

    public static function isOrderSynced(array $orderIds): bool
    {
        if (empty($orderIds) || !self::getConnector()) {
            return true;
        }
        $toUpdate = [];
        $toCreate = [];
        $toDelete = [];
        $orderLines = [];
        $f = false;

        foreach ($orderIds as $orderId) {
            $order = new MtoOrder($orderId);
            if (!$order) {
                $toDelete[] = $orderId;
                $f = true;
                continue;
            } elseif (!$order->getLastSyncDate()) {
                $toCreate[] = $orderId;
                $f = true;
                continue;
            } elseif ($order->isSyncRequired()) {
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

        if (!empty($toDelete)) {
            $isDelStatus = $mtoConnector->sendOrders($toDelete, MtoConnector::getApiEndPoint('order')->delete);
        }


        $orderLines = MtoStoreManger::getOrdersLines($orderIds);
        $statusOrderLines = $mtoConnector->sendOrderLines(
            $orderLines,
            MtoConnector::getApiEndPoint('orderLine')->batch
        );

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
        try {
            $isSubscribed = (bool)$_POST['mto_email_subscription'] ?? false;
            $contact = $_COOKIE['mtc_id'];

            if ($isSubscribed && !empty($_POST['billing_email'])) {
                self::getConnector()->createSubscriptionEvent($contact);
                self::getConnector()->updateContact(
                    $contact,
                    [
                        'firstname' => $_POST['billing_first_name'] ?? 'not set',
                        'lastname' =>$_POST['billing_last_name'] ?? 'not set',
                        'email' => $_POST['billing_email'],
                    ]
                );
            }

            $f = self::isOrderSynced([$orderId]);

            if (!$f) {
                LogData::writeApiErrors($f);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

}