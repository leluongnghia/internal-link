<?php

/**
 * Retrieve candidate posts for internal linking.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Retriever {

	/**
	 * Get candidate posts based on the current post's context.
     *
     * @param int $post_id The current post ID.
     * @param int $limit Number of candidates to retrieve.
     * @return array Array of candidates with title, url, summary.
	 */
	public function get_candidate_posts( $post_id, $limit = 50 ) {
        $strategy = get_option( 'ail_link_source', 'category' );
        
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'post__not_in'   => array( $post_id ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true, // Performance optimization
            'fields'         => 'ids' // Fetch IDs first
        );

        // Apply Strategy
        if ( 'category' === $strategy ) {
            $categories = wp_get_post_categories( $post_id );
            if ( ! empty( $categories ) ) {
                $args['category__in'] = $categories;
            }
        } elseif ( 'tag' === $strategy ) {
            $tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
            if ( ! empty( $tags ) ) {
                $args['tag__in'] = $tags;
            }
        }

        // Cache Key
        $cache_key = 'ail_candidates_' . $post_id . '_' . $strategy;
        $candidates = get_transient( $cache_key );

        if ( false === $candidates ) {
            $query = new WP_Query( $args );
            $candidates = array();

            if ( $query->have_posts() ) {
                foreach ( $query->posts as $p_id ) {
                    $candidates[] = array(
                        'id'      => $p_id,
                        'title'   => get_the_title( $p_id ),
                        'url'     => get_permalink( $p_id ),
                        // Get a short summary (excerpt or first 20 words)
                        'summary' => $this->get_short_summary( $p_id )
                    );
                }
            }

            // Cache for 1 hour
            set_transient( $cache_key, $candidates, HOUR_IN_SECONDS );
        }

        return $candidates;
	}

    /**
     * Get short summary for context.
     */
    private function get_short_summary( $post_id ) {
        $post = get_post( $post_id );
        $excerpt = $post->post_excerpt;
        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( $post->post_content, 15, '...' );
        }
        return wp_strip_all_tags( $excerpt );
    }

}
