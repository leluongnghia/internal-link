<?php

/**
 * Handles background scanning and link injection.
 * Utilizes WP-Cron to prevent server timeouts.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Sweeper
{
    private $injector;

    public function __construct()
    {
        // Hook new intelligent batch processor events
        add_action('ail_batch_index_cron', array($this, 'run_batch_index'));
        add_action('ail_batch_process_cron', array($this, 'run_batch_process'));
        add_action('ail_regex_sweep_cron', array($this, 'run_regex_sweep'));
    }

    /**
     * CronJob 1: Build the queue of all posts that need checking (Every 6 hours)
     */
    public function run_batch_index()
    {
        // Only run if batch processing is enabled
        if (!get_option('ail_batch_enabled', 1)) {
            return;
        }

        global $wpdb;

        // Reset pointer to 0
        update_option('ail_batch_queue_pointer', 0);

        // Fetch all published post IDs
        $post_types = "('post')"; // Extend if needed
        $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type IN $post_types AND post_status = 'publish' ORDER BY ID DESC";
        $post_ids = $wpdb->get_col($query);

        if (!empty($post_ids)) {
            // Save as JSON encoded array in wp_options
            update_option('ail_batch_queue', json_encode($post_ids));
        } else {
            // Empty queue
            update_option('ail_batch_queue', json_encode(array()));
        }

        // Log indexing event
        error_log('AI Internal Linker: Indexed ' . count($post_ids) . ' posts for batch processing.');
    }

    /**
     * CronJob 2: Process a small batch from queue (Every 5 minutes)
     */
    public function run_batch_process()
    {
        if (!get_option('ail_batch_enabled', 1)) {
            return;
        }

        // Ensure we don't run multiple instances simultaneously (Lock mechanism)
        if (get_transient('ail_batch_processing_lock')) {
            return;
        }
        set_transient('ail_batch_processing_lock', true, 4 * MINUTE_IN_SECONDS);

        // Track start time to guard against timeout
        $start_time = time();
        $max_execution_time = 45; // seconds

        // Get queue and pointer
        $queue_json = get_option('ail_batch_queue', '[]');
        $queue = json_decode($queue_json, true);
        if (!is_array($queue) || empty($queue)) {
            delete_transient('ail_batch_processing_lock');
            return; // Nothing to process
        }

        $pointer = (int) get_option('ail_batch_queue_pointer', 0);

        // If we reached the end of queue
        if ($pointer >= count($queue)) {
            delete_transient('ail_batch_processing_lock');
            return; // Wait for next index run
        }

        $batch_size = (int) get_option('ail_batch_size', 5);
        $batch_posts = array_slice($queue, $pointer, $batch_size);

        if (empty($batch_posts)) {
            delete_transient('ail_batch_processing_lock');
            return;
        }

        if (!class_exists('AIL_Injector')) {
            require_once plugin_dir_path(__FILE__) . 'class-ail-injector.php';
        }
        $this->injector = new AIL_Injector();

        $processed_count = 0;

        foreach ($batch_posts as $post_id) {
            // Time guard
            if ((time() - $start_time) > $max_execution_time) {
                error_log("AI Internal Linker: Batch process hit time limit ($max_execution_time s).");
                break;
            }

            // Memory guard
            if (memory_get_usage() > (ini_get('memory_limit') ? intval(ini_get('memory_limit')) * 1024 * 1024 * 0.8 : 134217728)) { // 128MB * 0.8 safety defaults
                error_log("AI Internal Linker: Batch process hit memory guard.");
                break;
            }

            // Skip if processed recently (e.g. 7 days fallback just in case queue is stale)
            $last_processed = get_post_meta($post_id, '_ail_last_processed', true);
            if ($last_processed && strtotime((string) $last_processed) > (time() - 7 * DAY_IN_SECONDS)) {
                $processed_count++;
                continue;
            }

            // Run AI injection logic for this post
            $post = get_post($post_id);
            if ($post && $post->post_status === 'publish') {
                $original_content = $post->post_content;
                $modified_content = $this->injector->inject_links($original_content, $post_id);

                if ($modified_content !== $original_content) {
                    // Update post silently
                    remove_action('save_post', 'ail_on_post_save'); // Suppose we will have this, prevent loop
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_content' => $modified_content
                    ));
                }
            }

            $processed_count++;
        }

        // Update pointer
        update_option('ail_batch_queue_pointer', $pointer + $processed_count);
        delete_transient('ail_batch_processing_lock');
    }

    /**
     * CronJob 3: Fallback regex-based sweeper for manually defined links (Every 30 mins)
     * No AI called, highly efficient.
     */
    public function run_regex_sweep()
    {
        $manual_links = get_option('ail_manual_links', array());

        if (empty($manual_links) || !is_array($manual_links)) {
            return;
        }

        // Lock
        if (get_transient('ail_regex_sweep_lock')) {
            return;
        }
        set_transient('ail_regex_sweep_lock', true, 25 * MINUTE_IN_SECONDS);

        $posts_per_run = (int) get_option('ail_sweep_batch_size', 20);
        $start_time = time();

        if (!class_exists('AIL_Injector')) {
            require_once plugin_dir_path(__FILE__) . 'class-ail-injector.php';
        }

        // Need to load parser manually since we use it directly here
        if (!class_exists('AIL_HTMLParser')) {
            require_once plugin_dir_path(__FILE__) . 'class-ail-html-parser.php';
        }

        $this->injector = new AIL_Injector();

        foreach ($manual_links as $link_data) {
            if ((time() - $start_time) > 25) { // 25s limit
                break;
            }

            $phrase = isset($link_data['phrase']) ? trim($link_data['phrase']) : '';
            $url = isset($link_data['url']) ? esc_url_raw($link_data['url']) : '';

            if (empty($phrase) || empty($url))
                continue;

            $this->process_manual_phrase($phrase, $url, $posts_per_run);
        }

        delete_transient('ail_regex_sweep_lock');
    }

    /**
     * Regex Process a specific manual phrase against DB
     */
    private function process_manual_phrase($phrase, $url, $limit)
    {
        global $wpdb;

        $escaped_phrase = $wpdb->esc_like($phrase);
        $escaped_url = $wpdb->esc_like($url);

        // Find posts that don't have this URL and were not swept in last 24h
        $query = $wpdb->prepare(
            "
            SELECT p.ID, p.post_content 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm 
                ON p.ID = pm.post_id AND pm.meta_key = '_ail_last_swept'
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            AND p.post_content LIKE %s 
            AND p.post_content NOT LIKE %s
            AND (pm.meta_value IS NULL OR pm.meta_value < %s)
            ORDER BY p.post_date DESC
            LIMIT %d
        ",
            '%' . $escaped_phrase . '%',
            '%' . $escaped_url . '%',
            date('Y-m-d H:i:s', time() - DAY_IN_SECONDS),
            $limit
        );

        $posts = $wpdb->get_results($query);

        if (empty($posts)) {
            return;
        }

        $max_repeat = intval(get_option('ail_max_anchor_repeat', 3));

        foreach ($posts as $post) {
            if ($this->has_exceeded_anchor_limit($phrase, $url, $max_repeat)) {
                break;
            }

            // Silo Gate Check
            $allowed_ids = AIL_Silo::get_allowed_targets($post->ID);
            if ($allowed_ids !== false) {
                // If it's in a silo, verify if target URL belongs to an allowed ID
                $target_id = url_to_postid($url);
                if ($target_id && !in_array($target_id, $allowed_ids)) {
                    continue; // Skip because target is outside silo
                }
            }

            // Using our advanced Parser instead of naive regex
            $new_content = AIL_HTMLParser::replace_phrase($post->post_content, $phrase, $url);

            if ($new_content !== $post->post_content) {
                // Update post silently (avoid triggering 'save_post' hook that might re-trigger AI)
                remove_action('save_post', 'ail_on_post_save'); // Suppose we will have this, prevent loop

                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $new_content
                ));

                // Log usage
                $this->injector->log_anchor_usage($phrase, $url);
                $this->injector->log_action($post->ID, 'regex', 'Regex Sweep', 1);
            }
        }
    }

    /**
     * Check if a specific anchor mapped to a specific URL has reached the limit.
     */
    private function has_exceeded_anchor_limit($phrase, $url, $max_limit)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ail_anchor_log';

        $query = $wpdb->prepare("SELECT usage_count FROM $table_name WHERE exact_phrase = %s AND target_url = %s", $phrase, $url);
        $count = $wpdb->get_var($query);

        return (!empty($count) && intval($count) >= $max_limit);
    }
}
