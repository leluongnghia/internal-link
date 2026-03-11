<?php

/**
 * Keyword Clusters Admin Page
 * Design: Data-Dense Dashboard | Colors: #3B82F6 primary | Font: Fira Sans
 */
if (! defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'ail_keywords';
$total_kw       = 0;
$total_clusters = 0;
$total_pillars  = 0;

if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
    $total_kw       = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $total_clusters = (int) $wpdb->get_var("SELECT COUNT(DISTINCT cluster_group) FROM $table WHERE cluster_group != '' AND cluster_group IS NOT NULL");
    $total_pillars  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_pillar = 1");
}

$run_nonce    = wp_create_nonce('ail_run_clustering');
$delete_nonce = wp_create_nonce('ail_delete_keywords');
$page   = isset($_GET['cpage']) ? abs((int)$_GET['cpage']) : 1;
$limit  = 30;
$offset = ($page * $limit) - $limit;
$kw_rows = ($total_kw > 0) ? $wpdb->get_results("SELECT * FROM $table ORDER BY volume DESC LIMIT $offset, $limit") : [];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

<div id="ail-kc-app">

    <!-- HEADER -->
    <div class="kc-header">
        <div class="kc-header__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <circle cx="3" cy="6" r="2" />
                <circle cx="21" cy="6" r="2" />
                <circle cx="3" cy="18" r="2" />
                <circle cx="21" cy="18" r="2" />
                <line x1="12" y1="9" x2="3" y2="7" />
                <line x1="12" y1="9" x2="21" y2="7" />
                <line x1="12" y1="15" x2="3" y2="17" />
                <line x1="12" y1="15" x2="21" y2="17" />
            </svg>
        </div>
        <div>
            <h1 class="kc-header__title">Keyword Clusters</h1>
            <p class="kc-header__sub">Semrush-style Topic Clustering — Hub &amp; Spoke strategy for Internal Linking</p>
        </div>
        <div class="kc-header__actions">
            <button id="kc-run-btn" class="kc-btn kc-btn--success" <?php echo !$total_kw ? 'disabled' : ''; ?> data-nonce="<?php echo esc_attr($run_nonce); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="5 3 19 12 5 21 5 3" />
                </svg>
                Run Clustering
            </button>
            <button id="kc-delete-btn" class="kc-btn kc-btn--ghost-danger" <?php echo !$total_kw ? 'disabled' : ''; ?> data-nonce="<?php echo esc_attr($delete_nonce); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                </svg>
                Reset
            </button>
        </div>
    </div>

    <!-- STATUS BAR -->
    <div id="kc-status-bar" style="display:none" class="kc-status-bar"></div>

    <!-- KPI GRID -->
    <div class="kc-kpi-grid">
        <div class="kc-kpi">
            <div class="kc-kpi__icon kc-kpi__icon--blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
            </div>
            <div>
                <div class="kc-kpi__num"><?php echo number_format($total_kw); ?></div>
                <div class="kc-kpi__label">Total Keywords</div>
            </div>
        </div>
        <div class="kc-kpi">
            <div class="kc-kpi__icon kc-kpi__icon--violet">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3" />
                    <circle cx="3" cy="6" r="2" />
                    <circle cx="21" cy="6" r="2" />
                    <circle cx="3" cy="18" r="2" />
                    <circle cx="21" cy="18" r="2" />
                    <line x1="12" y1="9" x2="3" y2="7" />
                    <line x1="12" y1="9" x2="21" y2="7" />
                    <line x1="12" y1="15" x2="3" y2="17" />
                    <line x1="12" y1="15" x2="21" y2="17" />
                </svg>
            </div>
            <div>
                <div class="kc-kpi__num"><?php echo number_format($total_clusters); ?></div>
                <div class="kc-kpi__label">Topic Clusters</div>
            </div>
        </div>
        <div class="kc-kpi">
            <div class="kc-kpi__icon kc-kpi__icon--amber">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                </svg>
            </div>
            <div>
                <div class="kc-kpi__num"><?php echo number_format($total_pillars); ?></div>
                <div class="kc-kpi__label">Pillar Keywords</div>
            </div>
        </div>
        <div class="kc-kpi">
            <div class="kc-kpi__icon kc-kpi__icon--red">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
            </div>
            <div>
                <div class="kc-kpi__num" id="kc-gap-count">—</div>
                <div class="kc-kpi__label">Content Gaps</div>
            </div>
        </div>
    </div>

    <!-- TWO COLUMN LAYOUT -->
    <div class="kc-layout">

        <!-- LEFT: Import Panel -->
        <div class="kc-panel">
            <div class="kc-panel__head">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                Import Keywords
            </div>
            <p class="kc-panel__desc">Upload a Semrush / Ahrefs export (.xlsx or .csv). Expected columns: Keyword, Intent, Volume, KD.</p>
            <form id="kc-import-form" enctype="multipart/form-data">
                <div class="kc-dropzone" id="kc-dropzone" role="button" tabindex="0" aria-label="Click or drag file here">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    <p class="kc-dropzone__text" id="kc-dz-text">Drag &amp; drop or <span class="kc-link">browse file</span></p>
                    <p class="kc-dropzone__hint">.xlsx / .csv &mdash; max 5 MB</p>
                    <input type="file" id="kc-file" name="file" accept=".xlsx,.csv" style="display:none" aria-hidden="true">
                </div>
                <div id="kc-import-msg" class="kc-msg" style="display:none"></div>
                <button type="submit" id="kc-import-btn" class="kc-btn kc-btn--primary kc-btn--full">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    Start Import
                </button>
            </form>
        </div>

        <!-- RIGHT: Main Content -->
        <div class="kc-main">

            <!-- TABS -->
            <div class="kc-tabs" role="tablist">
                <button class="kc-tab kc-tab--active" data-tab="clusters" role="tab" aria-selected="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" />
                        <circle cx="3" cy="6" r="2" />
                        <circle cx="21" cy="6" r="2" />
                        <circle cx="3" cy="18" r="2" />
                        <circle cx="21" cy="18" r="2" />
                        <line x1="12" y1="9" x2="3" y2="7" />
                        <line x1="12" y1="9" x2="21" y2="7" />
                        <line x1="12" y1="15" x2="3" y2="17" />
                        <line x1="12" y1="15" x2="21" y2="17" />
                    </svg>
                    Clusters
                </button>
                <button class="kc-tab" data-tab="gaps" role="tab" aria-selected="false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    Content Gaps <span id="kc-gap-badge" class="kc-tab__badge" style="display:none"></span>
                </button>
                <button class="kc-tab" data-tab="keywords" role="tab" aria-selected="false">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg>
                    All Keywords <span class="kc-tab__badge"><?php echo $total_kw; ?></span>
                </button>
            </div>

            <!-- TAB: CLUSTERS -->
            <div class="kc-tab-pane" id="kc-pane-clusters" role="tabpanel">
                <div id="kc-clusters-empty" class="kc-empty">
                    <?php if ($total_clusters > 0): ?>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <circle cx="3" cy="6" r="2" />
                            <circle cx="21" cy="6" r="2" />
                            <circle cx="3" cy="18" r="2" />
                            <circle cx="21" cy="18" r="2" />
                            <line x1="12" y1="9" x2="3" y2="7" />
                            <line x1="12" y1="9" x2="21" y2="7" />
                            <line x1="12" y1="15" x2="3" y2="17" />
                            <line x1="12" y1="15" x2="21" y2="17" />
                        </svg>
                        <p>Loading <?php echo $total_clusters; ?> clusters&hellip;</p>
                    <?php else: ?>
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <circle cx="3" cy="6" r="2" />
                            <circle cx="21" cy="6" r="2" />
                            <circle cx="3" cy="18" r="2" />
                            <circle cx="21" cy="18" r="2" />
                            <line x1="12" y1="9" x2="3" y2="7" />
                            <line x1="12" y1="9" x2="21" y2="7" />
                            <line x1="12" y1="15" x2="3" y2="17" />
                            <line x1="12" y1="15" x2="21" y2="17" />
                        </svg>
                        <p>Import keywords then click <strong>Run Clustering</strong></p>
                    <?php endif; ?>
                </div>
                <div id="kc-clusters-grid" style="display:none"></div>
            </div>

            <!-- TAB: GAPS -->
            <div class="kc-tab-pane" id="kc-pane-gaps" style="display:none" role="tabpanel">
                <div id="kc-gaps-empty" class="kc-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <p>Run clustering to generate the Content Gap report</p>
                </div>
                <div id="kc-gaps-out" style="display:none"></div>
            </div>

            <!-- TAB: KEYWORDS -->
            <div class="kc-tab-pane" id="kc-pane-keywords" style="display:none" role="tabpanel">
                <?php if (empty($kw_rows)): ?>
                    <div class="kc-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6" />
                            <line x1="8" y1="12" x2="21" y2="12" />
                            <line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" />
                            <line x1="3" y1="12" x2="3.01" y2="12" />
                            <line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg>
                        <p>No keywords imported yet</p>
                    </div>
                <?php else: ?>
                    <div class="kc-table-wrap">
                        <table class="kc-table">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Intent</th>
                                    <th>Volume</th>
                                    <th>KD</th>
                                    <th>Cluster</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kw_rows as $row):
                                    $ic = '#64748b';
                                    $ib = '#f1f5f9';
                                    if (stripos($row->intent, 'info') !== false) {
                                        $ic = '#0369a1';
                                        $ib = '#e0f2fe';
                                    } elseif (stripos($row->intent, 'transact') !== false) {
                                        $ic = '#065f46';
                                        $ib = '#d1fae5';
                                    } elseif (stripos($row->intent, 'commerc') !== false) {
                                        $ic = '#5b21b6';
                                        $ib = '#ede9fe';
                                    } elseif (stripos($row->intent, 'nav') !== false) {
                                        $ic = '#92400e';
                                        $ib = '#fef3c7';
                                    }
                                ?>
                                    <tr>
                                        <td class="kc-table__kw"><?php echo esc_html($row->keyword); ?></td>
                                        <td><?php if ($row->intent): ?><span class="kc-chip" style="background:<?php echo $ib ?>;color:<?php echo $ic ?>"><?php echo esc_html(ucfirst($row->intent)); ?></span><?php else: ?>—<?php endif; ?></td>
                                        <td class="kc-table__num"><?php echo number_format($row->volume); ?></td>
                                        <td><span class="kc-kd" style="color:<?php echo $row->kd > 50 ? '#ef4444' : ($row->kd > 30 ? '#f59e0b' : '#10b981') ?>"><?php echo esc_html($row->kd); ?></span></td>
                                        <td><?php echo $row->cluster_group ? '<span class="kc-chip kc-chip--cluster">' . esc_html($row->cluster_group) . '</span>' : '<span class="kc-muted">—</span>'; ?></td>
                                        <td><?php if ($row->is_pillar): ?><span class="kc-chip kc-chip--pillar">Pillar</span><?php elseif ($row->cluster_group): ?><span class="kc-chip kc-chip--spoke">Spoke</span><?php else: ?><span class="kc-muted">—</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (ceil($total_kw / $limit) > 1): ?>
                        <div class="kc-pagination">
                            <?php echo paginate_links(['base' => add_query_arg('cpage', '%#%'), 'format' => '', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'total' => ceil($total_kw / $limit), 'current' => $page]); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div><!-- .kc-main -->
    </div><!-- .kc-layout -->
