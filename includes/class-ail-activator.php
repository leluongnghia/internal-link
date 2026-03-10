<?php

/**
 * Fired during plugin activation
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Activator
{

    /**
     * Activate the plugin.
     * Set default options if they don't exist.
     */
    public static function activate()
    {
        if (false === get_option('ail_api_provider')) {
            add_option('ail_api_provider', 'openai');
        }
        if (false === get_option('ail_api_key')) {
            add_option('ail_api_key', '');
        }
        if (false === get_option('ail_link_source')) {
            add_option('ail_link_source', 'category'); // category, tag, all
        }
        if (false === get_option('ail_max_links')) {
            add_option('ail_max_links', 5);
        }

        // Create Logs Table
        global $wpdb;
        $table_name = $wpdb->prefix . 'ail_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            provider varchar(50) NOT NULL,
            model varchar(100) NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            links_created int(5) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

}
