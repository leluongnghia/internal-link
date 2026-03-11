<?php

/**
 * Handles all of the click tracking related functionality for AI Internal Linker
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Click_Tracker
{

    /**
     * Register hooks for click tracking
     */
    public function __construct()
    {
        add_action('wp_ajax_ail_link_clicked', array($this, 'ajax_link_clicked'));
        add_action('wp_ajax_nopriv_ail_link_clicked', array($this, 'ajax_link_clicked'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts for frontend click tracking
     */
    public function enqueue_scripts()
    {
        // Only enqueue on singular pages (posts, pages, custom post types)
        if (!is_singular()) {
            return;
        }

        wp_enqueue_script(
            'ail-click-tracker-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/ail-click-tracker.js',
            array('jquery'),
            AIL_VERSION,
            true
        );

        wp_localize_script(
            'ail-click-tracker-js',
            'ail_click_tracker',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'post_id' => get_the_ID()
            )
        );
    }

    /**
     * Stores data related to the user's recent link click
     **/
    public function ajax_link_clicked()
    {
        // exit if any critical data is missing
        if (
            !isset($_POST['post_id']) ||
            !isset($_POST['link_url']) ||
            !isset($_POST['link_anchor'])
        ) {
            wp_send_json_error('Missing data');
            die();
        }

        global $wpdb;

        // Assemble the click data
        $source_post_id = intval($_POST['post_id']);
        $url = esc_url_raw(urldecode($_POST['link_url']));
        $anchor = sanitize_text_field(urldecode($_POST['link_anchor']));

        $target_post_id = url_to_postid($url);

        $user_ip = $this->get_current_client_ip();

        // Get when the click was made
        $click_time = current_time('mysql', true);

        $table_name = $wpdb->prefix . 'ail_click_log';

        // Create in the insert data
        $insert_data = array(
            'link_url' => $url,
            'source_post_id' => $source_post_id,
            'target_post_id' => $target_post_id,
            'anchor_text' => $anchor,
            'visitor_ip' => $user_ip,
            'clicked_at' => $click_time,
        );

        // Create the format array
        $format_array = array(
            '%s', // link_url
            '%d', // source_post_id
            '%d', // target_post_id
            '%s', // anchor_text
            '%s', // visitor_ip
            '%s', // clicked_at
        );

        // Save the click data to the database
        $result = $wpdb->insert($table_name, $insert_data, $format_array);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Database error');
        }

        die();
    }

    /**
     * Gets the user's ip address.
     * @return string $ipaddress The user's ip address.
     **/
    private function get_current_client_ip()
    {
        $ipaddress = (isset($_SERVER['REMOTE_ADDR'])) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_X_FORWARDED']);
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']);
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != '127.0.0.1') {
            $ipaddress = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
        }

        $ips = explode(',', $ipaddress);
        if (isset($ips[1])) {
            $ipaddress = ltrim(trim($ips[0])); //Fix for some proxies
        }

        return $ipaddress;
    }
}
