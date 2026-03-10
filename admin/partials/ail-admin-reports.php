<?php
/**
 * Provide a admin area view for the reports
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */
?>

<div class="wrap">
    <h1>Internal Link Reports</h1>
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'ail_logs';

    // Check if table exists to avoid errors on first load before dbDelta runs
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo '<div class="notice notice-warning"><p>Logs table does not exist yet. Please deactivate and reactivate the plugin.</p></div>';
    } else {
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
        ?>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th>Post</th>
                    <th>Provider</th>
                    <th>Model</th>
                    <th>Links Created</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <?php echo esc_html($row->id); ?>
                            </td>
                            <td>
                                <?php
                                $post_title = get_the_title($row->post_id);
                                echo $post_title ? '<a href="' . get_edit_post_link($row->post_id) . '">' . esc_html($post_title) . '</a>' : 'Post #' . $row->post_id;
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->provider); ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->model); ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->links_created); ?>
                            </td>
                            <td>
                                <?php echo esc_html($row->created_at); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    ?>
</div>