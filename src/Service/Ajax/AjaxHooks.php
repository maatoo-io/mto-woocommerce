<?php

namespace Maatoo\WooCommerce\Service\Ajax;

use Maatoo\WooCommerce\Service\Admin\MtoOrderSync;
use Maatoo\WooCommerce\Service\Admin\MtoProductsSync;
use Maatoo\WooCommerce\Service\Admin\PluginOptions;

class AjaxHooks
{
    public function registryAdminAjax()
    {
        add_action('wp_ajax_mto_save_options', new PluginOptions());
        add_action('wp_ajax_mto_run_product_sync', new MtoProductsSync());
        add_action('wp_ajax_mto_run_order_sync', new MtoOrderSync());
    }
}