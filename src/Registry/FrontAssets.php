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
            MTO_PLUGIN_ASSETS . 'css/styles.min.css',
        );
    }

    /**
     * Register Scripts for Front End.
     */
    protected function scripts()
    {
        wp_enqueue_script(
            $this->handleLibs,
            MTO_PLUGIN_ASSETS . 'js/libs.css',
            [],
            false,
            true
        );

        wp_enqueue_script(
            $this->handle,
            MTO_PLUGIN_ASSETS . 'js/scripts.css',
            [$this->handleLibs],
            false,
            true
        );
    }
}