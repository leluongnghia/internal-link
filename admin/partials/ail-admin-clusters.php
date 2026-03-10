<?php

/**
 * Provide an admin area view for the Keyword Clusters
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */
?>

<div class="wrap" style="max-width: 1200px;">
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 12px; border-radius: 12px; margin-right: 15px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
                <line x1="15" y1="3" x2="15" y2="21"></line>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <line x1="3" y1="15" x2="21" y2="15"></line>
            </svg>
        </div>
        <h1 style="font-size: 28px; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2;">Keyword Clusters Data</h1>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        <!-- Upload Box -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); height: fit-content;">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-top: 0; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">Import Keywords</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                Upload a Semrush / Ahrefs Export (.xlsx or .csv) to generate Topic Clusters and Auto-Select Anchor Texts.
            </p>

            <form id="ail-import-keywords-form" enctype="multipart/form-data">
                <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 30px 20px; text-align: center; margin-bottom: 20px; background: #f8fafc; cursor: pointer; transition: all 0.2s ease;" id="ail-upload-zone">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p style="color: #475569; font-weight: 500; margin: 0 0 5px 0;">Drag & Drop or Click to Select File</p>
                    <p style="color: #94a3b8; font-size: 12px; margin: 0;">Supported format: .xlsx, .csv (Max: 5MB)</p>
                    <input type="file" id="ail_import_file" name="file" accept=".xlsx,.csv" style="display: none;">
                </div>

                <div id="ail-import-status" style="margin-bottom: 15px; display: none;"></div>

                <button type="submit" id="ail-import-btn" style="width: 100%; background: #2563eb; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s flex; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Start Import
                </button>
            </form>
        </div>

        <!-- Data Table -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h2 style="font-size: 18px; font-weight: 600; color: #0f172a; margin-top: 0; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                Keywords Library
                <span style="font-size: 13px; font-weight: 400; background: #e0f2fe; color: #0284c7; padding: 4px 10px; border-radius: 20px;">Filtered</span>
            </h2>

            <?php
            global $wpdb;
            $table_keywords = $wpdb->prefix . 'ail_keywords';

            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_keywords'") != $table_keywords) {
                echo '<p>Table not created yet. Please reactivate the plugin.</p>';
            } else {
                $total_query = "SELECT COUNT(*) FROM $table_keywords";
                $total = $wpdb->get_var($total_query);

                $page = isset($_GET['cpage']) ? abs((int) $_GET['cpage']) : 1;
                $limit = 20;
                $offset = ($page * $limit) - $limit;

                $results = $wpdb->get_results("SELECT * FROM $table_keywords ORDER BY volume DESC LIMIT $offset, $limit");
            ?>

                <?php if (empty($results)): ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <p style="color: #64748b; font-size: 15px; margin: 0;">No keywords found. Please upload a file.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="wp-list-table widefat fixed striped" style="border: none; box-shadow: none;">
                            <thead>
                                <tr>
                                    <th style="font-weight: 600; color: #475569;">Keyword</th>
                                    <th style="font-weight: 600; color: #475569;">Intent</th>
                                    <th style="font-weight: 600; color: #475569; width: 15%;">Volume</th>
                                    <th style="font-weight: 600; color: #475569; width: 12%;">KD</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row):
                                    $intent_color = '#64748b';
                                    $intent_bg = '#f1f5f9';
                                    if (stripos($row->intent, 'informational') !== false) {
                                        $intent_color = '#0284c7';
                                        $intent_bg = '#e0f2fe';
                                    } elseif (stripos($row->intent, 'commercial') !== false || stripos($row->intent, 'transactional') !== false) {
                                        $intent_color = '#16a34a';
                                        $intent_bg = '#dcfce3';
                                    }
                                ?>
                                    <tr>
                                        <td style="font-weight: 500; color: #0f172a;"><?php echo esc_html($row->keyword); ?></td>
                                        <td>
                                            <?php if (!empty($row->intent)): ?>
                                                <span style="font-size: 12px; padding: 3px 8px; border-radius: 4px; background: <?php echo esc_attr($intent_bg); ?>; color: <?php echo esc_attr($intent_color); ?>; font-weight: 500;">
                                                    <?php echo esc_html(ucfirst($row->intent)); ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #475569;"><?php echo number_format($row->volume); ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="font-weight: 500; color: <?php echo $row->kd > 50 ? '#ef4444' : ($row->kd > 30 ? '#f59e0b' : '#10b981'); ?>;"><?php echo esc_html($row->kd); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    // Pagination
                    echo '<div style="margin-top: 20px; display: flex; justify-content: flex-end;">';
                    echo paginate_links(array(
                        'base' => add_query_arg('cpage', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => ceil($total / $limit),
                        'current' => $page
                    ));
                    echo '</div>';
                    ?>
            <?php endif;
            } ?>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        var $uploadZone = $('#ail-upload-zone');
        var $fileInput = $('#ail_import_file');
        var $status = $('#ail-import-status');
        var $btn = $('#ail-import-btn');

        $uploadZone.on('click', function() {
            $fileInput.click();
        });

        $uploadZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css('background', '#e2e8f0');
        });

        $uploadZone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css('background', '#f8fafc');
        });

        $uploadZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css('background', '#f8fafc');

            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $fileInput[0].files = files;
                updateFileName(files[0].name);
            }
        });

        $fileInput.on('change', function() {
            if (this.files.length > 0) {
                updateFileName(this.files[0].name);
            }
        });

        function updateFileName(name) {
            $uploadZone.find('p:first').html('Selected: <b>' + name + '</b>');
        }

        $('#ail-import-keywords-form').on('submit', function(e) {
            e.preventDefault();

            var file = $fileInput[0].files[0];
            if (!file) {
                showStatus('Please select a file to upload.', 'error');
                return;
            }

            var formData = new FormData();
            formData.append('action', 'ail_import_keywords');
            formData.append('file', file);

            $btn.prop('disabled', true).html('<svg class="ail-spinner" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg> Importing Data...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showStatus(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showStatus(response.data.message || 'Import failed.', 'error');
                        $btn.prop('disabled', false).html('Start Import');
                    }
                },
                error: function() {
                    showStatus('Server connection error.', 'error');
                    $btn.prop('disabled', false).html('Start Import');
                }
            });
        });

        function showStatus(msg, type) {
            var bgColor = type === 'success' ? '#dcfce3' : '#fee2e2';
            var color = type === 'success' ? '#16a34a' : '#ef4444';

            $status.html(msg).css({
                'padding': '12px 15px',
                'border-radius': '8px',
                'background-color': bgColor,
                'color': color,
                'font-size': '14px',
                'font-weight': '500'
            }).fadeIn();
        }
    });
</script>

<style>
    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
    }

    .wp-core-ui .page-numbers {
        padding: 5px 10px;
        background: #fff;
        border: 1px solid #cbd5e1;
        margin-left: 5px;
        border-radius: 4px;
        color: #0f172a;
        text-decoration: none;
    }

    .wp-core-ui .page-numbers.current {
        background: #f1f5f9;
        font-weight: bold;
        border-color: #94a3b8;
    }
</style>