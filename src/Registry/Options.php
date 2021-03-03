<?php


namespace Maatoo\WooCommerce\Registry;


class Options
{
    public function __invoke()
    {
        $this->registerOptionPage();
    }

    public function registerOptionPage(){
        $icon = MTO_PLUGIN_URL . 'assets/images/maatoo.ico';
        add_menu_page('Maatoo', 'Maatoo', 'manage_options', 'mto', [$this, 'dashboard'], $icon,4);
    }

    public function dashboard(){
        if(!current_user_can('manage_options')){
            include MTO_PLUGIN_TEMPLATES . 'admin/access-denied.php';
            return;
        }
        include MTO_PLUGIN_TEMPLATES . 'admin/dashboard.php';
    }
}