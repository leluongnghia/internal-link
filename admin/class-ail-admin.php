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
        // wp_enqueue_style( 'ail_admin_css', plugin_dir_url( __FILE__ ) . 'css/ail-admin.css', array(), AIL_VERSION, 'all' );
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
        register_setting('ail_options_group', 'ail_auto_on_save');
        register_setting('ail_options_group', 'ail_background_mode');

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

}
