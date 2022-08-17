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
        $icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgdmlld0JveD0iMCAwIDI1NiAyNTYiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0xMjggMEM1Ny4xNTcyIDAgMCA1Ny4xNTcyIDAgMTI4QzAgMTk4Ljg0MyA1Ny4xNTcyIDI1NiAxMjggMjU2QzE5OC44NDMgMjU2IDI1NiAxOTguODQzIDI1NiAxMjhDMjU2IDU3LjE1NzIgMTk4Ljg0MyAwIDEyOCAwWk04MC41MDMxIDIwOC41MDNINTkuNTcyM1YxNjYuNjQySDgwLjUwMzFWMjA4LjUwM1pNMTE3LjUzNSAyMDguNTAzSDk2LjYwMzhWMTI1LjU4NUgxMTcuNTM1VjIwOC41MDNaTTE1NC41NjYgMjA4LjUwM0gxMzMuNjM1VjgzLjcyMzNIMTU0LjU2NlYyMDguNTAzWk0xNzUuNDk3IDgzLjcyMzNWNjIuNzkyNUgxNTQuNTY2VjQxLjg2MTZIMTk2LjQyOFY2Mi43OTI1VjYxLjE4MjRWNjQuNDAyNVY4My43MjMzSDE3NS40OTdaIiBmaWxsPSIjMDBDMURCIi8+Cjwvc3ZnPgo=';
        add_menu_page('maatoo', 'maatoo', 'manage_options', 'mto', [$this, 'dashboard'], $icon, 59);
    }

    public function dashboard()
    {
        if (!current_user_can('manage_options')) {
            include MTO_PLUGIN_TEMPLATES . 'admin/access-denied.php';
            return;
        }
        $lastFullSync = get_option('_mto_last_sync');

        $hook = 'mto_sync_orders';
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
        $event = $event[$key] ?? null;

        if (function_exists("wp_date")) {
            $nextEvent =  wp_date('m/d/Y H:i:s', $startTimestamp); 
        } else {
            $nextEvent =  date('m/d/Y H:i:s', $startTimestamp); 
        }

        $imageSizesList = $this->get_all_image_sizes_list();
        include MTO_PLUGIN_TEMPLATES . 'admin/dashboard.php';
    }


    /**
     * Get all the registered image sizes along with their dimensions
     *
     * @global array $_wp_additional_image_sizes
     *
     * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
     *
     * @return array $image_sizes The image sizes
     */
    function get_all_image_sizes() {
        global $_wp_additional_image_sizes;
        $image_sizes = array();
        $default_image_sizes = get_intermediate_image_sizes();
        foreach ($default_image_sizes as $size) {
            $image_sizes[$size]['width'] = intval( get_option("{$size}_size_w"));
            $image_sizes[$size]['height'] = intval( get_option("{$size}_size_h"));
            $image_sizes[$size]['crop'] = get_option("{$size}_crop") ? get_option("{$size}_crop") : false;
        }
        if (isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes)) {
            $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
        }
        return $image_sizes;
    }

    /**
     * @return array
     */
    function get_all_image_sizes_list() {
        $response = array();
        foreach ($this->get_all_image_sizes() as $key => $data) {
            $label = ucwords(str_replace('_', ' ', $key));
            $label = __($label);
            $response[$key] = "{$label} ({$data['width']} x {$data['height']})";
        }
        return $response;
    }
}