<?php

/**
 * AI Internal Linker – Settings Page
 * Design: Data-Dense Dashboard | Fira Sans | #3B82F6 primary
 */
if (!defined('ABSPATH'))
    exit;
$provider = get_option('ail_api_provider', 'openai');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link
    href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap"
    rel="stylesheet">

<div id="ail-settings-app" class="ail-s-wrap wrap">

    <!-- ── HEADER ──────────────────────────────────────────────────────── -->
    <div class="ail-s-header">
        <div class="ail-s-header__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
        </div>
        <div>
            <h1 class="ail-s-header__title">AI Internal Linker</h1>
            <p class="ail-s-header__sub">Cấu hình AI engine, chiến lược liên kết, tự động hóa và hệ thống</p>
        </div>
        <div class="ail-s-header__status">
            <span class="ail-s-dot"></span>
            Hệ thống hoạt động &mdash; <strong><?php echo esc_html(strtoupper($provider)); ?></strong> đang dùng
        </div>
    </div>

    <!-- ── FORM ────────────────────────────────────────────────────────── -->
    <form method="post" action="options.php" id="ail-settings-form">
        <?php settings_fields('ail_options_group'); ?>
        <?php do_settings_sections('ail_options_group'); ?>
        <input type="hidden" name="ail_api_provider" id="ail_api_provider" value="<?php echo esc_attr($provider); ?>">

        <!-- ── TABS ──────────────────────────────────────────────────────── -->
        <div class="ail-s-tabs" role="tablist">
            <button type="button" class="ail-s-tab ail-s-tab--active" data-tab="api" role="tab" aria-selected="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                    <line x1="8" y1="21" x2="16" y2="21" />
                    <line x1="12" y1="17" x2="12" y2="21" />
                </svg>
                AI Engine
            </button>
            <button type="button" class="ail-s-tab" data-tab="strategy" role="tab" aria-selected="false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
                Chiến lược liên kết
            </button>
            <button type="button" class="ail-s-tab" data-tab="automation" role="tab" aria-selected="false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.07 4.93A10 10 0 0 0 4.93 19.07" />
                    <path d="M4.93 4.93A10 10 0 0 1 19.07 19.07" />
                </svg>
                Tự động hóa
            </button>
            <button type="button" class="ail-s-tab" data-tab="system" role="tab" aria-selected="false">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3" />
                    <path
                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
                Hệ thống &amp; Cập nhật
            </button>
        </div>

        <!-- ── TAB: API ENGINES ───────────────────────────────────────────── -->
        <div class="ail-s-pane" id="ail-pane-api">

            <!-- Provider selector -->
            <div class="ail-s-card">
                <div class="ail-s-card__head">Chọn AI Engine</div>
                <div class="ail-s-providers">
                    <?php
                    $providers = [
                        'openai' => ['name' => 'OpenAI', 'sub' => 'GPT-4o, GPT-4 Turbo', 'icon' => '<path d="M12 2L2 7l10 5 10-5-10-5M2 17l10 5 10-5M2 12l10 5 10-5"/>'],
                        'gemini' => ['name' => 'Google Gemini', 'sub' => 'Gemini 2.5, 2.0 &amp; 1.5 Series', 'icon' => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>'],
                        'grok' => ['name' => 'Grok (xAI)', 'sub' => 'Grok 2, Grok 3, Grok 4.1', 'icon' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>'],
                    ];
                    foreach ($providers as $key => $p):
                        ?>
                        <div class="ail-s-provider <?php echo ($provider === $key) ? 'ail-s-provider--active' : ''; ?>"
                            data-provider="<?php echo esc_attr($key); ?>" role="button" tabindex="0"
                            aria-pressed="<?php echo ($provider === $key) ? 'true' : 'false'; ?>">
                            <div class="ail-s-provider__icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"><?php echo $p['icon']; ?></svg>
                            </div>
                            <div>
                                <div class="ail-s-provider__name"><?php echo esc_html($p['name']); ?></div>
                                <div class="ail-s-provider__sub"><?php echo $p['sub']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- OpenAI config -->
            <div class="ail-s-card ail-s-provgroup ail-s-provgroup--openai" <?php echo ($provider !== 'openai') ? 'style="display:none"' : ''; ?>>
                <div class="ail-s-card__head">Cấu hình OpenAI</div>
                <div class="ail-s-fields">
                    <?php $o_model = get_option('ail_openai_model', 'gpt-4o'); ?>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_openai_model">Model AI</label>
                        <select name="ail_openai_model" id="ail_openai_model" class="ail-s-select">
                            <option value="gpt-4o" <?php selected($o_model, 'gpt-4o'); ?>>GPT-4o (Khuyến nghị)</option>
                            <option value="gpt-4-turbo" <?php selected($o_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                            <option value="gpt-4o-mini" <?php selected($o_model, 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                        </select>
                    </div>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_openai_key">API Key</label>
                        <input type="password" name="ail_openai_key" id="ail_openai_key" class="ail-s-input"
                            value="<?php echo esc_attr(get_option('ail_openai_key')); ?>" placeholder="sk-..."
                            autocomplete="off">
                        <p class="ail-s-hint"><a href="https://platform.openai.com/api-keys" target="_blank"
                                rel="noopener">Lấy API key OpenAI tại đây</a></p>
                    </div>
                </div>
            </div>

            <!-- Gemini config -->
            <div class="ail-s-card ail-s-provgroup ail-s-provgroup--gemini" <?php echo ($provider !== 'gemini') ? 'style="display:none"' : ''; ?>>
                <div class="ail-s-card__head">Cấu hình Gemini</div>
                <div class="ail-s-fields">
                    <?php $g_model = get_option('ail_gemini_model', 'gemini-2.0-flash'); ?>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_gemini_model">Model AI</label>
                        <select name="ail_gemini_model" id="ail_gemini_model" class="ail-s-select">
                            <option value="gemini-2.5-flash" <?php selected($g_model, 'gemini-2.5-flash'); ?>>Gemini 2.5
                                Flash</option>
                            <option value="gemini-2.0-flash" <?php selected($g_model, 'gemini-2.0-flash'); ?>>Gemini 2.0
                                Flash</option>
                            <option value="gemini-2.0-flash-lite" <?php selected($g_model, 'gemini-2.0-flash-lite'); ?>>
                                Gemini 2.0 Flash-Lite</option>
                            <option value="gemini-2.0-pro-exp" <?php selected($g_model, 'gemini-2.0-pro-exp'); ?>>Gemini
                                2.0 Pro Experimental</option>
                            <option value="gemini-1.5-pro" <?php selected($g_model, 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro
                            </option>
                            <option value="gemini-1.5-flash" <?php selected($g_model, 'gemini-1.5-flash'); ?>>Gemini 1.5
                                Flash</option>
                            <option value="gemini-1.5-flash-8b" <?php selected($g_model, 'gemini-1.5-flash-8b'); ?>>
                                Gemini 1.5 Flash-8B</option>
                        </select>
                    </div>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_gemini_key">API Key</label>
                        <input type="password" name="ail_gemini_key" id="ail_gemini_key" class="ail-s-input"
                            value="<?php echo esc_attr(get_option('ail_gemini_key')); ?>" placeholder="AIzaSy..."
                            autocomplete="off">
                        <p class="ail-s-hint"><a href="https://aistudio.google.com/app/apikey" target="_blank"
                                rel="noopener">Lấy API key Gemini tại đây</a></p>
                    </div>
                </div>
            </div>

            <!-- Grok config -->
            <div class="ail-s-card ail-s-provgroup ail-s-provgroup--grok" <?php echo ($provider !== 'grok') ? 'style="display:none"' : ''; ?>>
                <div class="ail-s-card__head">Cấu hình Grok</div>
                <div class="ail-s-fields">
                    <?php $x_model = get_option('ail_grok_model', 'grok-2-1212'); ?>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_grok_model">Model AI</label>
                        <select name="ail_grok_model" id="ail_grok_model" class="ail-s-select">
                            <option value="grok-2-1212" <?php selected($x_model, 'grok-2-1212'); ?>>Grok 2</option>
                            <option value="grok-3" <?php selected($x_model, 'grok-3'); ?>>Grok 3</option>
                            <option value="grok-4-1-fast-reasoning" <?php selected($x_model, 'grok-4-1-fast-reasoning'); ?>>Grok 4.1 Fast Reasoning</option>
                        </select>
                    </div>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_grok_key">API Key</label>
                        <input type="password" name="ail_grok_key" id="ail_grok_key" class="ail-s-input"
                            value="<?php echo esc_attr(get_option('ail_grok_key')); ?>" placeholder="xai-..."
                            autocomplete="off">
                        <p class="ail-s-hint"><a href="https://docs.x.ai/developers/quickstart" target="_blank"
                                rel="noopener">Lấy API key Grok tại đây</a></p>
                    </div>
                </div>
            </div>
        </div><!-- #ail-pane-api -->

        <!-- ── TAB: LINK STRATEGY ─────────────────────────────────────────── -->
        <div class="ail-s-pane" id="ail-pane-strategy" style="display:none">
            <div class="ail-s-card">
                <div class="ail-s-card__head">Lọc nội dung & nguồn link</div>
                <div class="ail-s-fields">
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_link_source">Chiến lược nguồn link</label>
                        <select name="ail_link_source" id="ail_link_source" class="ail-s-select">
                            <option value="category" <?php selected(get_option('ail_link_source'), 'category'); ?>>Cùng
                                Danh mục (Khuyến nghị)</option>
                            <option value="tag" <?php selected(get_option('ail_link_source'), 'tag'); ?>>Cùng Thẻ tag
                            </option>
                            <option value="silo" <?php selected(get_option('ail_link_source'), 'silo'); ?>>Content Silo
                                (Pillar &amp; Cluster)</option>
                            <option value="all" <?php selected(get_option('ail_link_source'), 'all'); ?>>Toàn bộ nội
                                dung (Chậm hơn)</option>
                        </select>
                        <p class="ail-s-hint">AI sẽ tìm kiếm bài viết để đặt internal link ở phạm vi nào?</p>
                    </div>

                    <div class="ail-s-field">
                        <label class="ail-s-label">Optimization Skills (tập viết tùy chỉnh)</label>
                        <div class="ail-s-skills-box">
                            <label class="ail-s-skills-all">
                                <input type="checkbox" id="ail_skill_select_all"> <span>Chọn tất cả Skills</span>
                            </label>
                            <hr class="ail-s-divider">
                            <?php
                            $upload_dir = wp_upload_dir();
                            $skills_dir = trailingslashit($upload_dir['basedir']) . 'aprg-skills/';
                            $all_skills = [];
                            $has_skills = false;
                            if (file_exists($skills_dir)) {
                                $files = glob($skills_dir . '*.md');
                                if ($files) {
                                    $has_skills = true;
                                    foreach ($files as $f)
                                        $all_skills[] = basename($f, '.md');
                                }
                            }
                            $saved_skills = get_option('ail_selected_skill');
                            $selected_skills = ($saved_skills === false) ? $all_skills : (is_array($saved_skills) ? $saved_skills : []);
                            if ($has_skills):
                                ?>
                                <div class="ail-s-skills-grid">
                                    <?php foreach ($all_skills as $sk): ?>
                                        <label class="ail-s-skill-item">
                                            <input type="checkbox" name="ail_selected_skill[]"
                                                value="<?php echo esc_attr($sk); ?>" class="ail-skill-checkbox" <?php echo in_array($sk, $selected_skills) ? 'checked' : ''; ?>>
                                            <span><?php echo esc_html($sk); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="ail-s-hint">Chưa có skill nào trong <code>/aprg-skills/</code></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ail-s-card">
                <div class="ail-s-card__head">Giới hạn chèn link</div>
                <div class="ail-s-fields ail-s-fields--row">
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_max_links">Số link tối đa / bài</label>
                        <input type="number" name="ail_max_links" id="ail_max_links" class="ail-s-input ail-s-input--sm"
                            value="<?php echo esc_attr(get_option('ail_max_links', 5)); ?>" min="1" max="50">
                        <p class="ail-s-hint">Số internal link tối đa được chèn vào mỗi lần chạy</p>
                    </div>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_max_anchor_repeat">Lặp anchor tối đa</label>
                        <input type="number" name="ail_max_anchor_repeat" id="ail_max_anchor_repeat"
                            class="ail-s-input ail-s-input--sm"
                            value="<?php echo esc_attr(get_option('ail_max_anchor_repeat', 3)); ?>" min="1" max="100">
                        <p class="ail-s-hint">Giới hạn số lần dùng cùng một anchor text cho mỗi URL</p>
                    </div>
                </div>
            </div>
        </div><!-- #ail-pane-strategy -->

        <!-- ── TAB: AUTOMATION ───────────────────────────────────────────── -->
        <div class="ail-s-pane" id="ail-pane-automation" style="display:none">
            <div class="ail-s-card">
                <div class="ail-s-card__head">Sự kiện kích hoạt</div>
                <div class="ail-s-fields">
                    <div class="ail-s-field ail-s-field--toggle">
                        <div>
                            <div class="ail-s-label">Tự động chèn link khi lưu bài</div>
                            <p class="ail-s-hint">Tự động chèn internal link khi lưu hoặc xuất bản bài viết</p>
                        </div>
                        <label class="ail-toggle" aria-label="Auto-Link on Save">
                            <input type="hidden" name="ail_auto_on_save" value="0">
                            <input type="checkbox" name="ail_auto_on_save" value="1" <?php checked(get_option('ail_auto_on_save'), 1); ?>>
                            <span class="ail-toggle__slider"></span>
                        </label>
                    </div>
                    <div class="ail-s-field ail-s-field--toggle">
                        <div>
                            <div class="ail-s-label">Xử lý nền (Background)</div>
                            <p class="ail-s-hint">Chạy bất đồng bộ — nên bật với website có nhiều bài viết</p>
                        </div>
                        <label class="ail-toggle" aria-label="Background Processing">
                            <input type="hidden" name="ail_background_mode" value="0">
                            <input type="checkbox" name="ail_background_mode" value="1" <?php checked(get_option('ail_background_mode'), 1); ?>>
                            <span class="ail-toggle__slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="ail-s-card">
                <div class="ail-s-card__head">Xử lý hàng loạt (Cron tự động)</div>
                <div class="ail-s-fields">
                    <div class="ail-s-field ail-s-field--toggle">
                        <div>
                            <div class="ail-s-label">Bật Cron hàng ngày</div>
                            <p class="ail-s-hint">Kích hoạt quét tự động nền để xử lý các bài viết cũ</p>
                        </div>
                        <label class="ail-toggle" aria-label="Enable Daily Cron">
                            <input type="hidden" name="ail_batch_enabled" value="0">
                            <input type="checkbox" name="ail_batch_enabled" value="1" <?php checked(get_option('ail_batch_enabled', 1), 1); ?>>
                            <span class="ail-toggle__slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div><!-- #ail-pane-automation -->

        <!-- ── TAB: SYSTEM & UPDATES ─────────────────────────────────────── -->
        <div class="ail-s-pane" id="ail-pane-system" style="display:none">
            <div class="ail-s-card">
                <div class="ail-s-card__head">Tự động cập nhật qua GitHub</div>
                <div class="ail-s-fields">
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_github_updater_url">URL Repository GitHub</label>
                        <input type="url" name="ail_github_updater_url" id="ail_github_updater_url" class="ail-s-input"
                            value="<?php echo esc_attr(get_option('ail_github_updater_url')); ?>"
                            placeholder="https://github.com/leluongnghia/internal-link">
                        <p class="ail-s-hint">Đường dẫn đầy đủ tới repository GitHub để tự động cập nhật</p>
                    </div>
                    <div class="ail-s-field">
                        <label class="ail-s-label" for="ail_github_updater_token">GitHub Token <span
                                class="ail-s-optional">(Tùy chọn)</span></label>
                        <input type="password" name="ail_github_updater_token" id="ail_github_updater_token"
                            class="ail-s-input" value="<?php echo esc_attr(get_option('ail_github_updater_token')); ?>"
                            placeholder="ghp_..." autocomplete="off">
                        <p class="ail-s-hint">Bắt buộc nếu dùng repository riêng tư hoặc muốn bỏ qua giới hạn rate limit
                        </p>
                    </div>
                </div>
            </div>

            <div class="ail-s-card">
                <div class="ail-s-card__head">Trạng thái hiện tại</div>
                <div class="ail-s-fields">
                    <div class="ail-s-stat-row">
                        <span class="ail-s-stat-label">Phiên bản Plugin</span>
                        <span class="ail-s-stat-val"><code><?php echo esc_html(AIL_VERSION); ?></code></span>
                    </div>
                    <div class="ail-s-stat-row">
                        <span class="ail-s-stat-label">Repository</span>
                        <span class="ail-s-stat-val">
                            <?php $repo = get_option('ail_github_updater_url', '');
                            echo $repo ? '<a href="' . esc_url($repo) . '" target="_blank" rel="noopener">' . esc_html($repo) . '</a>' : '<em style="color:#94a3b8">Chưa cấu hình</em>'; ?>
                        </span>
                    </div>
                    <div class="ail-s-stat-row">
                        <span class="ail-s-stat-label">GitHub Token</span>
                        <span class="ail-s-stat-val">
                            <?php $tok = get_option('ail_github_updater_token', '');
                            if ($tok): ?>
                                <span style="color:#16a34a;display:inline-flex;align-items:center;gap:5px"><svg width="13"
                                        height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>Đã cấu hình (<?php echo esc_html(substr($tok, 0, 8)); ?>...)</span>
                            <?php else: ?>
                                <span style="color:#94a3b8;display:inline-flex;align-items:center;gap:5px"><svg width="13"
                                        height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18" />
                                        <line x1="6" y1="6" x2="18" y2="18" />
                                    </svg>Chưa đặt (chỉ cần với repo riêng tư)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="ail-s-stat-row">
                        <span class="ail-s-stat-label">Kiểm tra cập nhật</span>
                        <span class="ail-s-stat-val" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                            <button type="button" id="ail-force-update-btn" class="ail-s-btn ail-s-btn--secondary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="23 4 23 10 17 10" />
                                    <polyline points="1 20 1 14 7 14" />
                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                                </svg>
                                Kiểm tra cập nhật
                            </button>
                            <span id="ail-check-spinner" style="display:none;color:#64748b;font-size:13px">
                                <svg class="ail-spin" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="2" x2="12" y2="6" />
                                    <line x1="12" y1="18" x2="12" y2="22" />
                                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76" />
                                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07" />
                                    <line x1="2" y1="12" x2="6" y2="12" />
                                    <line x1="18" y1="12" x2="22" y2="12" />
                                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24" />
                                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93" />
                                </svg>
                                Đang kiểm tra GitHub&hellip;
                            </span>
                        </span>
                    </div>
                    <div id="ail-update-result"></div>
                </div>
            </div>
        </div><!-- #ail-pane-system -->

        <!-- ── SAVE BAR ───────────────────────────────────────────────────── -->
        <div class="ail-s-save-bar">
            <button type="submit" class="ail-s-btn ail-s-btn--primary" name="submit" id="submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                Lưu cài đặt
            </button>
        </div>
    </form>
</div><!-- #ail-settings-app -->

<script>
    (function () {
        // ── Tabs ──────────────────────────────────────────────────────────────
        document.querySelectorAll('.ail-s-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.ail-s-tab').forEach(function (t) {
                    t.classList.remove('ail-s-tab--active');
                    t.setAttribute('aria-selected', 'false');
                });
                tab.classList.add('ail-s-tab--active');
                tab.setAttribute('aria-selected', 'true');
                document.querySelectorAll('.ail-s-pane').forEach(function (p) {
                    p.style.display = 'none';
                });
                document.getElementById('ail-pane-' + tab.dataset.tab).style.display = '';
            });
        });

        // ── Provider cards ────────────────────────────────────────────────────
        document.querySelectorAll('.ail-s-provider').forEach(function (card) {
            function select() {
                var prov = card.dataset.provider;
                document.getElementById('ail_api_provider').value = prov;
                document.querySelectorAll('.ail-s-provider').forEach(function (c) {
                    c.classList.remove('ail-s-provider--active');
                    c.setAttribute('aria-pressed', 'false');
                });
                card.classList.add('ail-s-provider--active');
                card.setAttribute('aria-pressed', 'true');
                document.querySelectorAll('.ail-s-provgroup').forEach(function (g) {
                    g.style.display = 'none';
                });
                var target = document.querySelector('.ail-s-provgroup--' + prov);
                if (target) target.style.display = '';
            }
            card.addEventListener('click', select);
            card.addEventListener('keydown', function (e) {
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    select();
                }
            });
        });

        // ── Select All Skills ─────────────────────────────────────────────────
        var selectAll = document.getElementById('ail_skill_select_all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.ail-skill-checkbox').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
            });
        }

        // ── Force Update Check ────────────────────────────────────────────────
        document.getElementById('ail-force-update-btn').addEventListener('click', function () {
            var btn = this,
                spinner = document.getElementById('ail-check-spinner'),
                result = document.getElementById('ail-update-result');
            btn.disabled = true;
            spinner.style.display = 'inline-flex';
            result.innerHTML = '';
            var fd = new FormData();
            fd.append('action', 'ail_force_update_check');
            fd.append('nonce', '<?php echo wp_create_nonce('ail_force_update'); ?>');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (resp) {
                    spinner.style.display = 'none';
                    btn.disabled = false;
                    if (resp.success) {
                        var d = resp.data,
                            color = d.has_update ? '#ef4444' : '#16a34a';
                        var html = '<div class="ail-s-update-box" style="border-left-color:' + color + '">' +
                            '<strong>' + d.message + '</strong><br>' +
                            'Đã cài: <code>' + d.current_version + '</code> &nbsp;|&nbsp; Mới nhất: <code>' + d.latest_version + '</code><br>' +
                            'Ngày phát hành: ' + d.published_at;
                        if (d.has_update) html += '<br><a href="<?php echo admin_url('plugins.php'); ?>" class="ail-s-btn ail-s-btn--primary" style="margin-top:10px">Vào Plugins để cập nhật</a>';
                        html += '</div>';
                        result.innerHTML = html;
                    } else {
                        var msg = (resp.data && resp.data.message) ? resp.data.message : (resp.data || 'Unknown error');
                        result.innerHTML = '<div class="ail-s-update-box" style="border-left-color:#ef4444">' + msg + '</div>';
                    }
                })
                .catch(function (err) {
                    spinner.style.display = 'none';
                    btn.disabled = false;
                    result.innerHTML = '<div class="ail-s-update-box" style="border-left-color:#ef4444">Connection error: ' + err + '</div>';
                });
        });
    })();
