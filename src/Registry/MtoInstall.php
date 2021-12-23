<?php

namespace Maatoo\WooCommerce\Registry;

use Maatoo\WooCommerce\Service\Store\MtoStoreManger;

class MtoInstall
{
    private static function createDraftOrderTable()
    {
        global $wpdb;

        $collate = '';

        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }

        $table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mto_draft_orders (
                      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                      mto_id char(64) NULL,
                      mto_store char(32) NULL,
                      mto_session_key char(32) NOT NULL,
                      mto_lead_id char(32) NOT NULL,
                      mto_cart text NOT NULL,
                      mto_cart_value DECIMAL(19 , 2) NOT NULL,
                      date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                      date_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                      PRIMARY KEY  (id),
                      UNIQUE KEY id (id)
                    ) $collate;";
        $wpdb->query($table);
    }

    private static function dropDraftOrderTable($tableName)
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mto_draft_orders");
    }

    public static function uninstall(){
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

        self::dropDraftOrderTable();
    }

    public static function activate(){
        MtoInstall::createDraftOrderTable();
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
}