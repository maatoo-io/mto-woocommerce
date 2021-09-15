<?php

namespace Maatoo\WooCommerce\Service\Admin;


/**
 * Class PluginUpdate
 *
 * @package Maatoo\WooCommerce\Service\Admin
 */
class PluginUpdate
{
    public static $cache_allowed = true;

    public static function request(){

        $remote = get_transient( MTO_PLUGIN_SLUG );

        if( false === $remote || ! self::$cache_allowed ) {

            $remote = wp_remote_get(
                'https://update.maatoo.io/repository.php?plugin=' . MTO_PLUGIN_SLUG . '&cur='. MTO_PLUGIN_VERSION ,
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );

            if(
                is_wp_error( $remote )
                || 200 !== wp_remote_retrieve_response_code( $remote )
                || empty( wp_remote_retrieve_body( $remote ) )
            ) {
                return false;
            }

            set_transient( MTO_PLUGIN_SLUG, $remote, MTO_UPDATE_CACHE_EXPIRE );

        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );

        return $remote;

    }

    public static function details(array $links_array, $plugin_file_name, $plugin_data, $status) {
        if ( MTO_PLUGIN_SLUG.'/'.MTO_PLUGIN_SLUG.'.php' !== $plugin_file_name ) {
            return $links_array;
        }

        $remote = self::request();

        if(
            $remote
            && version_compare( MTO_PLUGIN_VERSION, $remote->version, '>=' )
        ) {

            $links_array[] = sprintf(
                '<a href="%s" class="thickbox open-plugin-details-modal">%s</a>',
                add_query_arg(
                    array(
                        'tab' => 'plugin-information',
                        'plugin' => MTO_PLUGIN_SLUG,
                        'TB_iframe' => true,
                        'width' => 772,
                        'height' => 788
                    ),
                    admin_url( 'plugin-install.php' )
                ),
                __( 'View details' )
            );
        }
        

        return $links_array;

    
    }

    public static function info( $res, $action, $args ) {    
        // do nothing if you're not getting plugin information right now
        if( 'plugin_information' !== $action ) {
            return false;
        }

        // do nothing if it is not our plugin
        if( MTO_PLUGIN_SLUG !== $args->slug ) {
            return false;
        }

        // get updates
        $remote = self::request();

        if( ! $remote ) {
            return false;
        }

        $res = new \stdClass();

        $res->plugin = MTO_PLUGIN_SLUG.'/'.MTO_PLUGIN_SLUG.'.php';
        $res->name = $remote->name;
        $res->slug = $remote->slug;
        $res->version = $remote->version;
        $res->tested = $remote->tested;
        $res->requires = $remote->requires;
        $res->author = $remote->author;
        $res->author_profile = $remote->author_profile;
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->requires_php = $remote->requires_php;
        $res->last_updated = $remote->last_updated;

        $res->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog
        );

        if( ! empty( $remote->banners ) ) {
            $res->banners = array(
                'low' => $remote->banners->low,
                'high' => $remote->banners->high
            );
        }

        return $res;

    }

    public static function update( $transient ) {
        
        if ( empty($transient->checked ) ) {
            return $transient;
        }

        $remote = self::request();

        if(
            $remote
            && version_compare( MTO_PLUGIN_VERSION, $remote->version, '<' )
            && version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
            && version_compare( $remote->requires_php, PHP_VERSION, '<' )
        ) {
            $res = new \stdClass();
            //$res->id = 'mto-woocommerce';
            $res->slug = MTO_PLUGIN_SLUG;
            $res->plugin = MTO_PLUGIN_SLUG.'/'.MTO_PLUGIN_SLUG.'.php';
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;

            $transient->response[ $res->plugin ] = $res;

        }

        return $transient;

    }

    public static function purge( $upgrader_object, $options){

        if (
            self::$cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options[ 'type' ]
        ) {
            // just clean the cache when new plugin version is installed
            delete_transient( MTO_PLUGIN_SLUG );
        }

    }

}