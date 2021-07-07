<?php


namespace Maatoo\WooCommerce\Service\Ajax;


abstract class AbstractAjaxCallback
{
    protected AjaxResponse $response;

    public function __construct()
    {
        $this->response = new AjaxResponse();
    }

    public function __invoke()
    {
        $this->responseCallback();
        $this->response->send();
    }

    abstract protected function responseCallback();
}