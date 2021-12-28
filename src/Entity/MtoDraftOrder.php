<?php

namespace Maatoo\WooCommerce\Entity;

use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;
use Maatoo\WooCommerce\Service\WooCommerce\DraftOrdersLineSync;

/**
 * Class explain and proceed actions related to draft orders
 */
class MtoDraftOrder
{
    private ?int $id; //session id
    private int $storeId;
    private string $externalId = ''; // identify record in local database. For maatoo local ID is named "External"
    private ?int $mtoId; // id in maatoo database
    private int $mtoLeadId; // customer id in maatoo database
    private array $cart; // array of items data which are in the cart
    private float $cartValue; // cart total value
    private ?string $dateCreated = '';
    private ?string $dateModified = '';

    public function __construct(string $sessionKey = '')
    {
        $data = $this->getBySessionKey($sessionKey);
        if (!$data) {
            return null;
        }
        $this->id = $data['id'];
        $this->storeId = $data['mto_store'];
        $this->externalId = $data['mto_session_key'];
        $this->mtoId = $data['mto_id'];
        $this->mtoLeadId = $data['mto_lead_id'];
        $this->cart = unserialize($data['mto_cart']);
        $this->cartValue = $data['mto_cart_value'];
        $this->dateCreated = $data['date_created'];
        $this->dateModified = $data['date_modified'];
        return $this;
    }

    /**
     * @return int|mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * save draft order data in the database
     */
    public function save()
    {
        if ($this->id) {
            $this->update();
            return;
        }
        global $wpdb;
        $data = $this->getAsArray();
        $result = $wpdb->insert($wpdb->prefix . 'mto_draft_orders', $data);
        if (!$result) {
            LogData::writeDebug('Can\'t insert draft order: ' . implode('; ', $data));
        }
        $this->id = $wpdb->insert_id;
    }

