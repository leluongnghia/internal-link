<?php

/**
 * Keyword Clustering Algorithm for Ai-Internal-Links
 *
 * Implements a Semrush-style clustering methodology:
 * 1. Semantic grouping via shared N-gram / root-word overlap (SERP similarity proxy)
 * 2. Intent-aware separation (Informational vs Commercial/Transactional clusters stay separate)
 * 3. Hub & Spoke election: highest-volume keyword becomes the Pillar (Primary Keyword)
 * 4. Saves cluster_group and is_pillar back to the ail_keywords table
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Keyword_Clusterer
{
    /**
     * Minimum token overlap ratio to consider two keywords as belonging to the same cluster.
     * Range 0..1. Higher = stricter (fewer but tighter clusters).
     */
    const SIMILARITY_THRESHOLD = 0.40;

    /**
     * Stop-words to ignore when comparing keyword tokens.
     */
    private static $stopwords = [
        'a',
        'an',
        'the',
        'and',
        'or',
        'for',
        'in',
        'on',
        'at',
        'to',
        'of',
        'with',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'by',
        'from',
        'up',
        'about',
        'into',
        'through',
        'during',
        'before',
        'after',
        'above',
        'below',
        'between',
        'out',
        'off',
        'over',
        'under',
        'again',
        'further',
        'then',
        'once',
        'how',
        'what',
        'which',
        'who',
        'this',
        'that',
        'these',
        'those',
        'can',
        'will',
        'would',
        'do',
        'does',
        'did',
        'so',
        'yet',
        'both',
        'each',
        'few',
        'more',
        'most',
        'other',
        'some',
        'such',
        'no',
        'not',
        'only',
        'same',
        'than',
        'too',
        'very',
    ];

    /**
     * Run the full clustering pipeline on all keywords in the DB.
     *
     * @return array   ['clusters' => [...], 'total' => int, 'cluster_count' => int]
     */
    public function run()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_keywords';

        // Load all keywords
        $keywords = $wpdb->get_results("SELECT id, keyword, intent, volume, kd FROM $table ORDER BY volume DESC", ARRAY_A);

        if (empty($keywords)) {
            return ['clusters' => [], 'total' => 0, 'cluster_count' => 0];
        }

        // Reset existing cluster assignments
        $wpdb->query("UPDATE $table SET cluster_group = '', is_pillar = 0");

        // Step 1: Group keywords into intent buckets first
        $intent_buckets = $this->split_by_intent($keywords);

        $all_clusters = [];
        $cluster_index = 1;

        foreach ($intent_buckets as $intent_label => $bucket) {
            $grouped = $this->cluster_bucket($bucket, $intent_label, $cluster_index);
            $all_clusters = array_merge($all_clusters, $grouped['clusters']);
            $cluster_index = $grouped['next_index'];
        }

        // Step 2: Persist clusters back to DB
        foreach ($all_clusters as $cluster) {
            $pillar_id = null;
            $pillar_volume = -1;

            // Elect pillar = highest volume in cluster
            foreach ($cluster['keywords'] as $kw) {
                if ((int)$kw['volume'] > $pillar_volume) {
                    $pillar_volume = (int)$kw['volume'];
                    $pillar_id = $kw['id'];
                }
            }

            foreach ($cluster['keywords'] as $kw) {
                $is_pillar = ($kw['id'] === $pillar_id) ? 1 : 0;
                $wpdb->update(
                    $table,
                    [
                        'cluster_group' => $cluster['name'],
                        'is_pillar'     => $is_pillar,
                    ],
                    ['id' => $kw['id']],
                    ['%s', '%d'],
                    ['%d']
                );
            }
        }

        return [
            'clusters'      => $all_clusters,
            'total'         => count($keywords),
            'cluster_count' => count($all_clusters),
        ];
    }

    /**
     * Split keyword rows into intent buckets.
     * Intent types: informational | navigational | commercial | transactional | mixed/unknown
     */
    private function split_by_intent(array $keywords): array
    {
        $buckets = [];
        foreach ($keywords as $kw) {
            $intent_raw = strtolower(trim($kw['intent'] ?? ''));
            $bucket_key = $this->normalise_intent($intent_raw);
            $buckets[$bucket_key][] = $kw;
        }
        return $buckets;
    }

    /**
     * Map various Semrush intent strings to one of 4 canonical buckets.
     * Semrush exports intent as comma-separated, e.g. "Commercial, Informational"
     */
    private function normalise_intent(string $raw): string
    {
        if (empty($raw)) return 'other';

        // Semrush often exports as CSV list — take first token
        $parts = preg_split('/[,;\/\|]+/', $raw);
        $primary = strtolower(trim($parts[0] ?? ''));

        if (strpos($primary, 'info') !== false)        return 'informational';
        if (strpos($primary, 'nav') !== false)         return 'navigational';
        if (strpos($primary, 'transact') !== false)    return 'transactional';
        if (strpos($primary, 'commerc') !== false)     return 'commercial';
        return 'other';
    }

    /**
     * Greedy single-linkage clustering within one intent bucket.
     * Two keywords are in the same cluster if their token-overlap ≥ SIMILARITY_THRESHOLD.
     */
    private function cluster_bucket(array $keywords, string $intent_label, int $start_index): array
    {
        $clusters    = [];
        $assigned    = [];

        foreach ($keywords as $i => $kw_a) {
            if (isset($assigned[$i])) continue;

            $tokens_a = $this->tokenize($kw_a['keyword']);
            $cluster  = [$kw_a];
            $assigned[$i] = true;

            foreach ($keywords as $j => $kw_b) {
                if ($i === $j || isset($assigned[$j])) continue;

                $tokens_b  = $this->tokenize($kw_b['keyword']);
                $similarity = $this->overlap_similarity($tokens_a, $tokens_b);

                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $cluster[]    = $kw_b;
                    $assigned[$j] = true;
                }
            }

            // Generate a human-readable cluster name from the highest-volume keyword's tokens
            usort($cluster, fn($a, $b) => (int)$b['volume'] - (int)$a['volume']);
            $cluster_name = $this->make_cluster_name($cluster[0]['keyword'], $intent_label, $start_index);

            $clusters[] = [
                'name'    => $cluster_name,
                'intent'  => $intent_label,
                'keywords' => $cluster,
                'volume'  => array_sum(array_column($cluster, 'volume')),
            ];
            $start_index++;
        }

        return ['clusters' => $clusters, 'next_index' => $start_index];
    }

    /**
     * Tokenize a keyword string: lowercase, remove stop-words, return unique tokens.
     */
    private function tokenize(string $keyword): array
    {
        $kw    = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $keyword));
        $words = preg_split('/\s+/', trim($kw), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn($w) => !in_array($w, self::$stopwords));
        return array_unique(array_values($words));
    }

    /**
     * Sørensen-Dice-like token overlap (2|A∩B| / (|A|+|B|))
     * Returns 0..1. Identical → 1, no common token → 0.
     */
    private function overlap_similarity(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0.0;
        $intersection = count(array_intersect($a, $b));
        return (2.0 * $intersection) / (count($a) + count($b));
    }

    /**
     * Build a clean, readable cluster name from the pillar keyword.
     */
    private function make_cluster_name(string $pillar_keyword, string $intent, int $index): string
    {
        // Capitalise each word, strip common modifiers
        $clean = ucwords(strtolower(trim($pillar_keyword)));
        // Keep name short — first 5 words only
        $words = explode(' ', $clean);
        if (count($words) > 5) {
            $words = array_slice($words, 0, 5);
            $clean = implode(' ', $words) . '...';
        }
        return $clean;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // QUERY HELPERS (used by AJAX / UI)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get all saved clusters with their keywords (post-run).
     *
     * @return array  Associative array keyed by cluster_group name.
     */
    public static function get_saved_clusters(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_keywords';

        $rows = $wpdb->get_results(
            "SELECT id, keyword, intent, volume, kd, cluster_group, is_pillar
             FROM $table
             WHERE cluster_group != '' AND cluster_group IS NOT NULL
             ORDER BY cluster_group ASC, is_pillar DESC, volume DESC",
            ARRAY_A
        );

        $clusters = [];
        foreach ($rows as $row) {
            $g = $row['cluster_group'];
            if (!isset($clusters[$g])) {
                $clusters[$g] = ['name' => $g, 'keywords' => [], 'pillar' => null, 'volume' => 0];
            }
            $clusters[$g]['keywords'][] = $row;
            $clusters[$g]['volume']    += (int)$row['volume'];
            if ($row['is_pillar']) {
                $clusters[$g]['pillar'] = $row;
            }
        }

        // Sort clusters by combined volume descending
        uasort($clusters, fn($a, $b) => $b['volume'] - $a['volume']);

        return $clusters;
    }

    /**
     * Get keywords that are not yet assigned to any cluster.
     */
    public static function get_unclustered(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_keywords';
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE cluster_group = '' OR cluster_group IS NULL ORDER BY volume DESC",
            ARRAY_A
        );
    }

    /**
     * Get Content-Gap report: clusters whose pillar keyword has no matching post.
     * Matches by checking if any published post title contains the pillar keyword (case-insensitive).
     */
    public static function get_content_gaps(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ail_keywords';

        $pillars = $wpdb->get_results(
            "SELECT id, keyword, cluster_group, volume, intent FROM $table WHERE is_pillar = 1 ORDER BY volume DESC",
            ARRAY_A
        );

        $gaps   = [];
        foreach ($pillars as $pillar) {
            $kw = esc_sql($pillar['keyword']);
            $match = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_status = 'publish'
                   AND post_type IN ('post','page')
                   AND (post_title LIKE %s OR post_name LIKE %s)
                 LIMIT 1",
                '%' . $wpdb->esc_like($pillar['keyword']) . '%',
                '%' . $wpdb->esc_like(sanitize_title($pillar['keyword'])) . '%'
            ));
            if (!$match) {
                $gaps[] = $pillar;
            }
        }
        return $gaps;
    }

    /**
     * Map clusters to existing posts (by pillar keyword ↔ post title / slug match).
     *
     * @return array  ['cluster_name' => ['pillar' => ..., 'post' => WP_Post|null, 'spokes' => [...] ]]
     */
    public static function get_cluster_post_map(): array
    {
        $clusters = self::get_saved_clusters();
        $map      = [];
        foreach ($clusters as $name => $cluster) {
            $matched_post = null;
            if (!empty($cluster['pillar'])) {
                $pillar_kw = $cluster['pillar']['keyword'];
                // Try exact slug first, then partial title
                $matched_post = get_page_by_path(sanitize_title($pillar_kw), OBJECT, ['post', 'page']);
                if (!$matched_post) {
                    global $wpdb;
                    $row = $wpdb->get_row($wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts}
                         WHERE post_status = 'publish'
                           AND post_type IN ('post','page')
                           AND post_title LIKE %s
                         ORDER BY post_date DESC LIMIT 1",
                        '%' . $wpdb->esc_like($pillar_kw) . '%'
                    ));
                    if ($row) {
                        $matched_post = get_post($row->ID);
                    }
                }
            }

            $spokes = array_filter($cluster['keywords'], fn($k) => !$k['is_pillar']);
            $map[$name] = [
                'pillar' => $cluster['pillar'],
                'post'   => $matched_post,
                'spokes' => array_values($spokes),
                'volume' => $cluster['volume'],
                'intent' => !empty($cluster['keywords']) ? $cluster['keywords'][0]['intent'] : '',
            ];
        }
        return $map;
    }
}
