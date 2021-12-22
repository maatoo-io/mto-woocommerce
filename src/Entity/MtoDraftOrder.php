<?php

namespace Maatoo\WooCommerce\Entity;

use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\WooCommerce\DraftOrdersLineSync;
use Maatoo\WooCommerce\Service\WooCommerce\DraftOrdersSync;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;

class MtoDraftOrder
{
    private ?int $id;
    private int $storeId;
    private string $externalId = '';
    private ?int $mtoId;
    private int $mtoLeadId;
    private array $cart;
    private float $cartValue;
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
        $this->cart = json_decode($data['mto_cart']);
        $this->cartValue = $data['mto_cart_value'];
        $this->dateCreated = $data['date_created'];
        $this->dateModified = $data['date_modified'];
        return $this;
    }

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
    }

    public function delete()
    {
        global $wpdb;
        $wpdb->delete($this->table, ['id' => $this->id]);
    }

    public function update()
    {
        global $wpdb;
        $data = $this->getAsArray();
        $result = $wpdb->update($wpdb->prefix . 'mto_draft_orders', $data, ['id' => $this->id]);
        if (!$result) {
            LogData::writeDebug('Can\'t update draft order: ' . implode('; ', $data));
        }
    }

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
     * @return mixed|string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }
    private function getAsArray(): array
    {
        return [
            'mto_id' => $this->mtoId ?: null,
            'mto_store' => $this->storeId,
            'mto_session_key' => $this->externalId,
            'mto_lead_id' => $this->mtoLeadId,
            'mto_cart' => json_encode($this->cart),
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
                DraftOrdersLineSync::runBackgroundSync($this);
                //wp_schedule_single_event(time(), 'mto_background_draft_order_sync', [$this]);
            }
        }
    }

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