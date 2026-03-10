<?php

/**
 * Provide a admin area view for the Reports page.
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_logs = $wpdb->prefix . 'ail_logs';
$table_anchors = $wpdb->prefix . 'ail_anchor_log';

// Fetch recent injection events
$recent_logs = $wpdb->get_results("SELECT * FROM $table_logs ORDER BY created_at DESC LIMIT 50");

// Fetch top anchored URLs
$top_anchors = $wpdb->get_results("SELECT * FROM $table_anchors ORDER BY usage_count DESC LIMIT 20");

?>

<div class="ail-wrap wrap">
    <h1>AI Internal Linker - Reports & Statistics</h1>

    <!-- Batch Process Dashboard -->
    <?php
    $queue_json = get_option('ail_batch_queue', '[]');
    $queue = json_decode($queue_json, true);
    $total_in_queue = is_array($queue) ? count($queue) : 0;
    $pointer = (int) get_option('ail_batch_queue_pointer', 0);
    $percent = $total_in_queue > 0 ? min(100, round(($pointer / $total_in_queue) * 100)) : 0;
    ?>
    <div class="ail-card" style="margin-top: 24px; padding: 24px;">
        <h2 class="ail-card-header" style="margin-bottom: 8px;">Batch Processing Status</h2>
        <p class="ail-help-text" style="margin-bottom: 20px;">Monitors the background operation that injects internal links automatically into old posts.</p>
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <div style="font-size: 14px; font-weight: 500; color: var(--ail-text-primary);">
                Processed: <span id="ail-pointer-val" style="color: var(--ail-accent);"><?php echo $pointer; ?></span> / <span id="ail-total-val"><?php echo $total_in_queue; ?></span>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="ail-progress-panel" id="ail-batch-progress-wrapper" style="<?php echo ($total_in_queue == 0) ? 'opacity: 0.5;' : ''; ?>">
            <div class="ail-progress-count" id="ail-batch-progress-bar" style="width: <?php echo esc_attr($percent); ?>%;">
                <?php echo esc_html($percent); ?>%
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="button" id="ail-force-batch-btn" class="ail-button ail-button-primary">Force Run Batch Now</button>
            <div id="ail-batch-message" style="margin-top: 10px; font-size: 13px;"></div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        
        function processBatchQueue() {
            var btn = $('#ail-force-batch-btn');
            var msg = $('#ail-batch-message');
            var wrapper = $('#ail-batch-progress-wrapper');
            var bar = $('#ail-batch-progress-bar');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ail_force_batch_process',
                    nonce: '<?php echo wp_create_nonce("ail_force_batch_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var p = response.data.pointer;
                        var t = response.data.total;
                        var finished = response.data.finished;
                        
                        var newPercent = (t > 0) ? Math.min(100, Math.round((p / t) * 100)) : 0;
                        bar.css('width', newPercent + '%').text(newPercent + '%');
                        $('#ail-pointer-val').text(p);
                        $('#ail-total-val').text(t);
                        
                        if (finished) {
                            wrapper.removeClass('active');
                            btn.prop('disabled', false).text('Force Run Batch Now');
                            msg.html('<span style="color:var(--ail-success);">Batch processing complete! Loading new stats...</span>');
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            msg.html('<span style="color:var(--ail-info);">Running batch... Processed ' + p + ' / ' + t + '</span>');
                            // Trigger next batch chunk immediately
                            processBatchQueue();
                        }
                    } else {
                        wrapper.removeClass('active');
                        btn.prop('disabled', false).text('Force Run Batch Now');
                        msg.html('<span style="color:var(--ail-error);">' + response.data + '</span>');
                    }
                },
                error: function() {
                    wrapper.removeClass('active');
                    btn.prop('disabled', false).text('Force Run Batch Now');
                    msg.html('<span style="color:var(--ail-error);">Server error occurred. Automatically retrying in 3s...</span>');
                    setTimeout(processBatchQueue, 3000);
                }
            });
        }
    
        $('#ail-force-batch-btn').on('click', function() {
            var btn = $(this);
            var msg = $('#ail-batch-message');
            var wrapper = $('#ail-batch-progress-wrapper');
            
            btn.prop('disabled', true).text('Processing...');
            wrapper.addClass('active'); // Start animation
            msg.html('<span style="color:var(--ail-info);">Starting continuous batch process...</span>');
            
            processBatchQueue();
        });
    });
    </script>

    <div class="ail-stats-dashboard" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-top: 24px;">
        
        <!-- Top Anchors Section -->
        <div class="ail-card">
            <h2 class="ail-card-header">Top Anchor Texts Used</h2>
            <p class="ail-help-text" style="margin-bottom: 16px;">Monitors how many times an exact phrase points to a specific URL (preventing keyword stuffing).</p>
            <table class="ail-table">
                <thead>
                    <tr>
                        <th>Exact Phrase</th>
                        <th>Target URL</th>
                        <th>Usage Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_anchors)) : ?>
                        <?php foreach ($top_anchors as $anchor) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($anchor->exact_phrase); ?></strong></td>
                                <td><a href="<?php echo esc_url($anchor->target_url); ?>" target="_blank" style="color: var(--ail-info); text-decoration: none;"><?php echo esc_url($anchor->target_url); ?></a></td>
                                <td><span style="background: var(--ail-bg-elevated); color: var(--ail-accent); border: 1px solid var(--ail-border); border-radius: 12px; padding: 2px 8px; font-weight: 600; font-family: 'JetBrains Mono', monospace; font-size: 12px;"><?php echo intval($anchor->usage_count); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--ail-text-secondary); padding: 20px;">No anchor data recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Runs Section -->
        <div class="ail-card">
            <h2 class="ail-card-header">Recent Linking Events</h2>
            <p class="ail-help-text" style="margin-bottom: 16px;">Displays the latest background sweeps or editor manual runs.</p>
            <table class="ail-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Source Post</th>
                        <th>Action By</th>
                        <th>Links Injected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_logs)) : ?>
                        <?php foreach ($recent_logs as $log) : ?>
                            <tr>
                                <td style="color: var(--ail-text-secondary); font-size: 13px;"><?php echo esc_html(get_date_from_gmt($log->created_at, 'M j, Y H:i')); ?></td>
                                <td><a href="<?php echo get_edit_post_link($log->post_id); ?>" target="_blank" style="color: var(--ail-text-primary); font-weight: 500; text-decoration: none;"><?php echo get_the_title($log->post_id); ?></a></td>
                                <td><span style="font-size: 12px; border: 1px solid var(--ail-border); border-radius: 4px; padding: 2px 6px; background: var(--ail-bg-canvas); color: var(--ail-text-secondary);"><?php echo esc_html(ucfirst($log->provider) . ' (' . $log->model . ')'); ?></span></td>
                                <td><strong style="color: var(--ail-success); font-family: 'JetBrains Mono', monospace;">+<?php echo intval($log->links_created); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--ail-text-secondary); padding: 20px;">No linking events recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>