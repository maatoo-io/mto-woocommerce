<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AjaxResponse;
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
        $this->mtoOptions = get_option('mto') ? : ['username' => null, 'password' => null, 'url' => null];
    }

    public function __invoke()
    {
        try {
            if (empty($_POST['username']) || empty($_POST['pass']) || empty($_POST['url'])) {
                $this->response->setResponseBody(__('All fields should be filled in', 'mto-woocommerce'))
                               ->setIsError(true)
                               ->send();
            }

            $mtoUser = MtoUser::toMtoUser(
                trim($_POST['username']),
                trim($_POST['pass']),
                filter_var(rtrim($_POST['url'], '/'), FILTER_SANITIZE_URL),
                $_POST['birthday'] == "true"
            );

            $provider = MtoConnector::getInstance($mtoUser);

            if ($provider && $provider->healthCheck()) {
                $msg[] = __('Credentials are valid and saved', 'mto-woocommerce');
                if ($this->mtoOptions['username'] !== $mtoUser->getUsername(
                    ) || $this->mtoOptions['password'] !== $mtoUser->getPassword(
                    ) || $this->mtoOptions['url'] !== $mtoUser->getUrl()
                     || (bool)$this->mtoOptions['birthday'] !== $mtoUser->isBirthdayEnabled()){
                    $this->mtoOptions['username'] = $mtoUser->getUsername();
                    $this->mtoOptions['password'] = $mtoUser->getPassword();
                    $this->mtoOptions['url'] = $mtoUser->getUrl();
                    $this->mtoOptions['store'] = null;
                    $this->mtoOptions['birthday'] = (int)$mtoUser->isBirthdayEnabled();
                    update_option('mto', $this->mtoOptions);
                    //register store if not exist and get status message
                }
                $msg[] = $this->registerStore($provider);
                $this->response->setResponseBody(implode('. ', $msg));
            } else {
                $this->response->setResponseBody(__('Credentials are invalid', 'mto-woocommerce'))
                               ->setIsError(true);
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
        $msg = __('The store exists on maatoo', 'mto-woocommerce');

        //create store if not exist
        $store = MtoStoreManger::getStoreData();
        if (is_null($store->getId())) {
            $msg = __('Can\'t create Store on Maatoo', 'mto-woocommerce');
            $store = $provider->registerStore($store);
            if (!$store) {
                $this->response->setIsError(true);
            } else {
                $msg = __('Connection with Maatoo is set up. Full sync will be run in 30 seconds', 'mto-woocommerce');
                $this->mtoOptions['store'] = $store->toArray();
                $this->mtoOptions['store']['id'] = $store->getId();
                update_option('mto', $this->mtoOptions);
                update_option('_mto_last_sync', null);
                //run full sync in 30 seconds
                wp_schedule_single_event(time() - 1, 'mto_sync_clear_log');
                wp_schedule_single_event(time() + 30, 'mto_sync_products');
                wp_schedule_single_event(time() + 180, 'mto_sync_orders');
            }
        }
        return $msg;
    }
}