</div><!-- #ail-kc-app -->

<script>
    (function($) {
        // ─── Tabs ────────────────────────────────────────────────────────────
        $('.kc-tab').on('click', function() {
            var t = $(this).data('tab');
            $('.kc-tab').removeClass('kc-tab--active').attr('aria-selected', 'false');
            $(this).addClass('kc-tab--active').attr('aria-selected', 'true');
            $('.kc-tab-pane').hide();
            $('#kc-pane-' + t).show();
        });

        // ─── Dropzone ────────────────────────────────────────────────────────
        var $dz = $('#kc-dropzone'),
            $fi = $('#kc-file');
        $dz.on('click keydown', function(e) {
            if (e.type === 'click' || e.key === ' ' || e.key === 'Enter') $fi.click();
        });
        $dz.on('dragover', function(e) {
            e.preventDefault();
            $dz.addClass('kc-dropzone--over');
        });
        $dz.on('dragleave drop', function(e) {
            e.preventDefault();
            $dz.removeClass('kc-dropzone--over');
            if (e.type === 'drop') {
                var f = e.originalEvent.dataTransfer.files;
                if (f.length) {
                    $fi[0].files = f;
                    setFileName(f[0].name);
                }
            }
        });
        $fi.on('change', function() {
            if (this.files.length) setFileName(this.files[0].name);
        });

        function setFileName(n) {
            $('#kc-dz-text').html('Selected: <strong>' + esc(n) + '</strong>');
        }

        // ─── Import ──────────────────────────────────────────────────────────
        $('#kc-import-form').on('submit', function(e) {
            e.preventDefault();
            var f = $fi[0].files[0];
            if (!f) {
                showMsg('#kc-import-msg', 'Please select a file first.', 'error');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'ail_import_keywords');
            fd.append('file', f);
            setBtn('#kc-import-btn', true, 'Importing\u2026');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(r) {
                    if (r.success) {
                        showMsg('#kc-import-msg', r.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1600);
                    } else {
                        showMsg('#kc-import-msg', r.data.message || 'Import failed.', 'error');
                        setBtn('#kc-import-btn', false, 'Start Import');
                    }
                },
                error: function() {
                    showMsg('#kc-import-msg', 'Server error.', 'error');
                    setBtn('#kc-import-btn', false, 'Start Import');
                }
            });
        });

        // ─── Run Clustering ──────────────────────────────────────────────────
        $('#kc-run-btn').on('click', function() {
            var nonce = $(this).data('nonce');
            setBtn('#kc-run-btn', true, 'Running\u2026');
            $.post(ajaxurl, {
                action: 'ail_run_clustering',
                nonce: nonce
            }, function(r) {
                setBtn('#kc-run-btn', false, 'Run Clustering');
                if (r.success) {
                    showStatus(r.data.message, 'success');
                    loadClusters();
                } else showStatus(r.data.message || 'Clustering failed.', 'error');
            });
        });

        // ─── Delete / Reset ──────────────────────────────────────────────────
        $('#kc-delete-btn').on('click', function() {
            if (!confirm('Delete ALL imported keywords and clusters? This cannot be undone.')) return;
            var nonce = $(this).data('nonce');
            setBtn('#kc-delete-btn', true, 'Deleting\u2026');
            $.post(ajaxurl, {
                action: 'ail_delete_keywords',
                nonce: nonce
            }, function(r) {
                if (r.success) location.reload();
                else {
                    showStatus(r.data.message || 'Delete failed.', 'error');
                    setBtn('#kc-delete-btn', false, 'Reset');
                }
            });
        });

        // ─── Load Clusters via AJAX ──────────────────────────────────────────
        function loadClusters() {
            $('#kc-clusters-empty').html('<p style="color:#64748b;padding:20px 0">' + spinner() + ' Loading clusters&hellip;</p>').show();
            $('#kc-clusters-grid').hide();
            $.post(ajaxurl, {
                action: 'ail_get_cluster_data'
            }, function(r) {
                if (!r.success) return;
                var clusters = r.data.clusters,
                    gaps = r.data.gaps;

                // Gap badge & count
                $('#kc-gap-count').text(gaps.length);
                if (gaps.length > 0) {
                    $('#kc-gap-badge').text(gaps.length).show();
                } else {
                    $('#kc-gap-badge').hide();
                }

                // Render clusters
                if (!clusters.length) {
                    $('#kc-clusters-empty').html('<p style="color:#64748b;padding:20px 0">No clusters yet. Run the algorithm.</p>').show();
                    return;
                }
                var html = '';
                $.each(clusters, function(_, c) {
                    var intentMap = {
                        info: 'kc-cluster--info',
                        transact: 'kc-cluster--transact',
                        commerc: 'kc-cluster--commerc',
                        nav: 'kc-cluster--nav'
                    };
                    var ic = '';
                    var intentLow = (c.intent || '').toLowerCase();
                    $.each(intentMap, function(k, v) {
                        if (intentLow.indexOf(k) !== -1) {
                            ic = v;
                            return false;
                        }
                    });

                    var postHtml = c.post ?
                        '<a href="' + esc(c.post.edit_url) + '" target="_blank" class="kc-post-link"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> ' + esc(c.post.title) + '</a>' :
                        '<span class="kc-no-post"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> No post found</span>';

                    var spokesHtml = '';
                    if (c.spokes && c.spokes.length) {
                        $.each(c.spokes.slice(0, 8), function(_, s) {
                            spokesHtml += '<div class="kc-spoke"><span class="kc-spoke__kw">' + esc(s.keyword) + '</span><span class="kc-spoke__vol">' + numFmt(s.volume) + '</span></div>';
                        });
                        if (c.spokes.length > 8) spokesHtml += '<div class="kc-spoke kc-spoke--more">+' + (c.spokes.length - 8) + ' more</div>';
                    } else {
                        spokesHtml = '<div class="kc-spoke kc-spoke--empty">No supporting keywords</div>';
                    }

                    html += '<div class="kc-cluster ' + ic + '">' +
                        '<div class="kc-cluster__head">' +
                        '<div class="kc-cluster__name">' + esc(c.name) + '</div>' +
                        '<div class="kc-cluster__meta">' +
                        '<span class="kc-chip kc-chip--vol">' + numFmt(c.volume) + '</span>' +
                        (c.intent ? '<span class="kc-chip kc-chip--intent">' + esc(c.intent) + '</span>' : '') +
                        '</div>' +
                        '</div>' +
                        '<div class="kc-cluster__body">' +
                        '<div class="kc-cluster__pillar"><span class="kc-pillar-badge">Pillar</span><strong>' + (c.pillar ? esc(c.pillar.keyword) : '—') + '</strong></div>' +
                        '<div class="kc-cluster__post">' + postHtml + '</div>' +
                        '<details class="kc-spokes"><summary>Supporting keywords (' + c.spokes.length + ')</summary>' +
                        '<div class="kc-spokes__list">' + spokesHtml + '</div>' +
                        '</details>' +
                        '</div>' +
                        '</div>';
                });
                $('#kc-clusters-empty').hide();
                $('#kc-clusters-grid').html(html).show();

                // Render gaps
                if (!gaps.length) {
                    $('#kc-gaps-empty').html('<p style="color:#16a34a;padding:20px 0"><svg style="vertical-align:middle;margin-right:6px" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>No gaps! Every cluster has at least one matching post.</p>').show();
                    $('#kc-gaps-out').hide();
                    return;
                }
                var gh = '<div class="kc-gap-notice"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
                    '<strong>' + gaps.length + ' cluster' + (gaps.length !== 1 ? 's' : '') + '</strong> missing a post. Create content for these Pillar keywords.</div>' +
                    '<div class="kc-table-wrap"><table class="kc-table"><thead><tr><th>Pillar Keyword</th><th>Cluster</th><th>Volume</th><th>Intent</th><th>Action</th></tr></thead><tbody>';
                $.each(gaps, function(_, g) {
                    gh += '<tr>' +
                        '<td class="kc-table__kw">' + esc(g.keyword) + '</td>' +
                        '<td><span class="kc-chip kc-chip--cluster">' + esc(g.cluster_group) + '</span></td>' +
                        '<td class="kc-table__num">' + numFmt(g.volume) + '</td>' +
                        '<td>' + esc(g.intent) + '</td>' +
                        '<td><a href="post-new.php?post_type=post" target="_blank" class="kc-btn kc-btn--xs kc-btn--primary">New post</a></td>' +
                        '</tr>';
                });
                gh += '</tbody></table></div>';
                $('#kc-gaps-empty').hide();
                $('#kc-gaps-out').html(gh).show();
            });
        }

        // ─── Helpers ─────────────────────────────────────────────────────────
        function esc(s) {
            return $('<div>').text(s || '').html();
        }

        function numFmt(n) {
            return parseInt(n || 0).toLocaleString();
        }

        function spinner() {
            return '<svg class="kc-spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>';
        }

        function showStatus(msg, type) {
            $('#kc-status-bar').attr('class', 'kc-status-bar kc-status-bar--' + type).html(msg).show();
            setTimeout(function() {
                $('#kc-status-bar').fadeOut();
            }, 5000);
        }

        function showMsg(sel, msg, type) {
            $(sel).attr('class', 'kc-msg kc-msg--' + type).html(msg).show();
        }

        function setBtn(sel, loading, label) {
            $(sel).prop('disabled', loading).html(loading ? spinner() + ' ' + label : label);
        }

        // ─── Auto-load on page open if clusters exist ─────────────────────
        <?php if ($total_clusters > 0): ?>loadClusters();
    <?php endif; ?>

    })(jQuery);
