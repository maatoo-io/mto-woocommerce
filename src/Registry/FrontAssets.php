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
        clearstatcache();
        if(!file_exists(MTO_PLUGIN_ASSETS . 'js/libs.js')){
            wp_enqueue_script(
              $this->handleLibs,
              MTO_PLUGIN_ASSETS . 'js/libs.js',
              ['jquery'],
              false,
              true
            );
            $this->handleLibs = null;
        }

        wp_enqueue_script(
            $this->handle,
            MTO_PLUGIN_ASSETS . 'js/scripts.js',
            [$this->handleLibs ?: 'jquery'],
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