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
use Maatoo\WooCommerce\Service\Front\MtoConversion;
use Maatoo\WooCommerce\Service\Maatoo\MtoSync;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;
use Maatoo\WooCommerce\Service\WooCommerce\ProductHooks;

use function Sodium\add;

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

if (!defined('MTO_STORE_ID')) {
    $store = MtoStoreManger::getStoreData();

    define('MTO_STORE_ID', $store ? $store->getId() : null);
}

add_action('init', new MtoWoocommerce());

class MtoWoocommerce
{
    public function __invoke()
    {
        $this->registerAssets();
        $this->registerPluginSettings();
        $this->registerAjaxHooks();
        $this->conversionTracker();
        $this->registerWcHooks();
        wp_schedule_single_event(time(), 'mto_sync');

        add_action('mto_sync', [$this, 'mtoHooks']);
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
        if (wp_doing_ajax()) {
            $ajaxCallbacks = new AjaxHooks();
            $ajaxCallbacks->registryAdminAjax();
        }
    }

    private function conversionTracker()
    {
        add_action('wp', new MtoConversion());
    }

    private function registerWcHooks()
    {
        add_action('wp', new OrderHooks());
        $productHooks = new ProductHooks();
    }

    public function mtoHooks()
    {
        new MtoSync();
    }
}