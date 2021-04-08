<?php

namespace Maatoo\WooCommerce\Entity;

/**
 * Class MtoOrder
 *
 * @package Maatoo\WooCommerce\Entity
 */
class MtoOrder extends AbstractMtoEntity
{
    private ?int $id = null;
    private string $externalOrderId = '';
    private float $value;
    private string $url;
    private string $status;
    private string $email;
    private string $firstName;
    private string $lastName;
    private ?string $lead = '';
    private ?array $conversion = ['type' => null, 'id' => null];

    public function __construct($orderId)
    {
        parent::__construct($orderId);
        $order = wc_get_order($orderId);
        if (!$order) {
            return null;
        }
        $this->id = get_post_meta($orderId, '_mto_id', true) ?: null;
        $this->externalOrderId = (string)$orderId;
        $this->value = floatval($order->get_total() ?: get_post_meta($orderId, '_order_total', true) ?: 0);
        $this->url = $order->get_view_order_url();
        $this->status = $order->get_status();
        $this->email = (string)$order->get_billing_email() ?: ($_POST['billing_email'] ?? '');
        $this->firstName = (string)$order->get_billing_first_name() ?: ($_POST['billing_first_name'] ?? '');
        $this->lastName = (string)$order->get_billing_last_name() ?: ($_POST['billing_last_name'] ?? '');
        $this->lead = get_post_meta($orderId, '_mto_contact_id', true);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return array|string[]
     */
    public function getConversion()
    {
        $data = $this->getConversionArray();
        if (!empty($data[0]) && !empty($data[1])) {
            $this->conversion = ['type' => $data[0], 'id' => $data[1]];
        }
        return $this->conversion;
    }

    protected function getConversionArray()
    {
        $conversion = get_post_meta($this->getExternalOrderId(), '_mto_conversion', true);
        if(empty($conversion)){
            $conversion = $_COOKIE['mto_conversion'] ?? '';
        }
        if (!empty($conversion)) {
            $src = unserialize(base64_decode($conversion));
            if ($src['source']) {
                return $src['source'];
            }
        }
        return [];
    }

    /**
     * @return string
     */
    public function getExternalOrderId(): string
    {
        return $this->externalOrderId;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        if(!empty($_POST['order_status'])){
            $this->setStatus($_POST['order_status']);
        }

        switch ($this->status) {
            case 'on-hold':
                $mtoPayment = 'open';
                break;
            case 'failed':
                $mtoPayment = 'failed';
                break;
            case 'processing':
                $mtoPayment = 'paid';
                break;
            case 'completed':
                $mtoPayment = 'complete';
                break;
            case 'cancelled':
                $mtoPayment = 'canceled';
                break;
            case 'refunded':
                $mtoPayment = 'refund';
                break;
            case 'pending':
            default:
                $mtoPayment = 'incomplete';
                break;
        }
        return $mtoPayment;
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        if(!$this->value){
            global $woocommerce;
            if(!empty($woocommerce->cart->get_totals()['total'])){
                return $woocommerce->cart->get_totals()['total'];
            }
        }
        return $this->value;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $status
     *
     * @return MtoOrder
     */
    public function setStatus(string $status): MtoOrder
    {
        $this->status = str_replace('wc-', '', $status);
        return $this;
    }

    /**
     * @param array|null[] $conversion
     *
     * @return MtoOrder
     */
    public function setConversion(?array $conversion): MtoOrder
    {
        $this->conversion = $conversion;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getLead()
    {
        $data = $this->getConversionArray();
        if (!empty($data['lead'])) {
            return $data['lead'];
        }

        return $this->lead;
    }

    /**
     * To Array.
     *
     * @return array
     */
    public function toArray()
    {
        $arr = [
            'store' => MTO_STORE_ID,
            'externalOrderId' => $this->getExternalOrderId(),
            'value' => $this->getValue(),
            'url' => $this->getUrl(),
            'status' => $this->getStatus(),
            'email' => $this->getEmail(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'conversion' => $this->getConversion(),
        ];

        if ($this->getLead()) {
            $arr['lead'] = $this->getLead();
        }
        return $arr;
    }

    public function toArrayPatch()
    {
        return [
            'status' => $this->getStatus(),
            'conversion' => $this->getConversion(),
        ];
    }
}