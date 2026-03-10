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

}
