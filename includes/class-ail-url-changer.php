<?php

/**
 * Handles automatic updating of internal links when a post's URL changes.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_URL_Changer
{

    /**
     * Register hooks for URL changer
     */
    public function __construct()
    {
        add_action('post_updated', array($this, 'detect_url_change'), 10, 3);
        add_action('ail_execute_url_replacement_cron', array($this, 'background_replace_urls'), 10, 2);
    }

    /**
     * Hooked into 'post_updated' to detect if a post's URL has changed.
     */
    public function detect_url_change($post_ID, $post_after, $post_before)
    {
        // Only run on published posts that are indexable
        if ($post_after->post_status !== 'publish' || $post_before->post_status !== 'publish') {
            return;
        }

        // Avoid autosaves and revisions
        if (wp_is_post_autosave($post_ID) || wp_is_post_revision($post_ID)) {
            return;
        }

        $old_url = get_permalink($post_before->ID);

        // Remove filters temporarily to get the real new permalink
        remove_filter('post_link', 'wp_trim_words');
        $new_url = get_permalink($post_after->ID);

        if ($old_url && $new_url && $old_url !== $new_url) {

            // Optional: We can write to a simple DB table wp_ail_url_changes to track if we need UI

            // Schedule a background job to scan and replace URLs to avoid slowing down post save
            if (!wp_next_scheduled('ail_execute_url_replacement_cron', array($old_url, $new_url))) {
                wp_schedule_single_event(time() + 5, 'ail_execute_url_replacement_cron', array($old_url, $new_url));
            }

            // Also check categories and tags if needed, but post_updated handles only posts/pages
        }
    }

    /**
     * Background worker to replace old URL with new URL across all content
     */
    public function background_replace_urls($old_url, $new_url)
    {
        global $wpdb;

        if (empty($old_url) || empty($new_url)) {
            return;
        }

        // Ensure no trailing slashes for safer LIKE matching base
        $old_url_clean = rtrim($old_url, '/');

        $like_pattern = '%' . $wpdb->esc_like($old_url_clean) . '%';

        // Find all posts containing the old URL
        $query = $wpdb->prepare("
			SELECT ID, post_content, post_title 
			FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND post_content LIKE %s
		", $like_pattern);

        $posts = $wpdb->get_results($query);

        if (empty($posts)) {
            return;
        }

        foreach ($posts as $post) {
            $modified = false;
            $content = $post->post_content;

            // Replace absolute matches (with trailing slash)
            if (strpos($content, $old_url) !== false) {
                $content = str_replace($old_url, $new_url, $content);
                $modified = true;
            }

            // Replace clean matches (without trailing slash)
            if (strpos($content, $old_url_clean . '"') !== false) {
                $content = str_replace($old_url_clean . '"', rtrim($new_url, '/') . '"', $content);
                $modified = true;
            }

            if (strpos($content, $old_url_clean . '\'') !== false) {
                $content = str_replace($old_url_clean . '\'', rtrim($new_url, '/') . '\'', $content);
                $modified = true;
            }

            if ($modified) {
                // Prevent our own save hook from triggering
                remove_action('post_updated', array($this, 'detect_url_change'), 10);

                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_content' => $content
                ));

                add_action('post_updated', array($this, 'detect_url_change'), 10, 3);
            }
        }
    }
}