</script>

<style>
    /* ── Design System: Data-Dense Dashboard / Fira Sans ─────────────────── */
    #ail-settings-app {
        font-family: 'Fira Sans', system-ui, sans-serif;
        color: #1e293b;
        max-width: 900px;
    }

    #ail-settings-app *,
    #ail-settings-app *::before,
    #ail-settings-app *::after {
        box-sizing: border-box;
    }

    /* Header */
    .ail-s-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .ail-s-header__icon {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #fff;
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 14px -4px rgba(99, 102, 241, .4);
    }

    .ail-s-header__title {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 3px;
    }

    .ail-s-header__sub {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    .ail-s-header__status {
        margin-left: auto;
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ail-s-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #16a34a;
        flex-shrink: 0;
        box-shadow: 0 0 0 2px #bbf7d0;
    }

    /* Tabs */
    .ail-s-tabs {
        display: flex;
        gap: 2px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
    }

    .ail-s-tab {
        background: transparent;
        border: none;
        border-bottom: 2.5px solid transparent;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: color .15s;
        margin-bottom: -2px;
        font-family: inherit;
    }

    .ail-s-tab:hover {
        color: #1e293b;
    }

    .ail-s-tab--active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        font-weight: 600;
    }

    /* Card */
    .ail-s-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }

    .ail-s-card__head {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    /* Fields */
    .ail-s-fields {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .ail-s-fields--row {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 24px;
    }

    .ail-s-fields--row .ail-s-field {
        flex: 1;
        min-width: 180px;
    }

    .ail-s-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .ail-s-field--toggle {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
    }

    .ail-s-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .ail-s-optional {
        font-weight: 400;
        color: #94a3b8;
        font-size: 12px;
    }

    .ail-s-hint {
        font-size: 12px;
        color: #64748b;
        margin: 0;
        line-height: 1.5;
    }

    .ail-s-hint a {
        color: #2563eb;
        text-decoration: none;
    }

    .ail-s-hint a:hover {
        text-decoration: underline;
    }

    /* Inputs */
    .ail-s-input {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Fira Sans', sans-serif;
        color: #0f172a;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }

    .ail-s-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    .ail-s-input--sm {
        max-width: 120px;
    }

    .ail-s-select {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Fira Sans', sans-serif;
        color: #0f172a;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 36px;
    }

    .ail-s-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    /* Provider grid */
    .ail-s-providers {
        padding: 16px 20px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ail-s-provider {
        flex: 1;
        min-width: 180px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: border-color .18s, background .18s, box-shadow .18s;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ail-s-provider:hover {
        border-color: #3b82f6;
        background: #f8fafc;
    }

    .ail-s-provider--active {
        border-color: #3b82f6;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    .ail-s-provider__icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        flex-shrink: 0;
        transition: background .18s, color .18s;
    }

    .ail-s-provider--active .ail-s-provider__icon {
        background: #2563eb;
        color: #fff;
    }

    .ail-s-provider__name {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
    }

    .ail-s-provider__sub {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Skills box */
    .ail-s-skills-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
    }

    .ail-s-skills-all {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        cursor: pointer;
    }

    .ail-s-divider {
        border: none;
        border-top: 1px solid #e2e8f0;
        margin: 12px 0;
    }

    .ail-s-skills-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .ail-s-skill-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #374151;
        cursor: pointer;
    }

    .ail-s-skill-item input {
        accent-color: #2563eb;
    }

    /* Toggle switch */
    .ail-toggle {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
    }

    .ail-toggle input[type="hidden"] {
        display: none;
    }

    .ail-toggle input[type="checkbox"] {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .ail-toggle__slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #cbd5e1;
        border-radius: 24px;
        transition: background .2s;
    }

    .ail-toggle__slider::before {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        left: 3px;
        bottom: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform .2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
    }

    .ail-toggle input:checked+.ail-toggle__slider {
        background: #3b82f6;
    }

    .ail-toggle input:checked+.ail-toggle__slider::before {
        transform: translateX(20px);
    }

    .ail-toggle input:focus-visible+.ail-toggle__slider {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .25);
    }

    /* Status rows */
    .ail-s-stat-row {
        display: flex;
        align-items: baseline;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        flex-wrap: wrap;
    }

    .ail-s-stat-row:last-child {
        border-bottom: none;
    }

    .ail-s-stat-label {
        min-width: 160px;
        font-weight: 600;
        color: #374151;
        flex-shrink: 0;
    }

    .ail-s-stat-val {
        color: #0f172a;
        flex: 1;
    }

    .ail-s-stat-val code {
        background: #f1f5f9;
        padding: 2px 7px;
        border-radius: 5px;
        font-family: 'Fira Code', monospace;
        font-size: 12px;
        color: #0f172a;
    }

    /* Update result box */
    .ail-s-update-box {
        padding: 14px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left-width: 4px;
        border-radius: 8px;
        font-size: 13px;
        line-height: 1.6;
        margin-top: 12px;
    }

    /* Save bar */
    .ail-s-save-bar {
        padding: 16px 0 4px;
        border-top: 1px solid #e2e8f0;
        margin-top: 8px;
    }

    /* Buttons */
    .ail-s-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background .18s, box-shadow .18s;
        font-family: inherit;
        text-decoration: none;
    }

    .ail-s-btn--primary {
        background: #2563eb;
        color: #fff;
    }

    .ail-s-btn--primary:hover:not(:disabled) {
        background: #1d4ed8;
        box-shadow: 0 3px 10px -3px rgba(37, 99, 235, .5);
    }

    .ail-s-btn--secondary {
        background: #fff;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }

    .ail-s-btn--secondary:hover:not(:disabled) {
        border-color: #3b82f6;
        color: #2563eb;
    }

    .ail-s-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    /* Spinner */
    @keyframes ail-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .ail-spin {
        animation: ail-spin .75s linear infinite;
        display: inline-block;
        vertical-align: middle;
    }

    /* Responsive */
    @media (max-width:640px) {
        .ail-s-providers {
            flex-direction: column;
        }

        .ail-s-fields--row {
            flex-direction: column;
        }

        .ail-s-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .ail-s-header__status {
            margin-left: 0;
        }

        .ail-s-skills-grid {
            grid-template-columns: 1fr;
        }
    }
</style>