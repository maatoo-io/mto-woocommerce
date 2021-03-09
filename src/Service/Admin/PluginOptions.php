<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AjaxResponse;
use Maatoo\WooCommerce\Service\Maatoo\API\Auth;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

/**
 * Class PluginOptions
 *
 * @package Maatoo\WooCommerce\Service\Admin
 */
class PluginOptions
{
    private AjaxResponse $response;
    private array $mtoOptions = [];

    public function __construct()
    {
        $this->response = new AjaxResponse();
    }

    public function __invoke()
    {
        try {
            if (empty($_POST['username']) || empty($_POST['pass']) || empty($_POST['url'])) {
                $this->response->setResponseBody(__('All fields should be filled in', 'mto'))
                               ->setIsError(true)
                               ->send();
            }
            $mtoUser = MtoUser::toMtoUser(
                trim($_POST['username']),
                trim($_POST['pass']),
                filter_var(rtrim($_POST['url'], '/'), FILTER_SANITIZE_URL)
            );

            $provider = new MtoConnector($mtoUser);

            if ($provider->healthCheck()) {
                $this->mtoOptions =
                    [
                        'username' => $mtoUser->getUsername(),
                        'password' => $mtoUser->getPassword(),
                        'url' => $mtoUser->getUrl(),
                    ];
                $msg[] = __('Credentials are valid and saved', 'mto');
                update_option('mto', $this->mtoOptions);
                //register store if not exist and get status message
                $msg[] = $this->registerStore($provider);
                $this->response->setResponseBody(implode('. ', $msg))
                               ->send();
            } else {
                $this->response->setResponseBody(__('Credentials are invalid', 'mto'))
                               ->setIsError(true)
                               ->send();
            }

            $this->response->send();
        } catch (\Exception $ex) {
            $this->response->setResponseBody($ex->getMessage())
                           ->setIsError(true)
                           ->send();
        }
    }

    private function registerStore(MtoConnector $provider)
    {
        //create store if not exist
        $msg = __('Can\'t create Store on Maatoo', 'mto');
        $store = MtoStoreManger::getStoreData();
        if (is_null($store->getId())) {
            $store = $provider->registerStore($store);
            if (!$store) {
                $this->response->setIsError(true);
            } else {
                $msg = __('Store on Maatoo created successful', 'mto');
                $this->mtoOptions['store'] = $store->toArray();
                update_option('mto', $this->mtoOptions);
            }
        }
        return $msg;
    }
}