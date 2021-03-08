<?php
/***
 * Plugin Name: Maatoo
 * Plugin URI:  https://github.com/maatoo-io/mto-woocommerce/
 * Description: Maatoo is a swiss-based SaaS that helps online shops to drive more revenue through targeted marketing
 * messages over email and other channels. Version:     1.0.0 Requires at least: 5.0 Requires PHP: 7.4 Author: Alina
 * Valovenko Text Domain: mto Domain Path: /languages
 */

namespace Maatoo\WooCommerce;

use Maatoo\WooCommerce\Registry\AdminAssets;
use Maatoo\WooCommerce\Service\Ajax\AjaxHooks;
use Maatoo\WooCommerce\Registry\FrontAssets;
use Maatoo\WooCommerce\Registry\Options;

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    die('WooCommerce plugin is disabled. WooCommerce plugin needs to be activated to keep using Maatoo.');
}

$composer_path = __DIR__ . '/vendor/autoload.php';
clearstatcache();
if (file_exists($composer_path)) {
    require_once($composer_path);
} else {
    exit;
}


if (!defined('MTO_PLUGIN_DIR')) {
    define('MTO_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('MTO_PLUGIN_URL')) {
    define('MTO_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('MTO_PLUGIN_TEMPLATES')) {
    define('MTO_PLUGIN_TEMPLATES', MTO_PLUGIN_DIR . 'template/');
}

if (!defined('MTO_PLUGIN_ASSETS')) {
    define('MTO_PLUGIN_ASSETS', MTO_PLUGIN_URL . 'assets/dist/');
}

add_action('init', new MtoWoocommerce());

class MtoWoocommerce
{
    public function __invoke()
    {
        $this->registerAssets();
        $this->registerPluginSettings();
        $this->registerAjaxHooks();
    }

    private function registerAssets()
    {
        add_action('wp_enqueue_scripts', new FrontAssets());
        add_action('admin_enqueue_scripts', new AdminAssets());
    }

    private function registerPluginSettings()
    {
        add_action('admin_menu', new Options());
    }

    private function registerAjaxHooks()
    {
        $ajaxCallbacks = new AjaxHooks();
        $ajaxCallbacks->registryAdminAjax();
    }
}