</script>

<style>
    /* ── Design System: Data-Dense Dashboard / Fira Sans ───────────────── */
    #ail-kc-app {
        font-family: 'Fira Sans', system-ui, sans-serif;
        color: #1e293b;
        max-width: 1320px;
    }

    #ail-kc-app *,
    #ail-kc-app *::before,
    #ail-kc-app *::after {
        box-sizing: border-box;
    }

    /* Header */
    .kc-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .kc-header__icon {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #fff;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 14px -4px rgba(99, 102, 241, .45);
    }

    .kc-header__title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 3px;
        line-height: 1.1;
    }

    .kc-header__sub {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    .kc-header__actions {
        margin-left: auto;
        display: flex;
        gap: 8px;
    }

    /* Status bar */
    .kc-status-bar {
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 16px;
    }

    .kc-status-bar--success {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .kc-status-bar--error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* KPIs */
    .kc-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }

    .kc-kpi {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .kc-kpi__icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .kc-kpi__icon--blue {
        background: #dbeafe;
        color: #2563eb;
    }

    .kc-kpi__icon--violet {
        background: #ede9fe;
        color: #7c3aed;
    }

    .kc-kpi__icon--amber {
        background: #fef3c7;
        color: #b45309;
    }

    .kc-kpi__icon--red {
        background: #fee2e2;
        color: #dc2626;
    }

    .kc-kpi__num {
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
        font-family: 'Fira Code', monospace;
    }

    .kc-kpi__label {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    /* Layout */
    .kc-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 20px;
        align-items: start;
    }

    /* Panel (import) */
    .kc-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
    }

    .kc-panel__head {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 10px;
    }

    .kc-panel__desc {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 16px;
        line-height: 1.6;
    }

    /* Dropzone */
    .kc-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        padding: 22px 14px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }

    .kc-dropzone:hover,
    .kc-dropzone:focus,
    .kc-dropzone--over {
        background: #eff6ff;
        border-color: #3b82f6;
        outline: none;
    }

    .kc-dropzone__text {
        font-size: 13px;
        color: #475569;
        margin: 8px 0 4px;
        font-weight: 500;
    }

    .kc-dropzone__hint {
        font-size: 12px;
        color: #94a3b8;
        margin: 0;
    }

    .kc-link {
        color: #2563eb;
        text-decoration: underline;
    }

    /* Buttons */
    .kc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background .18s, opacity .18s, box-shadow .18s;
        font-family: inherit;
    }

    .kc-btn--full {
        width: 100%;
        margin-top: 12px;
    }

    .kc-btn--primary {
        background: #2563eb;
        color: #fff;
    }

    .kc-btn--primary:hover:not(:disabled) {
        background: #1d4ed8;
        box-shadow: 0 3px 10px -3px rgba(37, 99, 235, .5);
    }

    .kc-btn--success {
        background: #059669;
        color: #fff;
    }

    .kc-btn--success:hover:not(:disabled) {
        background: #047857;
    }

    .kc-btn--ghost-danger {
        background: #fff;
        color: #dc2626;
        border: 1.5px solid #fca5a5;
    }

    .kc-btn--ghost-danger:hover:not(:disabled) {
        background: #fef2f2;
        border-color: #ef4444;
    }

    .kc-btn--xs {
        padding: 4px 10px;
        font-size: 12px;
    }

    .kc-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    /* Msg */
    .kc-msg {
        padding: 10px 13px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 12px;
    }

    .kc-msg--success {
        background: #f0fdf4;
        color: #166534;
    }

    .kc-msg--error {
        background: #fef2f2;
        color: #991b1b;
    }

    /* Tabs */
    .kc-main {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
    }

    .kc-tabs {
        display: flex;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 4px;
        background: #f8fafc;
    }

    .kc-tab {
        background: transparent;
        border: none;
        border-bottom: 2.5px solid transparent;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: color .15s;
        margin-bottom: -1px;
        font-family: inherit;
    }

    .kc-tab:hover {
        color: #1e293b;
    }

    .kc-tab--active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        font-weight: 600;
    }

    .kc-tab__badge {
        background: #e0e7ff;
        color: #3730a3;
        font-size: 11px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 20px;
    }

    .kc-tab--active .kc-tab__badge {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .kc-tab-pane {
        padding: 20px;
        min-height: 280px;
    }

    /* Empty */
    .kc-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
        gap: 10px;
    }

    /* Cluster grid */
    #kc-clusters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }

    .kc-cluster {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        transition: box-shadow .2s, transform .2s;
        cursor: default;
    }

    .kc-cluster:hover {
        box-shadow: 0 6px 20px -4px rgba(0, 0, 0, .1);
        transform: translateY(-1px);
    }

    .kc-cluster--info {
        border-top: 3px solid #0ea5e9;
    }

    .kc-cluster--transact {
        border-top: 3px solid #10b981;
    }

    .kc-cluster--commerc {
        border-top: 3px solid #8b5cf6;
    }

    .kc-cluster--nav {
        border-top: 3px solid #f59e0b;
    }

    .kc-cluster__head {
        padding: 14px 16px 10px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }

    .kc-cluster__name {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kc-cluster__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        flex-shrink: 0;
    }

    .kc-cluster__body {
        padding: 13px 16px;
    }

    .kc-cluster__pillar {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        font-size: 13px;
        overflow: hidden;
    }

    .kc-cluster__pillar strong {
        color: #0f172a;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kc-pillar-badge {
        background: #fef3c7;
        color: #92400e;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 20px;
        flex-shrink: 0;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .kc-cluster__post {
        font-size: 12px;
        margin-bottom: 10px;
    }

    .kc-post-link {
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .kc-post-link:hover {
        text-decoration: underline;
    }

    .kc-no-post {
        color: #f59e0b;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Spokes */
    .kc-spokes summary {
        font-size: 12px;
        color: #64748b;
        cursor: pointer;
        user-select: none;
        list-style: none;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .kc-spokes summary::-webkit-details-marker {
        display: none;
    }

    .kc-spokes[open] summary {
        color: #1e293b;
    }

    .kc-spokes__list {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .kc-spoke {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        padding: 4px 7px;
        border-radius: 6px;
        background: #f8fafc;
        transition: background .15s;
    }

    .kc-spoke:hover {
        background: #f1f5f9;
    }

    .kc-spoke__kw {
        color: #334155;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .kc-spoke__vol {
        color: #94a3b8;
        font-family: 'Fira Code', monospace;
        font-size: 11px;
        margin-left: 8px;
        flex-shrink: 0;
    }

    .kc-spoke--more {
        color: #2563eb;
        background: transparent;
        font-style: italic;
    }

    .kc-spoke--empty {
        color: #94a3b8;
        background: transparent;
        font-style: italic;
    }

    /* Chips */
    .kc-chip {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #475569;
        white-space: nowrap;
    }

    .kc-chip--vol {
        background: #dbeafe;
        color: #1d4ed8;
        font-family: 'Fira Code', monospace;
    }

    .kc-chip--intent {
        background: #f0fdf4;
        color: #166534;
    }

    .kc-chip--cluster {
        background: #ede9fe;
        color: #5b21b6;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .kc-chip--pillar {
        background: #fef3c7;
        color: #92400e;
    }

    .kc-chip--spoke {
        background: #f0fdf4;
        color: #166534;
    }

    /* Table */
    .kc-table-wrap {
        overflow-x: auto;
    }

    .kc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .kc-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1.5px solid #e2e8f0;
        white-space: nowrap;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .kc-table tbody td {
        padding: 9px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .kc-table tbody tr:hover {
        background: #f8fafc;
    }

    .kc-table__kw {
        font-weight: 500;
        color: #0f172a;
        max-width: 260px;
    }

    .kc-table__num {
        color: #475569;
        font-family: 'Fira Code', monospace;
    }

    .kc-kd {
        font-weight: 700;
        font-family: 'Fira Code', monospace;
    }

    .kc-muted {
        color: #94a3b8;
    }

    /* Gap notice */
    .kc-gap-notice {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #92400e;
        display: flex;
        align-items: center;
    }

    /* Pagination */
    .kc-pagination {
        margin-top: 16px;
        display: flex;
        justify-content: flex-end;
        gap: 4px;
    }

    .wp-core-ui .page-numbers {
        padding: 5px 10px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        transition: all .15s;
        cursor: pointer;
    }

    .wp-core-ui .page-numbers:hover {
        border-color: #3b82f6;
        color: #2563eb;
    }

    .wp-core-ui .page-numbers.current {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }

    /* Spinner */
    @keyframes kc-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .kc-spin {
        animation: kc-spin .75s linear infinite;
        display: inline-block;
        vertical-align: middle;
    }

    /* Responsive */
    @media (max-width:1024px) {
        .kc-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width:800px) {
        .kc-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        #kc-clusters-grid {
            grid-template-columns: 1fr;
        }

        .kc-header__actions {
            width: 100%;
        }
    }

    @media (max-width:480px) {
        .kc-kpi-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>