<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin
 */

class AIL_Admin
{

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style('ail_admin_css', plugin_dir_url(__FILE__) . 'css/ail-admin.css', array(), AIL_VERSION, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script('ail_admin_js', plugin_dir_url(__FILE__) . 'js/ail-admin.js', array('jquery'), AIL_VERSION, true);
    }

    /**
     * Add menu page
     */
    public function add_plugin_admin_menu()
    {
        add_menu_page(
            'Internal Links',
            'Internal Links',
            'manage_options',
            'ai-internal-links',
            array($this, 'display_plugin_settings_page'),
            'dashicons-admin-links',
            80
        );

        // Add Reports Submenu
        add_submenu_page(
            'ai-internal-links',
            'Reports',
            'Reports',
            'manage_options',
            'ai-internal-links-reports',
            array($this, 'display_plugin_reports_page')
        );

        // Add Orphaned Posts Submenu
        add_submenu_page(
            'ai-internal-links',
            'Orphaned Posts',
            'Orphaned Posts',
            'manage_options',
            'ai-internal-links-orphaned',
            array($this, 'display_plugin_orphaned_page')
        );

        // Add Keyword Clusters Submenu
        add_submenu_page(
            'ai-internal-links',
            'Keyword Clusters',
            '🔮 Keyword Clusters',
            'manage_options',
            'ai-internal-links-clusters',
            array($this, 'display_plugin_clusters_page')
        );
    }

