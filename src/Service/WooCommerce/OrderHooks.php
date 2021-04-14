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
        $mtoUser = new MtoUser();
        add_action('save_post_shop_order', [$this, 'saveOrder']);
        add_action('before_delete_post', [$this, 'deleteOrder']);
        add_action('mto_background_order_sync', [$this, 'singleOrderSync'], 10, 2);
        if($mtoUser && $mtoUser->isBirthdayEnabled()){
            add_filter( 'woocommerce_billing_fields', [$this,'addBirthdayField'], 20, 1 );
        }
    }

    public function addBirthdayField($fields){
        $fields['billing_birth_date'] = array(
          'type' => 'date',
          'label' => __('Birth date'),
          'class' => array('form-row-wide'),
          'priority' => 25,
          'required' => false,
          'clear' => true,
        );
        return $fields;
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
        if (!$remoteOrders) {
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
            $isExistRemote = array_key_exists($order->getId(), $remoteOrders['orders']);
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
            $orderLines = MtoStoreManger::getOrdersLines($orderIds);
            $statusOrderLines = $mtoConnector->sendOrderLines(
                $orderLines,
                MtoConnector::getApiEndPoint('orderLine')->batch
            );
        }

        if (!empty($toUpdate)) {
            $isUpdatedStatus = $mtoConnector->sendOrders($toUpdate, MtoConnector::getApiEndPoint('order')->edit);
        }

        if (!empty($toDelete)) {
            $isDelStatus = $mtoConnector->sendOrders($toDelete, MtoConnector::getApiEndPoint('order')->delete);
        }


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
            $contact = $_COOKIE['mtc_id'] ?? null;
            update_post_meta($orderId, '_mto_is_subscribed', $isSubscribed ? '1' : '0');
            update_post_meta($orderId, '_mto_contact_id', $contact);
            // clear conversion data
            if (!empty($_COOKIE['mto_conversion'])) {
                update_post_meta($orderId, '_mto_conversion', $contact);
                wc_setcookie('mto_conversion', null);
            }
            wp_schedule_single_event(time() - 1, 'mto_background_order_sync', [$orderId, $_POST]);
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    public function deleteOrder($orderId)
    {
        if ('shop_order' !== get_post_type($orderId)) {
            return;
        }

        try {
            $state = self::getConnector()->sendOrders([$orderId], MtoConnector::getApiEndPoint('order')->delete);

            if (!$state) {
                LogData::writeApiErrors('Order ' . $orderId . ' doesn\'t removed: ' . $state);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    public function singleOrderSync($orderId, $postData)
    {
        update_post_meta($orderId, 'test_sync', time());
        $isSubscribed = (bool)get_post_meta($orderId, '_mto_is_subscribed', true);
        $contact = get_post_meta($orderId, '_mto_contact_id', true);

        if ($isSubscribed && !empty($postData['billing_email'])) {
            self::getConnector()->updateContact(
                $contact,
                [
                    'firstname' => $postData['billing_first_name'] ?? 'not set',
                    'lastname' => $postData['billing_last_name'] ?? 'not set',
                    'email' => $postData['billing_email'] ?? '',
                    'phone' => $postData['billing_phone'] ?? '',
                    'tags' => [MTO_STORE_TAG_ID],
                    'fields' => ['all' => ['birthday_date'=>$postData['billing_birth_date'] ?? '']]
                ]
            );
        }

        $f = self::isOrderSynced([$orderId]);

        if (!$f) {
            LogData::writeApiErrors($f);
        }
    }
}