<?php

/**
 * Handles background scanning to build internal link statistics (Inbound/Outbound counts).
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Link_Indexer
{
    public function __construct()
    {
        // Hook for cron job
        add_action('ail_link_stats_index_cron', array($this, 'run_link_indexing'));
    }

    /**
     * Build the stats queue and count inbound strings
     * This method runs in batches to prevent timeouts
     */
    public function run_link_indexing()
    {
        global $wpdb;

        // Ensure we don't run multiple instances
        if (get_transient('ail_link_indexing_lock')) {
            return;
        }
        set_transient('ail_link_indexing_lock', true, 10 * MINUTE_IN_SECONDS);

        $table_link_stats = $wpdb->prefix . 'ail_link_stats';

        // Get queue of posts to index
        $queue_json = get_option('ail_link_index_queue', '');
        $queue = json_decode($queue_json, true);

        // If queue is empty, repopulate it
        if (!is_array($queue) || empty($queue)) {
            $post_types = "('post')"; // Can be expanded via filter
            $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type IN $post_types AND post_status = 'publish'";
            $queue = $wpdb->get_col($query);

            // If still empty, nothing to do
            if (empty($queue)) {
                delete_transient('ail_link_indexing_lock');
                return;
            }

            // Re-initialize queue and reset pointers
            update_option('ail_link_index_queue', json_encode($queue));
            update_option('ail_link_index_queue_pointer', 0);

            // Reset all stats before full sweep
            // $wpdb->query("TRUNCATE TABLE $table_link_stats"); // Truncate might be too harsh, updating to 0 is safer
            $wpdb->query("UPDATE $table_link_stats SET inbound_internal_links = 0, outbound_internal_links = 0");
        }

        $pointer = (int) get_option('ail_link_index_queue_pointer', 0);
        $batch_size = 50; // Parse 50 posts per run

        $batch_posts = array_slice($queue, $pointer, $batch_size);

        if (empty($batch_posts)) {
            // Finished
            update_option('ail_link_index_queue', '');
            update_option('ail_link_index_queue_pointer', 0);
            delete_transient('ail_link_indexing_lock');
            return;
        }

        $start_time = time();
        $max_execution_time = 25; // 25s limit

        $site_url = get_site_url();
        $site_url_escaped = preg_quote($site_url, '/');

        foreach ($batch_posts as $post_id) {
            if ((time() - $start_time) > $max_execution_time) {
                break;
            }

            $this->process_single_post_links($post_id, $site_url, $site_url_escaped, $table_link_stats);
            $pointer++;
        }

        // Update pointer
        update_option('ail_link_index_queue_pointer', $pointer);

        // If we reached the end
        if ($pointer >= count($queue)) {
            update_option('ail_link_index_queue', '');
            update_option('ail_link_index_queue_pointer', 0);
        }

        delete_transient('ail_link_indexing_lock');
    }

    /**
     * Process links for a single post
     */
    private function process_single_post_links($post_id, $site_url, $site_url_escaped, $table_link_stats)
    {
        global $wpdb;

        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }

        $content = $post->post_content;

        // Find all internal links in this post
        // Pattern matches href pointing to the site url or relative paths.
        // Simplified approach: match href="..."
        preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);

        $outbound_count = 0;
        $inbound_targets = array(); // Post IDs that this post links to

        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                $url = trim($url);

                // Check if it's an internal link
                if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                    // Make it absolute if relative
                    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                        $url = rtrim($site_url, '/') . $url;
                    }

                    // Don't count self links
                    $target_post_id = url_to_postid($url);
                    if ($target_post_id && $target_post_id != $post_id) {
                        $outbound_count++;
                        $inbound_targets[] = $target_post_id;
                    }
                }
            }
        }

        // 1. Update outbound stats for THIS post
        $this->update_link_stats($post_id, 'outbound_internal_links', $outbound_count, true);

        // 2. Update inbound stats for TARGET posts
        if (!empty($inbound_targets)) {
            $inbound_targets = array_unique($inbound_targets); // Count each post at most once per source post (or we could count total links)
            foreach ($inbound_targets as $target_id) {
                $this->update_link_stats($target_id, 'inbound_internal_links', 1, false); // Increment
            }
        }
    }

    /**
     * Upsert link stats record
     */
    private function update_link_stats($post_id, $column, $value, $is_absolute)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_link_stats';

        $exists = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $table WHERE post_id = %d", $post_id));

        if ($exists) {
            if ($is_absolute) {
                $wpdb->query($wpdb->prepare("UPDATE $table SET $column = %d, last_updated = %s WHERE post_id = %d", $value, current_time('mysql'), $post_id));
            } else {
                $wpdb->query($wpdb->prepare("UPDATE $table SET $column = $column + %d, last_updated = %s WHERE post_id = %d", $value, current_time('mysql'), $post_id));
            }
        } else {
            $data = array(
                'post_id' => $post_id,
                'inbound_internal_links' => 0,
                'outbound_internal_links' => 0,
                'last_updated' => current_time('mysql')
            );
            $data[$column] = $value;
            $wpdb->insert($table, $data);
        }
    }
}