    /**
     * Display settings page
     */
    public function display_plugin_settings_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/ail-admin-display.php';
    }

    /**
     * Display reports page
     */
    public function display_plugin_reports_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/ail-admin-reports.php';
    }

    /**
     * Display orphaned posts page
     */
    public function display_plugin_orphaned_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/ail-admin-orphaned.php';
    }

    /**
     * Display keyword clusters page
     */
    public function display_plugin_clusters_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/ail-admin-clusters.php';
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('ail_options_group', 'ail_api_provider');

        // Provider Specific Settings
        register_setting('ail_options_group', 'ail_openai_key');
        register_setting('ail_options_group', 'ail_openai_model');

        register_setting('ail_options_group', 'ail_gemini_key');
        register_setting('ail_options_group', 'ail_gemini_model');

        register_setting('ail_options_group', 'ail_grok_key');
        register_setting('ail_options_group', 'ail_grok_model');

        // General Settings
        register_setting('ail_options_group', 'ail_selected_skill');
        register_setting('ail_options_group', 'ail_link_source');
        register_setting('ail_options_group', 'ail_max_links');
        register_setting('ail_options_group', 'ail_max_anchor_repeat');

        // Automation / Trigger Settings
        register_setting('ail_options_group', 'ail_auto_on_save');
        register_setting('ail_options_group', 'ail_background_mode');

        // Batch Settings
        register_setting('ail_options_group', 'ail_batch_enabled');
        register_setting('ail_options_group', 'ail_batch_size');
        register_setting('ail_options_group', 'ail_sweep_batch_size');
        register_setting('ail_options_group', 'ail_batch_frequency');

        // GitHub Updater Settings
        register_setting('ail_options_group', 'ail_github_updater_url');
        register_setting('ail_options_group', 'ail_github_updater_token');

        // Legacy (might need migration later, but keeping for now to avoid breaking if referenced)
        register_setting('ail_options_group', 'ail_api_key');
        register_setting('ail_options_group', 'ail_api_model');
    }

    /**
     * Add "Run AI Linker" to post row actions
     */
    public function add_manual_run_action($actions, $post)
    {
        if (current_user_can('edit_post', $post->ID)) {
            $nonce = wp_create_nonce('ail_manual_run_' . $post->ID);
            $actions['ail_manual_run'] = '<a href="#" class="ail-run-linker" data-id="' . $post->ID . '" data-nonce="' . $nonce . '">✨ Run AI Linker</a>';
        }
        return $actions;
    }

    /**
     * Handle AJAX manual run
     */
    public function ajax_handle_manual_run()
    {
        check_ajax_referer('ail_manual_run_' . $_POST['post_id'], 'nonce');

        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        if (!function_exists('ail_process_content')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'internal-links.php';
        }

        // Process content
        $content = $post->post_content;
        $new_content = ail_process_content($content, $post_id);

        if ($new_content && $new_content !== $content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $new_content
            ));
            wp_send_json_success('Links injected successfully!');
        } else {
            wp_send_json_error('No links injected (AI returned same content or failed). Check Reports.');
        }
    }

    /**
     * Add Interactive Scanner Meta Box
     */
    public function add_interactive_scanner_meta_box()
    {
        add_meta_box(
            'ail_interactive_scanner',
            'AI Internal Link Scanner',
            array($this, 'render_interactive_scanner_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    /**
     * Render the Meta Box UI
     */
    public function render_interactive_scanner_meta_box($post)
    {
        $nonce = wp_create_nonce('ail_interactive_scan_' . $post->ID);
?>
        <div class="ail-scanner-container">
            <p>Scan your content to get real-time internal link suggestions from AI.</p>
            <button type="button" class="button button-primary ail-scan-btn" data-id="<?php echo esc_attr($post->ID); ?>"
                data-nonce="<?php echo esc_attr($nonce); ?>">
                <span class="dashicons dashicons-search" style="margin-top: 3px;"></span> Scan for Links
            </button>
            <div class="ail-scan-results" style="margin-top: 15px; display: none;">
                <!-- Results will be injected here via JS -->
            </div>
            <span class="spinner ail-scan-spinner"></span>
        </div>
        <style>
            .ail-scan-results ul {
                list-style-type: disc;
                margin-left: 20px;
            }

            .ail-scan-results li {
                margin-bottom: 5px;
            }

            .ail-suggestion-phrase {
                font-weight: bold;
            }
        </style>
<?php
    }

    /**
     * AJAX handler for the Interactive Scanner
     */
    public function ajax_interactive_scan()
    {
        check_ajax_referer('ail_interactive_scan_' . $_POST['post_id'], 'nonce');

        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }

        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        if (empty($content)) {
            // Fallback to saved content if no editor content passed
            $post = get_post($post_id);
            $content = $post ? $post->post_content : '';
        }

        if (empty($content)) {
            wp_send_json_error('No content provided for scanning.');
        }

        if (!class_exists('AIL_Injector')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-injector.php';
        }

        $injector = new AIL_Injector();

        // Expose a public method or use reflection, or rebuild prompt here.
        // For simplicity, let's temporarily mock the return or we need to adjust Injector to expose a suggest_links method.
        // We will add a suggest_links method to Injector.

        if (method_exists($injector, 'suggest_links')) {
            $suggestions = $injector->suggest_links($content, $post_id);
            if (!empty($suggestions)) {
                wp_send_json_success($suggestions);
            } else {
                wp_send_json_error('No relevant links found for this content.');
            }
        } else {
            wp_send_json_error('Injector suggest method not ready.');
        }
    }

    /**
     * AJAX handler to force run batch process
     */
    public function ajax_force_batch_process()
    {
        check_ajax_referer('ail_force_batch_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!class_exists('AIL_Sweeper')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-sweeper.php';
        }

        $sweeper = new AIL_Sweeper();

        // Temporarily clear the lock just to make sure force run can execute
        delete_transient('ail_batch_processing_lock');

        // Execute batch process
        $sweeper->run_batch_process();

        $queue_json = get_option('ail_batch_queue', '[]');
        $queue = json_decode($queue_json, true);
        $total = is_array($queue) ? count($queue) : 0;
        $pointer = (int) get_option('ail_batch_queue_pointer', 0);
        $finished = ($pointer >= $total && $total > 0);

        wp_send_json_success(array(
            'message' => 'Batch step completed.',
            'pointer' => $pointer,
            'total' => $total,
            'finished' => $finished
        ));
    }

    /**
     * AJAX handler to force-check GitHub for a new plugin version.
     * Clears cached transients so it always hits the GitHub API fresh.
     */
    public function ajax_force_update_check()
    {
        if (!current_user_can('manage_options') || !check_ajax_referer('ail_force_update', 'nonce', false)) {
            wp_send_json_error('Permission denied.');
        }

        // ── 1. Read config ────────────────────────────────────────
        $github_url = get_option('ail_github_updater_url', '');
        $token = get_option('ail_github_updater_token', '');

        if (empty($github_url)) {
            wp_send_json_error('GitHub Repository URL is not configured in plugin settings.');
        }

        // Parse owner/repo from URL  e.g. https://github.com/leluongnghia/internal-link
        $parts = array_filter(explode('/', rtrim(parse_url($github_url, PHP_URL_PATH), '/')));
        $parts = array_values($parts);
        $username = $parts[0] ?? '';
        $repo = $parts[1] ?? '';

        if (empty($username) || empty($repo)) {
            wp_send_json_error('Invalid GitHub URL format. Expected: https://github.com/owner/repo');
        }

        // ── 2. Clear caches ───────────────────────────────────────
        delete_site_transient('update_plugins');
        $transient_key = 'ail_github_latest_release_' . md5($username . $repo);
        delete_transient($transient_key);

        // ── 3. Fetch latest release from GitHub API ───────────────
        $api_url = "https://api.github.com/repos/{$username}/{$repo}/releases/latest";
        $args = array(
            'timeout' => 20,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/AIL-Plugin',
            ),
        );

        if (!empty($token)) {
            $args['headers']['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error('GitHub API error: ' . $response->get_error_message());
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            wp_send_json_error('GitHub returned HTTP ' . wp_remote_retrieve_response_code($response) . '. Check the repo URL and token.');
        }

        $release = json_decode(wp_remote_retrieve_body($response));

        if (empty($release->tag_name)) {
            wp_send_json_error('No releases found on GitHub for this repository.');
        }

        // Cache for 12 hours
        set_transient($transient_key, $release, 12 * HOUR_IN_SECONDS);

        // ── 4. Compare with installed version ─────────────────────
        $current_version = AIL_VERSION;
        $latest_version = ltrim($release->tag_name, 'v');
        $has_update = version_compare($current_version, $latest_version, '<');

        // Force WordPress to refresh update transient
        wp_update_plugins();

        wp_send_json_success(array(
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'has_update' => $has_update,
            'release_url' => $release->html_url,
            'published_at' => $release->published_at,
            'message' => $has_update
                ? "🎉 New update available: v{$latest_version}! Go to Plugins → Update now."
                : "✅ Plugin is up to date (v{$current_version})",
        ));
    }

    /**
     * AJAX handler to force link indexer
     */
    public function ajax_force_link_index()
    {
        check_ajax_referer('ail_force_link_index', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!class_exists('AIL_Link_Indexer')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-link-indexer.php';
        }

        // Delete lock to force run
        delete_transient('ail_link_indexing_lock');

        // We run it directly. It has built-in execution limits but we'll try to run enough to make a difference
        $indexer = new AIL_Link_Indexer();
        $indexer->run_link_indexing();

        $pointer = (int) get_option('ail_link_index_queue_pointer', 0);
        $queue_json = get_option('ail_link_index_queue', '');
        $queue = json_decode($queue_json, true);
        $total = is_array($queue) ? count($queue) : 0;

        if ($total === 0 || $pointer >= $total) {
            wp_send_json_success(array('message' => 'Index complete!'));
        } else {
            wp_send_json_success(array('message' => "Indexing... ($pointer / $total). The process will continue in the background."));
        }
    }

    /**
     * AJAX handler for Auto-Inbound Link (One Click Setup)
     */
    public function ajax_auto_inbound()
    {
        check_ajax_referer('ail_auto_inbound_' . $_POST['post_id'], 'nonce');

        $target_post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $target_post_id)) {
            wp_send_json_error('Permission denied');
        }

        // 1. Get the orphaned post
        $target_post = get_post($target_post_id);
        if (!$target_post) {
            wp_send_json_error('Post not found');
        }

        if (!class_exists('AIL_Retriever')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-retriever.php';
        }
        if (!class_exists('AIL_Injector')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-injector.php';
        }

        $retriever = new AIL_Retriever();
        $injector = new AIL_Injector();

        // Let's find some OTHER posts that might be relevant to THIS orphaned post.
        // We can just use the retriever with the target post ID. The retriever excludes the post ID itself.
        // It brings back posts in the same category/tag etc. (Based on strategy).
        $candidates = $retriever->get_candidate_posts($target_post_id, 10); // get top 10

        if (empty($candidates)) {
            wp_send_json_error('No related posts found to scan. Add more content to the same category first.');
        }

        // Create the single candidate target for AI (The orphaned post)
        // AIL_Retriever has get_short_summary but it's private. Let's just build it manually.
        $target_summary = wp_trim_words(wp_strip_all_tags(mb_substr($target_post->post_content, 0, 400)), 15, '...');

        $orphaned_candidate = array(
            array(
                'id' => $target_post_id,
                'title' => $target_post->post_title,
                'url' => get_permalink($target_post_id),
                'summary' => $target_summary
            )
        );

        $links_created = 0;
        $processed = 0;

        // Loop through related posts and try to inject a link TO the orphaned post
        foreach ($candidates as $candidate_post) {
            if ($links_created >= 3) {
                break; // Stop after creating 3 inbound links for this post per click
            }

            $source_post_id = $candidate_post['id'];
            $source_post = get_post($source_post_id);
            if (!$source_post)
                continue;

            $content = $source_post->post_content;

            // To do this, we need a slightly modified injection approach.
            // But luckily, AIL_Injector::suggest_links or AIL_Injector internal logic can take any candidate list!
            // Let's directly call AI API using a custom prompt tailored for ONE target URL

            // Re-use injector, maybe use reflection or a simpler approach? Let's just process the content!
            // Wait, we can mock the environment by passing the orphaned post as the ONLY candidate to the AI.
            // But AIL_Injector::inject_links fetches candidates INSIDE the method. So we can't inject candidates directly.
            // We'll need a new method in Injector or we can write a custom prompt here. Let's add cross-link capacity.

            // Since Injector is self-contained and hardcoded to fetch candidates, let's call AI directly here for simplicity,
            // or add a method to Injector.

            // I'll add a method `inject_specific_links($content, $target_candidates, $source_post_id)` to AIL_Injector
            if (method_exists($injector, 'inject_specific_links')) {
                $new_content = $injector->inject_specific_links($content, $orphaned_candidate, $source_post_id);
                if ($new_content && $new_content !== $content) {
                    wp_update_post(array(
                        'ID' => $source_post_id,
                        'post_content' => $new_content
                    ));
                    $links_created++;

                    // Update stats
                    global $wpdb;
                    $table = $wpdb->prefix . 'ail_link_stats';
                    $wpdb->query("UPDATE $table SET inbound_internal_links = inbound_internal_links + 1 WHERE post_id = $target_post_id");
                }
            }
            $processed++;
        }

        if ($links_created > 0) {
            wp_send_json_success(array('message' => "Successfully injected $links_created inbound links from $processed related posts!"));
        } else {
            wp_send_json_error("AI analyzed $processed posts but couldn't find a natural context to link back to this post.");
        }
    }

    /**
     * AJAX handler to run the Keyword Clustering algorithm
     */
    public function ajax_run_clustering()
    {
        if (!current_user_can('manage_options') || !check_ajax_referer('ail_run_clustering', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        if (!class_exists('AIL_Keyword_Clusterer')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-keyword-clusterer.php';
        }

        $clusterer = new AIL_Keyword_Clusterer();
        $result    = $clusterer->run();

        if ($result['total'] === 0) {
            wp_send_json_error(array('message' => 'No keywords found. Please import keywords first.'));
        }

        wp_send_json_success(array(
            'message'       => sprintf(
                '✅ Clustering complete! Found <strong>%d clusters</strong> from %d keywords.',
                $result['cluster_count'],
                $result['total']
            ),
            'cluster_count' => $result['cluster_count'],
            'total'         => $result['total'],
        ));
    }

    /**
     * AJAX handler to delete all imported keywords (reset)
     */
    public function ajax_delete_keywords()
    {
        if (!current_user_can('manage_options') || !check_ajax_referer('ail_delete_keywords', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ail_keywords';
        $wpdb->query("TRUNCATE TABLE $table");

        wp_send_json_success(array('message' => 'All keywords deleted successfully.'));
    }

    /**
     * AJAX handler to return cluster data for the UI (JSON)
     */
    public function ajax_get_cluster_data()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        if (!class_exists('AIL_Keyword_Clusterer')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ail-keyword-clusterer.php';
        }

        $map  = AIL_Keyword_Clusterer::get_cluster_post_map();
        $gaps = AIL_Keyword_Clusterer::get_content_gaps();

        // Serialize for JSON (WP_Post objects are not serializable cleanly)
        $map_clean = [];
        foreach ($map as $name => $data) {
            $post_data = null;
            if ($data['post']) {
                $post_data = [
                    'ID'          => $data['post']->ID,
                    'title'       => $data['post']->post_title,
                    'url'         => get_permalink($data['post']->ID),
                    'edit_url'    => get_edit_post_link($data['post']->ID, 'raw'),
                ];
            }
            $map_clean[$name] = [
                'name'    => $name,
                'pillar'  => $data['pillar'],
                'spokes'  => $data['spokes'],
                'volume'  => $data['volume'],
                'intent'  => $data['intent'],
                'post'    => $post_data,
            ];
        }

        wp_send_json_success(array(
            'clusters' => array_values($map_clean),
            'gaps'     => $gaps,
        ));
    }

    /**
     * AJAX handler to import keywords from an Excel (.xlsx) file
     */
    public function ajax_import_keywords()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'No file uploaded.'));
        }

        $file = $_FILES['file'];

        // Allowed extensions
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx' && $ext !== 'csv') {
            wp_send_json_error(array('message' => 'Only .xlsx and .csv files are supported.'));
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libs/simplexlsx.php';
        global $wpdb;
        $table_keywords = $wpdb->prefix . 'ail_keywords';

        try {
            $imported_count = 0;
            $skipped_count = 0;

            if ($ext === 'xlsx') {
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                    $rows = $xlsx->rows();
                    $header = array_shift($rows);

                    foreach ($rows as $row) {
                        $keyword = isset($row[0]) ? sanitize_text_field($row[0]) : '';
                        $intent  = isset($row[1]) ? sanitize_text_field($row[1]) : '';
                        $volume  = isset($row[2]) ? intval(str_replace(',', '', $row[2])) : 0;
                        $kd      = isset($row[3]) ? floatval($row[3]) : 0.0;

                        if (empty($keyword)) continue;

                        $inserted = $wpdb->insert(
                            $table_keywords,
                            array(
                                'keyword' => $keyword,
                                'intent' => $intent,
                                'volume' => $volume,
                                'kd' => $kd
                            ),
                            array('%s', '%s', '%d', '%f')
                        );

                        if ($inserted) {
                            $imported_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                } else {
                    wp_send_json_error(array('message' => \Shuchkin\SimpleXLSX::parseError()));
                }
            } else if ($ext === 'csv') {
                $handle = fopen($file['tmp_name'], "r");
                if ($handle !== FALSE) {
                    $header = fgetcsv($handle);
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $keyword = isset($data[0]) ? sanitize_text_field($data[0]) : '';
                        $intent  = isset($data[1]) ? sanitize_text_field($data[1]) : '';
                        $volume  = isset($data[2]) ? intval(str_replace(',', '', $data[2])) : 0;
                        $kd      = isset($data[3]) ? floatval($data[3]) : 0.0;

                        if (empty($keyword)) continue;

                        $inserted = $wpdb->insert(
                            $table_keywords,
                            array(
                                'keyword' => $keyword,
                                'intent' => $intent,
                                'volume' => $volume,
                                'kd' => $kd
                            ),
                            array('%s', '%s', '%d', '%f')
                        );
                        if ($inserted) {
                            $imported_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                    fclose($handle);
                }
            }

            wp_send_json_success(array(
                'message' => sprintf('Imported %d keywords successfully. Skipped %d duplicates.', $imported_count, $skipped_count),
                'imported' => $imported_count,
                'duplicates' => $skipped_count
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}