    /**
     * delete record from the datavase
     */
    public function delete()
    {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'mto_draft_orders', ['id' => $this->id]);
    }

    /**
     * update draft order's data in the database
     */
    public function update()
    {
        global $wpdb;
        $data = $this->getAsArray();
        $result = $wpdb->update($wpdb->prefix . 'mto_draft_orders', $data, ['id' => $this->id]);
        if ($wpdb->last_error) {
            LogData::writeDebug('Can\'t update draft order: ' . $this->id . '; Message' . $wpdb->last_error);
        }
    }

    /*
     * get draft order data from the db in array format
     */
    private function getBySessionKey(string $sessionKey)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mto_draft_orders';
        $sql = "SELECT * FROM $table WHERE mto_session_key = '$sessionKey'";
        $data = $wpdb->get_row($sql, ARRAY_A);
        if (!$data) {
            return null;
        }
        return $data;
    }

    /**
     * Creates an instance of MtoDraftOrder if such exists
     * @param string $id
     * @return MtoDraftOrder|null
     */
    public static function getById(string $id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mto_draft_orders';
        $sql = "SELECT * FROM $table WHERE id = '$id'";
        $data = $wpdb->get_row($sql, ARRAY_A);
        if (!$data) {
            return null;
        }
        $instance = new self();
        return $instance->setId($data['id'])
            ->setExternalId($data['mto_session_key'])
            ->setMtoId($data['mto_id'])
            ->setStoreId($data['mto_store'])
            ->setMtoLeadId($data['mto_lead_id'])
            ->setCart(unserialize($data['mto_cart']))
            ->setCartValue($data['mto_cart_value'])
            ->setDateCreated($data['date_created'])
            ->setDateModified($data['date_modified']);
    }

    /**
     * @return mixed|string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * convert object to array
     * @return array
     */
    private function getAsArray(): array
    {
        return [
            'mto_id' => (string)$this->mtoId ?: null,
            'mto_store' => (string)$this->storeId,
            'mto_session_key' => (string)$this->externalId,
            'mto_lead_id' => (string)$this->mtoLeadId,
            'mto_cart' => serialize($this->cart),
            'mto_cart_value' => $this->cartValue,
            'date_created' => property_exists($this, 'dateCreated') && !empty($this->dateCreated) ? $this->dateCreated : date('Y-m-d H:i:s', strtotime('now')),
            'date_modified' => $this->dateModified
        ];
    }

    /**
     * @param string $externalId
     * @return MtoDraftOrder
     */
    public function setExternalId(string $externalId): MtoDraftOrder
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * @param int|null $mtoId
     * @return $this
     */
    public function setMtoId(?int $mtoId): MtoDraftOrder
    {
        $this->mtoId = $mtoId;
        return $this;
    }

    /**
     * @param array $cart
     * @return MtoDraftOrder
     */
    public function setCart(array $cart): MtoDraftOrder
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @param string $dateCreated
     * @return MtoDraftOrder
     */
    public function setDateCreated(string $dateCreated): MtoDraftOrder
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @param string $dateModified
     * @return MtoDraftOrder
     */
    public function setDateModified(string $dateModified): MtoDraftOrder
    {
        $this->dateModified = $dateModified;
        return $this;
    }

    /**
     * @param int $mtoLeadId
     * @return MtoDraftOrder
     */
    public function setMtoLeadId(int $mtoLeadId): MtoDraftOrder
    {
        $this->mtoLeadId = $mtoLeadId;
        return $this;
    }

    /**
     * @param float $cartValue
     * @return MtoDraftOrder
     */
    public function setCartValue(float $cartValue): MtoDraftOrder
    {
        $this->cartValue = $cartValue;
        return $this;
    }

    /**
     * @param int $storeId
     * @return MtoDraftOrder
     */
    public function setStoreId(int $storeId): MtoDraftOrder
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @param ?int $id
     * @return MtoDraftOrder
     */
    public function setId(?int $id): MtoDraftOrder
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Send order data to maatoo
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sync()
    {
        $endpoint = !$this->mtoId
            ? MtoConnector::getApiEndPoint('order')->create
            : MtoConnector::getApiEndPoint('order')->edit;

        if ($this->mtoId) {
            $endpoint->route = str_replace('{id}', $this->mtoId, $endpoint->route);
        }
        $orderRequestData = [
            'store' => $this->storeId,
            'externalOrderId' => $this->externalId,
            'externalDateProcessed' => null, //what if order is not processed?
            'externalDateUpdated' => date('Y-m-d H:i:s', strtotime('now')),
            'externalDateCancelled' => null,
            'value' => $this->cartValue,
            'url' => $this->getOrderUrl(), // what if order hasn't been placed yet?
            'status' => 'draft',
            'lead_id' => $this->mtoLeadId
        ];

        $connector = MtoConnector::getInstance(new MtoUser());
        $response = $connector->getResponseData($endpoint, $orderRequestData);
        if (!empty($response['order'])) {
            $id = $response['order']['externalOrderId'] ?? null;
            if ($id && !empty($response['order']['id'])) {
                $this->mtoId = $response['order']['id'];
                $this->update();
                //DraftOrdersLineSync::runBackgroundSync($this);
                wp_schedule_single_event(time()+ 10, 'mto_background_draft_orderlines_sync', [$this]); // run in 10 seconds
            }
        }
    }

    /**
     * Send orders data to maatoo
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function syncOrderLines(){
        $connector = MtoConnector::getInstance(new MtoUser());
        $orderLinesRemote = $connector->getRemoteList($connector::getApiEndPoint('order'), 0, $this->mtoId)['orderLines'] ?? [];
        $formattedArray = []; // formatted array to contain remote order lines
        foreach ($orderLinesRemote as $id => $item) {
            $formattedArray[$id]['id'] = $id;
            $formattedArray[$id]['store'] = $item['store']['id'];
            $formattedArray[$id]['product'] = $item['product']['id'];
            $formattedArray[$id]['order'] = $item['order']['id'];
            $formattedArray[$id]['quantity'] = $item['quantity'];
        }
        $cartContent = $this->getOrderLines();
        foreach ($cartContent as $orderLine) {
            $products = array_column($formattedArray, 'product', 'id');
            if(in_array((int)$orderLine['product'], $products)){
                $data['update'][array_search((int)$orderLine['product'], $products)] = $orderLine;
            } else {
                $data['create'][] = $orderLine;
            }
        }

        if(!empty($data['update'])){
            //get list of items needs to be removed
            $toUpdateKeys = array_keys($data['update']) ?? [];
            $remoteKeys = array_keys($formattedArray) ?? [];
            $data['delete'] = array_diff($remoteKeys, $toUpdateKeys);
        }

        OrderHooks::launchOrderLineSync($data, $connector);

    }

    /**
     * Convert cart data to array with order lines to be sent to maatoo
     * @return array
     */
    private function getOrderLines(){
        $data = [];
        foreach ($this->cart as $item) {
            $data[] = [
                'store' => $item['store'],
                'product' => $item['mto_product_id'],
                'order' => $this->mtoId,
                'quantity' => $item['quantity'],
            ];
        }
        return $data;
    }

    /**
     * create a link which add items to cart automatically and be the draft order url
     * @return string
     */
    private function getOrderUrl()
    {
        return sprintf(
            '%s?mto=%s',
            wc_get_cart_url(),
            base64_encode(
                serialize([
                    'draftOrderId' => $this->id,
                    'sessionKey' => $this->externalId
                    ])
            )
        );
    }

    /**
     * @return array
     */
    public function getCart(): array
    {
        return (array)$this->cart;
    }

    /**
     * @return int|mixed|null
     */
    public function getMtoId()
    {
        return $this->mtoId;
    }

    /**
     * Convert data to MtoDraftOrder object
     *
     * @param $storeId
     * @param $sessionKey
     * @param $leadId
     * @param $cart
     * @param $cartValue
     * @param $dateModified
     * @param null $id
     * @param null $mtoId
     * @return MtoDraftOrder
     */
    public static function toMtoDraftOrder(
        $storeId,
        $sessionKey,
        $leadId,
        $cart,
        $cartValue,
        $dateModified,
        $id = null,
        $mtoId = null)
    {
        $instance = new MtoDraftOrder();
        return $instance->setMtoId($mtoId)
            ->setId($id)
            ->setStoreId($storeId)
            ->setExternalId($sessionKey)
            ->setMtoLeadId($leadId)
            ->setCart($cart)
            ->setCartValue($cartValue)
            ->setDateModified($dateModified);
    }
}