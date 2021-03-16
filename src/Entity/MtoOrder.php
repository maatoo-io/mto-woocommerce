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
    private ?array $conversion = ['type' => null, 'id' => null]; //TODO replace by real data

    public function __construct($orderId)
    {
        parent::__construct($orderId);
        $order = wc_get_order($orderId);
        if (!$order) {
            return null;
        }
        global $woocommerce;
        $this->id = get_post_meta($orderId, '_mto_id', true) ? : null;
        $this->externalOrderId = (string)$orderId;
        $this->value = floatval($order->get_total() ?: ($woocommerce->cart->get_totals()['total'] ?? 0));
        $this->url = $order->get_view_order_url();
        $this->status = $order->get_status();
        $this->email = $order->get_billing_email() ?: ($_POST['billing_email'] ?? '');
        $this->firstName = $order->get_billing_first_name() ?: ($_POST['billing_first_name'] ?? '');
        $this->lastName = $order->get_billing_last_name() ?: ($_POST['billing_last_name'] ?? '');
        if (!empty($_COOKIE['mtc_id'])) {
            $this->conversion = [
                'type' => 'email',
                'id' => $_COOKIE['mtc_id'],
            ];
        }
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
        if (!empty($_COOKIE['mto_conversion'])) {
            $src = unserialize(base64_decode($_COOKIE['mto_conversion']));
            if ($src['source']) {
                return $src['source'];
            }
        }
        return $this->conversion;
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
        $this->status = $status;
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
     * To Array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
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
    }

    public function toArrayPatch()
    {
        return [
            'status' => $this->getStatus(),
            'conversion' => $this->getConversion(),
        ];
    }
}