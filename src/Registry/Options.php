<?php

namespace Maatoo\WooCommerce\Registry;

class Options
{
    public function __invoke()
    {
        $this->registerOptionPage();
    }

    public function registerOptionPage()
    {
        $icon = MTO_PLUGIN_URL . 'assets/images/maatoo.ico';
        add_menu_page('Maatoo', 'Maatoo', 'manage_options', 'mto', [$this, 'dashboard'], $icon, 4);
    }

    public function dashboard()
    {
        if (!current_user_can('manage_options')) {
            include MTO_PLUGIN_TEMPLATES . 'admin/access-denied.php';
            return;
        }

        $lastFullSync = get_option('_mto_last_sync');

        $hook = 'mto_sync';
        $crons = get_option('cron');
        $event = [];
        $startTimestamp = time();
        foreach ( $crons as $timestamp => $cron ) {
             if(isset( $cron[$hook])) {
                 $event = $cron[$hook];
                 $startTimestamp = $timestamp;
                 break;
             }
        }
        $key = array_keys($event)[0] ?? false;
        $event = $event[$key];
        $nextEvent = date('m/d/Y H:i:s', $lastFullSync ? $lastFullSync + $event['interval'] : $startTimestamp);
        include MTO_PLUGIN_TEMPLATES . 'admin/dashboard.php';
    }
}