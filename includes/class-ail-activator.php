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
        if (false === get_option('ail_api_key_openai')) {
            add_option('ail_api_key_openai', '');
        }
        if (false === get_option('ail_api_key_gemini')) {
            add_option('ail_api_key_gemini', '');
        }
        if (false === get_option('ail_link_source')) {
            add_option('ail_link_source', 'category'); // category, tag, all, silo
        }
        if (false === get_option('ail_max_links')) {
            add_option('ail_max_links', 5);
        }
        if (false === get_option('ail_max_anchor_repeat')) {
            add_option('ail_max_anchor_repeat', 3);
        }

        // Create Logs Table
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_logs = $wpdb->prefix . 'ail_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            provider varchar(50) NOT NULL,
            model varchar(100) NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            links_created int(5) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_silos = $wpdb->prefix . 'ail_silos';
        $sql_silos = "CREATE TABLE $table_silos (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            pillar_url varchar(255) NOT NULL,
            cluster_urls text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY pillar_url (pillar_url)
        ) $charset_collate;";

        $table_anchor_log = $wpdb->prefix . 'ail_anchor_log';
        $sql_anchor_log = "CREATE TABLE $table_anchor_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            exact_phrase varchar(255) NOT NULL,
            target_url varchar(255) NOT NULL,
            usage_count int(11) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY phrase_target (exact_phrase, target_url)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_logs);
        dbDelta($sql_silos);
        dbDelta($sql_anchor_log);

        // Clear old cron
        wp_clear_scheduled_hook('ail_daily_sweep_event');

        // Schedule new WP-Crons for Intelligent Sweeper
        if (!wp_next_scheduled('ail_batch_index_cron')) {
            wp_schedule_event(time(), 'ail_six_hourly', 'ail_batch_index_cron');
        }
        if (!wp_next_scheduled('ail_batch_process_cron')) {
            wp_schedule_event(time(), 'ail_five_minutes', 'ail_batch_process_cron');
        }
        if (!wp_next_scheduled('ail_regex_sweep_cron')) {
            wp_schedule_event(time(), 'ail_half_hourly', 'ail_regex_sweep_cron');
        }
    }

}
