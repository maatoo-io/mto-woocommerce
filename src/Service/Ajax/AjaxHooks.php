<?php

namespace Maatoo\WooCommerce\Service\Ajax;

use Maatoo\WooCommerce\Service\Admin\PluginOptions;

class AjaxHooks
{
    public function registryAdminAjax()
    {
        add_action('wp_ajax_mto_save_options', new PluginOptions());
    }
}