<?php

/**
 * Retrieve candidate posts for internal linking.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Retriever
{

    /**
     * Get candidate posts based on the current post's context.
     *
     * @param int $post_id The current post ID.
     * @param int $limit Number of candidates to retrieve.
     * @return array Array of candidates with title, url, summary.
     */
    public function get_candidate_posts($post_id, $limit = 50)
    {
        $strategy = get_option('ail_link_source', 'category');

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true, // Performance optimization
            'fields' => 'ids' // Fetch IDs first
        );

        // Include Silo logic if required
        if (!class_exists('AIL_Silo')) {
            require_once plugin_dir_path(__FILE__) . 'class-ail-silo.php';
        }

        // Apply Strategy
        if ('silo' === $strategy) {
            $allowed_ids = AIL_Silo::get_allowed_targets($post_id);
            if ($allowed_ids !== false && !empty($allowed_ids)) {
                $args['post__in'] = $allowed_ids;
            } else {
                // Fallback: If not in a Silo, or Silo empty, just use recent posts (remove category constraints)
                // We don't add category/tag constraints here, just recent posts.
            }
        } elseif ('category' === $strategy) {
            $categories = wp_get_post_categories($post_id);
            if (!empty($categories)) {
                $args['category__in'] = $categories;
            }
        } elseif ('tag' === $strategy) {
            $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
            if (!empty($tags)) {
                $args['tag__in'] = $tags;
            }
        }

        // Cache Key
        $cache_key = 'ail_candidates_' . $post_id . '_' . $strategy;
        // Sử dụng Object Cache (Memory) thay vì Transient (DB) để tăng tốc độ đối với site lớn
        $candidates = wp_cache_get($cache_key, 'ail');

        if (false === $candidates) {
            $query = new WP_Query($args);
            $candidates = array();

            if ($query->have_posts()) {
                // Giới hạn max 20 candidates để tiết kiệm AI Tokens
                $count = 0;
                foreach ($query->posts as $p_id) {
                    if ($count >= 20)
                        break;
                    $candidates[] = array(
                        'id' => $p_id,
                        'title' => get_the_title($p_id),
                        'url' => get_permalink($p_id),
                        'summary' => $this->get_short_summary($p_id)
                    );
                    $count++;
                }
            }

            // Cache 15 phút
            wp_cache_set($cache_key, $candidates, 'ail', 15 * MINUTE_IN_SECONDS);
        }

        return $candidates;
    }

    /**
     * Get short summary for context. (Optimized performance)
     */
    private function get_short_summary($post_id)
    {
        $post = get_post($post_id);
        if (!$post)
            return '';

        $excerpt = trim($post->post_excerpt);
        if (empty($excerpt)) {
            // Tối ưu: Cắt nhanh đoạn text thay vì load cả body lớn qua hàm wp_trim_words
            $raw_text = wp_strip_all_tags(mb_substr($post->post_content, 0, 400));
            $excerpt = wp_trim_words($raw_text, 15, '...');
        }
        return $excerpt;
    }

}
