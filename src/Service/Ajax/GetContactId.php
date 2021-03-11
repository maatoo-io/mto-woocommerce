<?php

namespace Maatoo\WooCommerce\Service\Ajax;

class GetContactId extends AbstractAjaxCallback
{
    protected function responseCallback()
    {
        $contactId = $_POST['id'] ?? null;
        if(!$contactId){
            $this->response->setIsError(true);
            $this->response->setResponseBody(__('Contact Id is missing', 'mto'));
            return;
        }
        if(!get_option('mto_contact_id')){
            update_option('mto_contact_id', filter_var($contactId, FILTER_SANITIZE_NUMBER_INT));
        }
        $this->response->setResponseBody(__('Success: contact ID received', 'mto'));
    }
}