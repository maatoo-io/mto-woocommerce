<?php

namespace Maatoo\WooCommerce\Service\Ajax;

class GetContactId extends AbstractAjaxCallback
{
    protected function responseCallback()
    {
        session_start();
        $contactId = $_POST['id'] ?? null;
        if(!$contactId){
            $this->response->setIsError(true);
            $this->response->setResponseBody(__('Contact Id is missing', 'mto-woocommerce'));
            return;
        }
        $_SESSION['mtc_id'] = $contactId;
        $this->response->setResponseBody(__('Success: contact ID received', 'mto-woocommerce'));
    }
}