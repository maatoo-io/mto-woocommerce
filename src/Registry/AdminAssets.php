<?php


namespace Maatoo\WooCommerce\Registry;

/**
 * Class AdminAssets
 *
 * @package Maatoo\WooCommerce\Registry
 */
class AdminAssets extends AbstractAssets
{
    /**
     * Styles.
     */
    protected function styles()
    {
        wp_enqueue_style(
            $this->handle,
            MTO_PLUGIN_ASSETS . 'css/admin-styles.css',
            [],
            MTO_PLUGIN_VERSION
        );
    }

    /**
     * Scripts.
     */
    protected function scripts()
    {
        wp_enqueue_script(
            $this->handle,
            MTO_PLUGIN_ASSETS . 'js/admin.js',
            ['jquery'],
            false,
            true
        );
    }
}