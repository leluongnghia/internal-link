<?php
/**
 * Provide a admin area view for Orphaned Posts
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */

global $wpdb;
$table_link_stats = $wpdb->prefix . 'ail_link_stats';

// Get total indexed posts to check if cron is running
$total_indexed = $wpdb->get_var("SELECT COUNT(*) FROM $table_link_stats");

// Get Orphaned Posts (Inbound = 0)
// We join with wp_posts to make sure the post still exists and is published
$orphaned_posts = $wpdb->get_results("
    SELECT s.post_id, p.post_title, p.post_date 
    FROM $table_link_stats s
    INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
    WHERE s.inbound_internal_links = 0 
    AND p.post_status = 'publish' 
    AND p.post_type = 'post'
    ORDER BY p.post_date DESC
    LIMIT 100
");
?>

<div class="ail-wrap wrap">
    <h1>Orphaned Posts Dashboard</h1>
    <p>This report highlights posts that have <strong>0 incoming internal links</strong>. These posts are "orphaned" and
        are harder for users and search engines to find.</p>

    <!-- Scanner Status -->
    <div class="ail-card" style="margin-bottom: 20px;">
        <h2 class="ail-card-header">Link Indexer Status</h2>
        <div style="padding: 15px;">
            <p>Total posts indexed: <strong>
                    <?php echo esc_html($total_indexed); ?>
                </strong></p>
            <p class="ail-help-text">The indexer runs in the background. If you just installed the update, please wait a
                few hours or click to force run.</p>
            <button type="button" id="ail-force-index-btn" class="ail-btn ail-btn-secondary">
                <span class="dashicons dashicons-update" style="margin-top:3px;"></span> Run Indexer Now
            </button>
            <span id="ail-index-spinner" class="spinner" style="float:none; margin-top:0;"></span>
            <div id="ail-index-result" style="margin-top: 10px;"></div>
        </div>
    </div>

    <!-- The List -->
    <div class="ail-card">
        <h2 class="ail-card-header">Orphaned Posts (
            <?php echo count($orphaned_posts); ?> shown)
        </h2>
        <?php if (empty($orphaned_posts)): ?>
            <div style="padding: 15px;">
                <p>Great! No orphaned posts found. All your posts have at least one internal link pointing to them (or the
                    indexer hasn't run yet).</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 40%">Post Title</th>
                        <th style="width: 15%">Date</th>
                        <th style="width: 45%">Action (One-Click Setup)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orphaned_posts as $post): ?>
                        <tr id="orphan-row-<?php echo esc_attr($post->post_id); ?>">
                            <td>
                                <strong><a href="<?php echo esc_url(get_edit_post_link($post->post_id)); ?>" target="_blank">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a></strong>
                                <div class="row-actions">
                                    <span class="view"><a href="<?php echo esc_url(get_permalink($post->post_id)); ?>"
                                            target="_blank">View</a></span>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($post->post_date))); ?>
                            </td>
                            <td>
                                <button type="button" class="ail-btn ail-btn-primary ail-auto-inbound-btn"
                                    data-id="<?php echo esc_attr($post->post_id); ?>"
                                    data-nonce="<?php echo esc_attr(wp_create_nonce('ail_auto_inbound_' . $post->post_id)); ?>">
                                    ✨ Auto-Link (AI)
                                </button>
                                <button type="button" class="ail-btn ail-btn-secondary ail-suggest-inbound-btn"
                                    data-id="<?php echo esc_attr($post->post_id); ?>"
                                    title="View Manual Suggestions (Coming Soon)" disabled>
                                    View Suggestions
                                </button>
                                <span class="spinner orphan-spinner-<?php echo esc_attr($post->post_id); ?>"
                                    style="float:none; margin-top:0;"></span>
                                <div class="orphan-result-<?php echo esc_attr($post->post_id); ?>"
                                    style="margin-top: 5px; font-size: 13px;"></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        // Force Indexer
        $('#ail-force-index-btn').on('click', function () {
            var btn = $(this);
            var spinner = $('#ail-index-spinner');
            var res = $('#ail-index-result');

            btn.prop('disabled', true);
            spinner.addClass('is-active');

            $.post(ajaxurl, {
                action: 'ail_force_link_index',
                nonce: '<?php echo esc_js(wp_create_nonce('ail_force_link_index')); ?>'
        }, function (response) {
                spinner.removeClass('is-active');
                btn.prop('disabled', false);
                if (response.success) {
                    res.html('<span style="color:var(--ail-success)">' + response.data.message + ' Page will reload.</span>');
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    res.html('<span style="color:var(--ail-danger)">' + (response.data || 'Error') + '</span>');
                }
            }).fail(function () {
                spinner.removeClass('is-active');
                btn.prop('disabled', false);
                res.html('<span style="color:var(--ail-danger)">Request failed.</span>');
            });
        });

        // Auto-Link (One Click Setup)
        $('.ail-auto-inbound-btn').on('click', function () {
            var btn = $(this);
            var id = btn.data('id');
            var nonce = btn.data('nonce');
            var spinner = $('.orphan-spinner-' + id);
            var res = $('.orphan-result-' + id);
            var row = $('#orphan-row-' + id);

            btn.prop('disabled', true);
            spinner.addClass('is-active');
            res.html('AI Brain is reading candidates...');

            $.post(ajaxurl, {
                action: 'ail_auto_inbound',
                post_id: id,
                nonce: nonce
            }, function (response) {
                spinner.removeClass('is-active');
                if (response.success) {
                    res.html('<span style="color:var(--ail-success)">✅ ' + response.data.message + '</span>');
                    // Optional: hide row if successful after delay
                    row.css('background-color', '#e5f5ea');
                    setTimeout(function () {
                        row.fadeOut();
                    }, 2000);
                } else {
                    btn.prop('disabled', false);
                    res.html('<span style="color:var(--ail-danger)">❌ ' + (response.data || 'Failed') + '</span>');
                }
            }).fail(function () {
                spinner.removeClass('is-active');
                btn.prop('disabled', false);
                res.html('<span style="color:var(--ail-danger)">❌ Request failed.</span>');
            });
        });
    });
</script>