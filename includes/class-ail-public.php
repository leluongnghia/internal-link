<?php

/**
 * Public facing functionality
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_Public
{

    /**
     * Hook into save_post to auto-inject links.
     */
    public function auto_inject_on_save($post_id)
    {
        // Check if auto-link is enabled
        if (!get_option('ail_auto_on_save')) {
            return;
        }

        // Verify nonce (optional validation if coming from specific form, but generic save_post needs checks)
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check post type (only posts for now)
        if (get_post_type($post_id) !== 'post') {
            return;
        }

        // Prevent infinite loop
        if (get_post_meta($post_id, '_ail_processed', true)) {
            return;
        }

        // Get Content
        $post = get_post($post_id);
        $content = $post->post_content;

        if (empty($content)) {
            return;
        }

        // Check background mode
        if (get_option('ail_background_mode')) {
            // Schedule async event
            wp_schedule_single_event(time() + 5, 'ail_async_process_start', array($post_id));
            // Mark as processed processed here? No, we need it to run later.
            // But we must ensure the scheduled event doesn't trigger loop problems.
            // Actually, the scheduled event calls `ail_process_content` and then `wp_update_post`.
            // `wp_update_post` triggers `save_post`, which triggers this function again.
            // So we DO need to mark `_ail_processed`. 
            // BUT wait, if we mark it here, the background job might see it?
            // No, `_ail_processed` check is correct.
            // Let's rely on standard logic.
            // BUT, if we return early, we don't set `_ail_processed`.
            // So if user saves again, it triggers another schedule. That's fine.

            // To prevent the background job (which calls wp_update_post) from triggering a NEW background job:
            // The background job should set `_ail_processed` before saving.
            return;
        }

        // Mark as processed immediately to avoid loop if update_post triggers again
        update_post_meta($post_id, '_ail_processed', true);

        // Inject Links
        if (function_exists('ail_process_content')) {
            $new_content = ail_process_content($content, $post_id);

            // Validate if content actually changed to avoid unnecessary updates
            if ($new_content && $new_content !== $content) {
                // Unhook this function to prevent infinite loop
                remove_action('save_post', array($this, 'auto_inject_on_save'));

                // Update the post
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $new_content
                ));

                // Re-hook (optional, not strictly needed for this request lifecycle)
                add_action('save_post', array($this, 'auto_inject_on_save'));
            }
        }

        // Clean up flag after update (optional, depend if we want to run only once ever or every save)
        delete_post_meta($post_id, '_ail_processed');
    }

    /**
     * Handle Background Process
     */
    public function process_background_event($post_id)
    {
        // Prevent infinite loop if this triggers save_post again
        if (get_post_meta($post_id, '_ail_processed', true)) {
            return;
        }
        update_post_meta($post_id, '_ail_processed', true);

        $post = get_post($post_id);
        if (!$post)
            return;

        if (function_exists('ail_process_content')) {
            $content = $post->post_content;
            $new_content = ail_process_content($content, $post_id);

            if ($new_content && $new_content !== $content) {
                // We need to unhook save_post for this update? 
                // Because we are in a cron job, `auto_inject_on_save` might still be hooked.
                // But `auto_inject_on_save` checks for `_ail_processed` at the top!
                // So it will return early.
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $new_content
                ));
            }
        }

        // Clean up
        delete_post_meta($post_id, '_ail_processed');
    }

}
