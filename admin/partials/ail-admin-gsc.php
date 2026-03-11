<?php
/**
 * Provide a admin area view for the Plugin GSC Opportunities
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */

if (!class_exists('AIL_GSC')) {
    require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-ail-gsc.php';
}

$opportunities = AIL_GSC::get_opportunity_keywords(30);
?>
<div class="wrap">
    <h1>Google Search Console Integration</h1>
    <p>Discover opportunity keywords. Upload your GSC query export (CSV) to identify queries with high impressions but
        low clicks.</p>

    <!-- Upload Form -->
    <div style="background:#fff; padding:15px; border:1px solid #ccd0d4; margin-bottom: 20px;">
        <h2>Import GSC Data</h2>
        <form id="ail-gsc-upload-form" enctype="multipart/form-data">
            <input type="file" name="gsc_csv" accept=".csv" required />
            <input type="hidden" name="action" value="ail_upload_gsc_csv" />
            <?php wp_nonce_field('ail_gsc_upload', 'nonce'); ?>
            <button type="submit" class="button button-primary">Upload CSV</button>
            <span class="spinner" id="ail-gsc-spinner"></span>
            <div id="ail-gsc-upload-msg" style="margin-top: 10px; font-weight: bold;"></div>
        </form>
    </div>

    <!-- Opportunities Table -->
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th>Keyword</th>
                <th>Impressions</th>
                <th>Clicks</th>
                <th>CTR (%)</th>
                <th>Position</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($opportunities)): ?>
                <tr>
                    <td colspan="6">No opportunity keywords found. Please upload a GSC export.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($opportunities as $opp): ?>
                    <tr>
                        <td><strong>
                                <?php echo esc_html($opp->keyword); ?>
                            </strong></td>
                        <td>
                            <?php echo number_format($opp->impressions); ?>
                        </td>
                        <td>
                            <?php echo number_format($opp->clicks); ?>
                        </td>
                        <td>
                            <?php echo esc_html($opp->ctr); ?>%
                        </td>
                        <td>
                            <?php echo esc_html($opp->position); ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=ai-internal-links-manual'); ?>" class="button">Add
                                target URL</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    jQuery(document).ready(function ($) {
        $('#ail-gsc-upload-form').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            $('#ail-gsc-spinner').addClass('is-active');
            $('#ail-gsc-upload-msg').html('').removeClass('error updated');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#ail-gsc-spinner').removeClass('is-active');
                    if (response.success) {
                        $('#ail-gsc-upload-msg').addClass('updated').html('<p>' + response.data.message + '</p>');
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        $('#ail-gsc-upload-msg').addClass('error').html('<p>' + response.data + '</p>');
                    }
                },
                error: function () {
                    $('#ail-gsc-spinner').removeClass('is-active');
                    $('#ail-gsc-upload-msg').addClass('error').html('<p>Server error during upload.</p>');
                }
            });
        });
    });
</script>