<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoDraftOrder;
use Maatoo\WooCommerce\Entity\MtoOrder;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\DraftOrdersLineSync;

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
        add_action('mto_background_draft_order_sync', [DraftOrdersSync::class, 'runBackgroundSync'], 10, 1);
        add_action('mto_background_draft_orderlines_sync', [DraftOrdersLineSync::class, 'runBackgroundSync'], 10, 1);
        if($mtoUser && $mtoUser->isBirthdayEnabled()){
            add_filter( 'woocommerce_billing_fields', [$this,'addBirthdayField'], 20, 1 );
        }

        if(!is_admin()){
            add_action( 'woocommerce_add_to_cart', new DraftOrdersSync(), 10, 6);
            add_filter( 'woocommerce_update_cart_action_cart_updated', new DraftOrdersLineSync(), 101, 1);
            add_action( 'woocommerce_cart_item_removed', [DraftOrdersLineSync::class, 'removeItemFromCart'], 101, 2);
            add_action( 'template_redirect', [DraftOrdersSync::class, 'wakeupUserSession']);

        }
    }

    public function addBirthdayField($fields){
        $fields['billing_birth_date'] = array(
          'type' => 'date',
          'label' => __('Birthday', 'mto-woocommerce'),
          'class' => array('form-row-wide'),
          'priority' => 25,
          'required' => false,
          'clear' => true,
        );
        return $fields;
    }

    public static function isOrderSynced(array $orderIds, $forceUpdate = false): bool
    {
        if (empty($orderIds) || !self::getConnector()) {
            return true;
        }
        $toUpdate = [];
        $toCreate = [];
        $toDelete = [];
        $f = false;
        $mtoConnector = self::getConnector();
        foreach ($orderIds as $orderId) {
            $order = new MtoOrder($orderId);
            if (!$order) {
                continue;
            }

            if (!$order->getId()) {
                $toCreate[] = $orderId;
                $f = true;
                continue;
            }

            if ($order->isSyncRequired() || $forceUpdate) {
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
            $orderLinesToCreate = MtoStoreManger::getOrdersLines($toCreate);
            self::launchOrderLineSync($orderLinesToCreate, $mtoConnector);
        }

        if (!empty($toUpdate)) {
            $isUpdatedStatus = $mtoConnector->sendOrders($toUpdate, MtoConnector::getApiEndPoint('order')->edit);
            $orderLinesUpdate = MtoStoreManger::getOrdersLines($toUpdate);
            self::launchOrderLineSync($orderLinesUpdate, $mtoConnector);
        }

        if (!empty($toDelete)) {
            $isDelStatus = $mtoConnector->sendOrders($toDelete, MtoConnector::getApiEndPoint('order')->delete);
        }


        if ($isCreatedStatus && $isUpdatedStatus && $isDelStatus) {
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
            wc_setcookie('mto_restore_do_id', null); // clear cookie with draft order
            wc_setcookie('mto_wakeup_session', null); // clear cookie with draft order
            if(!get_post_meta($orderId, '_mto_id', true)) {
                $isSubscribed = (bool)($_POST['mto_email_subscription'] ?? false);
                $contact = $_COOKIE['mtc_id'] ?? null;
                $customerId = DraftOrdersSync::getCustomerID();
                $draftOrder = new MtoDraftOrder($customerId);

                if (!empty($draftOrder->getExternalId())) {
                    update_post_meta($orderId, '_mto_id', $draftOrder->getMtoId() ?: '');
                    $draftOrder->delete(); //remove draft order from the DB
                }
                update_post_meta($orderId, '_mto_is_subscribed', $isSubscribed ? '1' : '0');
                update_post_meta($orderId, '_mto_contact_id', $contact);
                update_post_meta($orderId, '_mto_birthday', $_POST['billing_birth_date'] ?? '');
                // clear conversion data
                if (!empty($_COOKIE['mto_conversion'])) {
                    update_post_meta($orderId, '_mto_conversion', $_COOKIE['mto_conversion']);
                    wc_setcookie('mto_conversion', null);
                }
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
        if ($isSubscribed && !empty($postData['billing_email'])) {
            $contact = get_post_meta($orderId, '_mto_contact_id', true);
            $bd =  get_post_meta($orderId, '_mto_birthday', true);
            $args =  [
                'firstname' => $postData['billing_first_name'] ?? 'not set',
                'lastname' => $postData['billing_last_name'] ?? 'not set',
                'email' => $postData['billing_email'] ?? '',
                'phone' => $postData['billing_phone'] ?? '',
                'tags' => [MTO_STORE_TAG_ID]
            ];
            if(!empty($bd)){
                $args['birthday_date'] = $bd;
            }
            self::getConnector()->updateContact(
                $contact,
                $args
            );
        }

        $f = self::isOrderSynced([$orderId]);

        if (!$f) {
            LogData::writeApiErrors($f);
        }
    }

    public static function launchOrderLineSync($orderLines, MtoConnector $mtoConnector){
        if(!empty($orderLines['create'])){
            $statusOrderLines = $mtoConnector->sendOrderLines(
              $orderLines['create'],
              MtoConnector::getApiEndPoint('orderLine')->batch
            );
        }

        if(!empty($orderLines['update'])){
            $statusOrderLines = $mtoConnector->sendOrderLines(
              $orderLines['update'],
              MtoConnector::getApiEndPoint('orderLine')->edit
            );
        }

        if(!empty($orderLines['delete'])){
            $statusOrderLines = $mtoConnector->sendOrderLines(
              $orderLines['delete'],
              MtoConnector::getApiEndPoint('orderLine')->delete
            );
        }
    }
}