<?php
/**
 * Plugin Name: maatoo for WooCommerce
 * Description: Connect your online shop to drive more revenue with intelligent automations, e.g. abanoned cart reminders and more.
 * Version:     1.4.1
 * Author: maatoo.io
 * Author URI: https://maatoo.io
 * License: GPL-3.0+
 * Text Domain: mto-woocommerce
 * Domain Path: /languages
 * Requires PHP: 7.3
 * Requires at least: 4.9
 * Tested up to: 5.7
 * WC requires at least: 3.7.2
 * WC tested up to: 5.1
 */

namespace Maatoo\WooCommerce;

use Maatoo\WooCommerce\Registry\AdminAssets;
use Maatoo\WooCommerce\Service\Ajax\AjaxHooks;
use Maatoo\WooCommerce\Registry\FrontAssets;
use Maatoo\WooCommerce\Registry\Options;
use Maatoo\WooCommerce\Service\Front\MtoConversion;
use Maatoo\WooCommerce\Service\Front\WooHooks;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;
use Maatoo\WooCommerce\Service\WooCommerce\ProductHooks;
use Maatoo\WooCommerce\Service\Admin\PluginUpdate;

defined('ABSPATH') or exit;
include_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die(__('WooCommerce plugin is disabled. WooCommerce plugin needs to be activated to keep using Maatoo.', 'mto-woocommerce'));
}

$composer_path = __DIR__ . '/vendor/autoload.php';
clearstatcache();
if (file_exists($composer_path)) {
    require_once($composer_path);
} else {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die(__('PHP composer files id missing. Can\'t include ' . $composer_path, 'mto-woocommerce'));
}

if (!defined('MTO_PLUGIN_VERSION')) {
    define('MTO_PLUGIN_VERSION', '1.4.0');
}

if (!defined('MTO_PLUGIN_SLUG')) {
    define('MTO_PLUGIN_SLUG', 'mto-woocommerce');
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

if (!defined('MTO_STORE_TAG_ID')) {
      define('MTO_STORE_TAG_ID', get_option('_mto_tag_id') ?: null);
}

if (!defined('MTO_UPDATE_CACHE_EXPIRE')) {
    define('MTO_UPDATE_CACHE_EXPIRE', DAY_IN_SECONDS / 2);
}

if (!defined('MTO_SYNC_INTERVAL')) {
    define('MTO_SYNC_INTERVAL', DAY_IN_SECONDS);
}

add_action('init', new MtoWoocommerce());
register_uninstall_hook(__FILE__, ['\Maatoo\WooCommerce\MtoWoocommerce', 'uninstall']);
register_activation_hook(__FILE__, ['\Maatoo\WooCommerce\MtoWoocommerce', 'activate']);
register_deactivation_hook(__FILE__, ['\Maatoo\WooCommerce\MtoWoocommerce', 'deactivate']);

class MtoWoocommerce
{
    public function __invoke()
    {
        $this->registerAssets();
        $this->registerPluginSettings();
        $this->registerAjaxHooks();
        $this->conversionTracker();
        $this->registerWcHooks();
        $this->translations();
        $this->registerPluginUpdate();
        add_action('mto_sync_clear_log', ['\Maatoo\WooCommerce\Service\LogErrors\LogData', 'clearLogFiles']);
        add_action('mto_sync_products', ['\Maatoo\WooCommerce\Service\Maatoo\MtoSync', 'runProductSync']);
        add_action('mto_sync_orders', ['\Maatoo\WooCommerce\Service\Maatoo\MtoSync', 'runOrderSync']);
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

    private function registerPluginUpdate()
    {
        add_filter( 'plugins_api', ['\Maatoo\WooCommerce\Service\Admin\PluginUpdate', 'info'], 20, 3 );
        add_filter( 'site_transient_update_plugins', ['\Maatoo\WooCommerce\Service\Admin\PluginUpdate', 'update'] );
        add_action( 'upgrader_process_complete', ['\Maatoo\WooCommerce\Service\Admin\PluginUpdate', 'purge'], 10, 2 );
        add_filter( 'plugin_row_meta', ['\Maatoo\WooCommerce\Service\Admin\PluginUpdate', 'details'], 25, 4 );
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
        $orderHooks = new OrderHooks();
        $productHooks = new ProductHooks();
        $frontEndHooks = new WooHooks();
    }

    public static function activate()
    {
        if (!as_next_scheduled_action('mto_sync_clear_log')) {
            as_schedule_recurring_action(time(), MTO_SYNC_INTERVAL, 'mto_sync_clear_log');
        }

        if (!as_next_scheduled_action('mto_sync_products')) {
            as_schedule_recurring_action(time() + 1, MTO_SYNC_INTERVAL, 'mto_sync_products');
        }

        if (!as_next_scheduled_action('mto_sync_orders')) {
            as_schedule_recurring_action(time() + 360, MTO_SYNC_INTERVAL, 'mto_sync_orders');
        }
        $store = MtoStoreManger::getStoreData();
        if($store && method_exists($store, 'getShortName')){
            update_option('_mto_tag_id', $store->getShortName() . '-pending');
        }
    }

    public static function deactivate()
    {
        as_unschedule_all_actions('mto_sync_clear_log');
        as_unschedule_all_actions('mto_sync_products');
        as_unschedule_all_actions('mto_sync_orders');
    }

    public function translations() {
        load_plugin_textdomain( 'mto-woocommerce', false, dirname( plugin_basename(__FILE__)) . '/languages');
    }

    public static function uninstall()
    {
        global $wpdb;

        $wpdb->delete(
            $wpdb->postmeta,
            ['meta_key' => '_mto_last_sync']
        );

        delete_option('_mto_last_sync');
        delete_option('_mto_tag_id');

        as_unschedule_all_actions('mto_sync_clear_log');
        as_unschedule_all_actions('mto_sync_products');
        as_unschedule_all_actions('mto_sync_orders');
    }
}