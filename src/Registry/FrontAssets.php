<?php

namespace Maatoo\WooCommerce\Registry;

/**
 * Class FrontAssets
 *
 * @package Maatoo\WooCommerce\Registry
 */
class FrontAssets extends AbstractAssets
{
    /**
     * Register Styles for Front End.
     */
    protected function styles()
    {
        wp_enqueue_style(
            $this->handle,
            MTO_PLUGIN_ASSETS . 'css/styles.css',
        );
    }

    /**
     * Register Scripts for Front End.
     */
    protected function scripts()
    {
        wp_enqueue_script(
            $this->handleLibs,
            MTO_PLUGIN_ASSETS . 'js/libs.js',
            [],
            false,
            true
        );

        wp_enqueue_script(
            $this->handle,
            MTO_PLUGIN_ASSETS . 'js/scripts.js',
            [$this->handleLibs],
            false,
            true
        );

        wp_localize_script(
            $this->handle,
            'mto',
            [
                "ajaxUrl" => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('_mtoAjax_nonceHash'),
            ]
        );
    }
}