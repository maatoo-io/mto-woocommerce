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
    private array $conversion = ['type' => null, 'id' => null]; //TODO replace by real data

    public function __construct($orderId)
    {
        parent::__construct($orderId);
        $order = wc_get_order($orderId);
        if (!$order) {
            return null;
        }
        $this->id = get_post_meta($orderId, '_mto_id', true) ? : null;
        $this->externalOrderId = (string)$orderId;
        $this->value = $order->get_total();
        $this->url = $order->get_view_order_url();
        $this->status = $order->get_status();
        $this->email = $order->get_billing_email() ?? '';
        $this->firstName = $order->get_billing_first_name() ?? '';
        $this->lastName = $order->get_billing_last_name() ?? '';
        if (!empty($_SESSION['mtc_id'])) {
            $this->conversion = [
                'type' => 'email',
                'id' => $_SESSION['mtc_id'],
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
//            case '':
//                $mtoPayment = 'incomplete';
//                break;
//            case '':
//                $mtoPayment = 'awaitingcall';
//                break;
//            case '':
//                $mtoPayment = 'draft';
//                break;
//            case '':
//                $mtoPayment = 'declined';
//                break;
//            case '':
//                $mtoPayment = 'paid';
//                break;
            case 'completed':
                $mtoPayment = 'complete';
                break;
            case 'cancelled':
                $mtoPayment = 'canceled';
                break;
            case 'refunded':
                $mtoPayment = 'refund';
                break;
            case 'on-hold':
            case 'processing':
            case 'pending':
            default:
                $mtoPayment = 'open';
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
}