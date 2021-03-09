<?php

namespace Maatoo\WooCommerce\Service\Admin;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Ajax\AjaxResponse;
use Maatoo\WooCommerce\Service\Maatoo\API\Auth;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

/**
 * Class PluginOptions
 *
 * @package Maatoo\WooCommerce\Service\Admin
 */
class PluginOptions
{
    private $response;

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
            $mtoUser = MtoUser::toMtoUser($_POST['username'], $_POST['pass'], $_POST['url']);
            $provider = new MtoConnector($mtoUser);
            if ($provider->healthCheck()) {
                update_option(
                    'mto',
                    [
                        'username' => $mtoUser->getUsername(),
                        'password' => $mtoUser->getPassword(),
                        'url' => $mtoUser->getUrl(),
                    ]
                );
                $this->response->setResponseBody(__('Credentials Saved', 'mto'))
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
}