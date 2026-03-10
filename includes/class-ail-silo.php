<?php

/**
 * Manages the Content Silo ecosystem (Pillar & Cluster rules).
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Silo
{
    /**
     * Get the Silo cluster relationships for a given post.
     * 
     * Rules:
     * - If it's a Pillar, it can only link to its Clusters.
     * - If it's a Cluster, it can link to its Pillar, or other Clusters in the SAME Silo.
     *
     * @param int $post_id The ID of the post.
     * @return array|false Array of allowed target post IDs, or false if not in a Silo.
     */
    public static function get_allowed_targets($post_id)
    {
        global $wpdb;
        $table_silos = $wpdb->prefix . 'ail_silos';
        $post_url = get_permalink($post_id);

        if (!$post_url) {
            return false;
        }

        // Check if this post is a Pillar
        $is_pillar = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_silos WHERE pillar_url = %s",
            $post_url
        ));

        if ($is_pillar) {
            // It's a Pillar. Allowed targets: its clusters.
            $clusters = json_decode($is_pillar->cluster_urls, true);
            return self::urls_to_ids($clusters);
        }

        // Check if this post is a Cluster in any Silo
        $all_silos = $wpdb->get_results("SELECT * FROM $table_silos");
        foreach ($all_silos as $silo) {
            $clusters = json_decode($silo->cluster_urls, true);
            if (is_array($clusters) && in_array($post_url, $clusters)) {
                // It's a Cluster in this Silo.
                // Allowed targets: The Pillar + All other Clusters in this Silo
                $allowed_urls = $clusters;
                $allowed_urls[] = $silo->pillar_url;

                // Remove the current post URL from the allowed targets
                $allowed_urls = array_diff($allowed_urls, [$post_url]);

                return self::urls_to_ids($allowed_urls);
            }
        }

        return false; // Not part of any Silo
    }

    /**
     * Convert an array of URLs to an array of Post IDs.
     *
     * @param array $urls Array of URLs.
     * @return array Array of valid Post IDs.
     */
    private static function urls_to_ids($urls)
    {
        $ids = [];
        if (is_array($urls)) {
            foreach ($urls as $url) {
                $post_id = url_to_postid($url);
                if ($post_id) {
                    $ids[] = $post_id;
                }
            }
        }
        return array_unique($ids);
    }
}
