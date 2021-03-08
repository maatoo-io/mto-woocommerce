<?php

namespace Maatoo\WooCommerce\Service\Ajax;

/**
 * Class AjaxResponse
 *
 * @package Maatoo\WooCommerce\Service\Ajax
 */
class AjaxResponse
{
    protected ?int $status = 0;
    protected bool $isError = false;
    protected ?string $body = '';

    /**
     * Set Status.
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set Response Body.
     *
     * @param string $html
     *
     * @return $this
     */
    public function setResponseBody(string $html)
    {
        $this->body = $html;
        return $this;
    }

    /**
     * @param bool $isError
     *
     * @return AjaxResponse
     */
    public function setIsError(bool $isError)
    {
        $this->isError = $isError;
        return $this;
    }

    /**
     * Send.
     *
     * @return string
     */
    public function send(): string
    {
        $response = [
            'status' => $this->status,
            'isError' => $this->isError,
            'body' => $this->body,
        ];
        echo json_encode($response);
        wp_die();
    }
}