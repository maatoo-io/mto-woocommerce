<?php
/**
 * Plugin Name: Maatoo
 * Plugin URI:  https://github.com/alinavalovenko/event-calendar
 * Description: Maatoo is a swiss-based SaaS that helps online shops to drive more revenue through targeted marketing
 * messages over email and other channels. Version:     1.0.0 Requires at least: 5.0 Requires PHP:      7.4 Author:
 * Alina Valovenko
 * Text Domain: mto
 * Domain Path: /languages
 */

namespace Maatoo\WooCommerce;

use Maatoo\WooCommerce\Admin\MtoAdminAssets;
use Maatoo\WooCommerce\Front\MtoAssets;

$composer_path = __DIR__ . '/vendor/autoload.php';
clearstatcache();
if (file_exists($composer_path)) {
    require_once($composer_path);
} else {
    exit;
}

add_action('init', new MtoWoocommerce());

class MtoWoocommerce
{
    public function __invoke()
    {
        $this->registerAssets();
    }

    private function registerAssets()
    {
        add_action('wp_enqueue_scripts', new MtoAssets());
        add_action('admin_enqueue_scripts', new MtoAdminAssets());
    }
}