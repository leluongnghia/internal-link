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

        $table_keywords = $wpdb->prefix . 'ail_keywords';
        $sql_keywords = "CREATE TABLE $table_keywords (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            intent varchar(100) DEFAULT '',
            volume int(11) DEFAULT 0,
            kd float DEFAULT 0,
            cpc float DEFAULT 0,
            cluster_group varchar(255) DEFAULT '',
            is_pillar tinyint(1) DEFAULT 0,
            imported_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY keyword (keyword)
        ) $charset_collate;";

        $table_link_stats = $wpdb->prefix . 'ail_link_stats';
        $sql_link_stats = "CREATE TABLE $table_link_stats (
            post_id bigint(20) NOT NULL,
            inbound_internal_links int(11) DEFAULT 0 NOT NULL,
            outbound_internal_links int(11) DEFAULT 0 NOT NULL,
            last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (post_id)
        ) $charset_collate;";

        $table_click_log = $wpdb->prefix . 'ail_click_log';
        $sql_click_log = "CREATE TABLE $table_click_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_url varchar(500) NOT NULL,
            source_post_id bigint(20) NOT NULL,
            target_post_id bigint(20) DEFAULT 0,
            anchor_text varchar(255) NOT NULL,
            visitor_ip varchar(100) NOT NULL,
            clicked_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY source_post_id (source_post_id),
            KEY clicked_at (clicked_at)
        ) $charset_collate;";

        $table_gsc_keywords = $wpdb->prefix . 'ail_gsc_keywords';
        $sql_gsc_keywords = "CREATE TABLE $table_gsc_keywords (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(500) NOT NULL,
            page_url varchar(500) NOT NULL,
            impressions int(11) DEFAULT 0,
            clicks int(11) DEFAULT 0,
            ctr float DEFAULT 0,
            position float DEFAULT 0,
            data_date date NOT NULL,
            processed tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY keyword (keyword(191)),
            KEY processed (processed)
        ) $charset_collate;";

        $table_link_report = $wpdb->prefix . 'ail_link_report';
        $sql_link_report = "CREATE TABLE $table_link_report (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            inbound_count int(11) DEFAULT 0,
            outbound_count int(11) DEFAULT 0,
            last_scanned datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_logs);
        dbDelta($sql_silos);
        dbDelta($sql_link_stats);
        dbDelta($sql_keywords);
        dbDelta($sql_click_log);
        dbDelta($sql_gsc_keywords);
        dbDelta($sql_link_report);

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
