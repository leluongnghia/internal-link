<?php
/**
 * Provide a admin area view for the Click Stats
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */

global $wpdb;
$table_click_log = $wpdb->prefix . 'ail_click_log';

// Basic Pagination
$per_page = 50;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($paged - 1) * $per_page;

// Count Total
$total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_click_log");
$total_pages = ceil($total_items / $per_page);

// Get Data
$clicks = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_click_log ORDER BY clicked_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));
?>
<div class="wrap">
    <h1>Link Click Statistics</h1>
    <p>See exactly which internal links are driving traffic across your site in real-time.</p>

    <!-- Stat boxes -->
    <div style="display:flex; gap:20px; margin: 20px 0;">
        <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; text-align:center; min-width: 150px;">
            <h3>Total Clicks (30d)</h3>
            <p style="font-size: 2em; font-weight: bold; margin:0;">
                <?php echo number_format($total_items); ?>
            </p>
        </div>
        <!-- Add more stats like unique sources, most clicked target -->
    </div>

    <!-- Data Table -->
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Source Page</th>
                <th>Target Page (URL)</th>
                <th>Anchor Text</th>
                <th>Visitor IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clicks)): ?>
                <tr>
                    <td colspan="5">No clicks tracked yet. The tracker is active and waiting for visitors!</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clicks as $click): ?>
                    <tr>
                        <td><strong>
                                <?php echo esc_html($click->clicked_at); ?>
                            </strong></td>
                        <td>
                            <?php
                            $src_post = get_post($click->source_post_id);
                            echo $src_post ? esc_html($src_post->post_title) : "ID: " . esc_html($click->source_post_id);
                            ?>
                            <div class="row-actions">
                                <a href="<?php echo get_edit_post_link($click->source_post_id); ?>" target="_blank">Edit</a> |
                                <a href="<?php echo get_permalink($click->source_post_id); ?>" target="_blank">View</a>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($click->target_post_id) {
                                $tgt_post = get_post($click->target_post_id);
                                echo $tgt_post ? esc_html($tgt_post->post_title) : esc_html($click->link_url);
                            } else {
                                echo esc_html($click->link_url);
                            }
                            ?>
                            <div class="row-actions">
                                <a href="<?php echo esc_url($click->link_url); ?>" target="_blank">Visit URL</a>
                            </div>
                        </td>
                        <td>"<i>
                                <?php echo esc_html($click->anchor_text); ?>
                            </i>"</td>
                        <td>
                            <?php echo esc_html($click->visitor_ip); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $paged
                    ));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>