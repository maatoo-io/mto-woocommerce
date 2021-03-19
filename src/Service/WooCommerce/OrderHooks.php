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
        add_action('before_delete_post', [$this, 'deleteOrder']);
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
        $mtoConnector = self::getConnector();
        $remoteOrders = $mtoConnector->getRemoteList($mtoConnector::getApiEndPoint('order'));
        if(!$remoteOrders){
            LogData::writeApiErrors('Maatoo orders list is not available');
            $remoteOrders = [];
        }
        foreach ($orderIds as $orderId) {
            $order = new MtoOrder($orderId);
            if (!$order) {
                $toDelete[] = $orderId;
                $f = true;
                continue;
            }
            $isExistRemote = array_key_exists($order->getId(),$remoteOrders['orders']);
            if (!$isExistRemote) {
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
                self::getConnector()->updateContact(
                    $contact,
                    [
                        'firstname' => $_POST['billing_first_name'] ?? 'not set',
                        'lastname' =>$_POST['billing_last_name'] ?? 'not set',
                        'email' => $_POST['billing_email'] ?? '',
                        'phone' => $_POST['billing_phone'] ?? '',
                        'tags'=>[MTO_STORE_TAG_ID]
                    ]
                );
            }

            $f = self::isOrderSynced([$orderId]);

            // clear conversion data
            if(!empty($_COOKIE['mto_conversion'])){
                wc_setcookie('mto_conversion', null);
            }

            if (!$f) {
                LogData::writeApiErrors($f);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    public function deleteOrder($orderId){
        global $post;

        if('shop_order' !== $post->post_type){
            return;
        }

        try {
            $order = new MtoOrder($orderId);
            if (!$order || !$order->getId()) {
                return;
            }
            $state = self::getConnector()->sendOrders([$orderId], MtoConnector::getApiEndPoint('order')->delete);

            if (!$state) {
                LogData::writeApiErrors('Order '. $orderId .' doesn\'t removed: ' . $state);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }
}