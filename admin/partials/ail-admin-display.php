<?php
/**
 * Provide a admin area view for the plugin
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */
?>

<div class="ail-wrap wrap">
    <h1>AI Internal Linker Settings</h1>

    <!-- Status Bar -->
    <div class="ail-status-bar">
        <div class="ail-status-item">
            <span class="ail-status-indicator active"></span>
            System Status: Online
        </div>
        <div class="ail-status-item">
            <?php
            $provider = get_option('ail_api_provider', 'openai');
            echo 'Active Model: ' . esc_html(strtoupper($provider));
            ?>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('ail_options_group'); ?>
        <?php do_settings_sections('ail_options_group'); ?>

        <!-- Tabs Navigation -->
        <div class="ail-tabs">
            <div class="ail-tab active" data-tab="tab-api">API Engines</div>
            <div class="ail-tab" data-tab="tab-strategy">Link Strategy</div>
            <div class="ail-tab" data-tab="tab-automation">Automation</div>
            <div class="ail-tab" data-tab="tab-system">System & Updates</div>
        </div>

        <!-- Tab Content: API Engines -->
        <div id="tab-api" class="ail-tab-content active">
            <div class="ail-card interactive">
                <h2 class="ail-card-header">Select AI Engine</h2>
                <input type="hidden" name="ail_api_provider" id="ail_api_provider"
                    value="<?php echo esc_attr($provider); ?>">
                <div class="ail-provider-grid">
                    <div class="ail-card ail-provider-card <?php echo ($provider === 'openai') ? 'active' : ''; ?>"
                        data-provider="openai">
                        <h3>OpenAI</h3>
                        <p class="ail-help-text">GPT-4o, GPT-4 Turbo</p>
                    </div>
                    <div class="ail-card ail-provider-card <?php echo ($provider === 'gemini') ? 'active' : ''; ?>"
                        data-provider="gemini">
                        <h3>Google Gemini</h3>
                        <p class="ail-help-text">Gemini 2.5, 2.0 & 1.5 Series</p>
                    </div>
                    <div class="ail-card ail-provider-card <?php echo ($provider === 'grok') ? 'active' : ''; ?>"
                        data-provider="grok">
                        <h3>Grok (xAI)</h3>
                        <p class="ail-help-text">Grok 2, Grok 3</p>
                    </div>
                </div>
            </div>

            <!-- OpenAI Settings -->
            <div class="ail-card ail-provider-group ail-provider-openai" <?php echo ($provider !== 'openai') ? 'style="display:none;"' : ''; ?>>
                <h2 class="ail-card-header">OpenAI Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <?php $o_model = get_option('ail_openai_model', 'gpt-4o'); ?>
                            <select name="ail_openai_model" class="ail-select">
                                <option value="gpt-4o" <?php selected($o_model, 'gpt-4o'); ?>>GPT-4o (Recommended)
                                </option>
                                <option value="gpt-4-turbo" <?php selected($o_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo
                                </option>
                                <option value="gpt-4o-mini" <?php selected($o_model, 'gpt-4o-mini'); ?>>GPT-4o Mini
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="ail_openai_key" class="ail-input"
                                value="<?php echo esc_attr(get_option('ail_openai_key')); ?>" placeholder="sk-..." />
                            <p class="description"><a href="https://platform.openai.com/api-keys" target="_blank">Get
                                    your OpenAI API key here</a></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Gemini Settings -->
            <div class="ail-card ail-provider-group ail-provider-gemini" <?php echo ($provider !== 'gemini') ? 'style="display:none;"' : ''; ?>>
                <h2 class="ail-card-header">Gemini Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <?php $g_model = get_option('ail_gemini_model', 'gemini-1.5-pro'); ?>
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
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="ail_gemini_key" class="ail-input"
                                value="<?php echo esc_attr(get_option('ail_gemini_key')); ?>" placeholder="AIzaSy..." />
                            <p class="description"><a href="https://aistudio.google.com/app/apikey" target="_blank">Get
                                    your Gemini API key here</a></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Grok Settings -->
            <div class="ail-card ail-provider-group ail-provider-grok" <?php echo ($provider !== 'grok') ? 'style="display:none;"' : ''; ?>>
                <h2 class="ail-card-header">Grok Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <?php $x_model = get_option('ail_grok_model', 'grok-2-1212'); ?>
                            <select name="ail_grok_model" class="ail-select">
                                <option value="grok-2-1212" <?php selected($x_model, 'grok-2-1212'); ?>>Grok 2</option>
                                <option value="grok-3" <?php selected($x_model, 'grok-3'); ?>>Grok 3</option>
                                <option value="grok-4-1-fast-reasoning" <?php selected($x_model, 'grok-4-1-fast-reasoning'); ?>>Grok 4.1 Fast Reasoning</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" name="ail_grok_key" class="ail-input"
                                value="<?php echo esc_attr(get_option('ail_grok_key')); ?>" placeholder="xai-..." />
                            <p class="description"><a href="https://docs.x.ai/developers/quickstart" target="_blank">Get
                                    your Grok API key here</a></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tab Content: Link Strategy -->
        <div id="tab-strategy" class="ail-tab-content">
            <div class="ail-card interactive">
                <h2 class="ail-card-header">Content Filtering</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Link Source Strategy</th>
                        <td>
                            <select name="ail_link_source" class="ail-select">
                                <option value="category" <?php selected(get_option('ail_link_source'), 'category'); ?>>
                                    Same Category (Recommended)</option>
                                <option value="tag" <?php selected(get_option('ail_link_source'), 'tag'); ?>>Same Tag
                                </option>
                                <option value="silo" <?php selected(get_option('ail_link_source'), 'silo'); ?>>Content
                                    Silo (Pillar & Cluster)</option>
                                <option value="all" <?php selected(get_option('ail_link_source'), 'all'); ?>>All Content
                                    (Slow)</option>
                            </select>
                            <span class="ail-help-text">Where should the AI look for internal link candidates?</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Optimization Skills</th>
                        <td>
                            <div
                                style="background: var(--ail-bg-canvas); padding: 16px; border-radius: 6px; border: 1px solid var(--ail-border);">
                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="checkbox" id="ail_skill_select_all"> <strong>Select All Skills</strong>
                                </label>
                                <hr style="border-color: var(--ail-border); margin: 12px 0;">
                                <?php
                                $upload_dir = wp_upload_dir();
                                $skills_dir = trailingslashit($upload_dir['basedir']) . 'aprg-skills/';
                                $all_skills = array();
                                $has_skills = false;
                                if (file_exists($skills_dir)) {
                                    $files = glob($skills_dir . '*.md');
                                    if ($files) {
                                        $has_skills = true;
                                        foreach ($files as $file) {
                                            $all_skills[] = basename($file, '.md');
                                        }
                                    }
                                }
                                $saved_skills = get_option('ail_selected_skill');
                                $selected_skills = ($saved_skills === false) ? $all_skills : (is_array($saved_skills) ? $saved_skills : array());

                                if ($has_skills) {
                                    echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px;">';
                                    foreach ($all_skills as $filename) {
                                        $checked = in_array($filename, $selected_skills) ? 'checked' : '';
                                        echo '<label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="ail_selected_skill[]" value="' . esc_attr($filename) . '" class="ail-skill-checkbox" ' . $checked . '> ' . esc_html($filename) . '</label>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<span class="ail-help-text">No skills found in /aprg-skills/</span>';
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="ail-card interactive">
                <h2 class="ail-card-header">Injection Limits</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Max Links per Post</th>
                        <td>
                            <input type="number" name="ail_max_links" class="ail-input" style="max-width: 100px;"
                                value="<?php echo esc_attr(get_option('ail_max_links', 5)); ?>" min="1" max="50" />
                            <span class="ail-help-text">Maximum internal links injected per run.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Anchor Repeat</th>
                        <td>
                            <input type="number" name="ail_max_anchor_repeat" class="ail-input"
                                style="max-width: 100px;"
                                value="<?php echo esc_attr(get_option('ail_max_anchor_repeat', 3)); ?>" min="1"
                                max="100" />
                            <span class="ail-help-text">Limit reusing the exact same anchor pointing to the same
                                URL.</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tab Content: Automation -->
        <div id="tab-automation" class="ail-tab-content">
            <div class="ail-card interactive">
                <h2 class="ail-card-header">Trigger Events</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto-Link on Save</th>
                        <td>
                            <label class="ail-toggle">
                                <input type="checkbox" name="ail_auto_on_save" value="1" <?php checked(get_option('ail_auto_on_save'), 1); ?>>
                                <span class="ail-toggle-slider"></span>
                            </label>
                            <span class="ail-help-text">Automatically process and inject when saving/publishing a
                                post.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Background processing</th>
                        <td>
                            <label class="ail-toggle">
                                <input type="checkbox" name="ail_background_mode" value="1" <?php checked(get_option('ail_background_mode'), 1); ?>>
                                <span class="ail-toggle-slider"></span>
                            </label>
                            <span class="ail-help-text">Run asynchronously. Recommend ON for large sites so saving isn't
                                slow.</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="ail-card interactive">
                <h2 class="ail-card-header">Batch Processing (Cron)</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Daily Cron</th>
                        <td>
                            <label class="ail-toggle">
                                <input type="checkbox" name="ail_batch_enabled" value="1" <?php checked(get_option('ail_batch_enabled', 1), 1); ?>>
                                <span class="ail-toggle-slider"></span>
                            </label>
                            <span class="ail-help-text">Activate the automatic background scanner for older
                                posts.</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tab Content: System -->
        <div id="tab-system" class="ail-tab-content">
            <div class="ail-card interactive">
                <h2 class="ail-card-header">GitHub Auto Updater</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Repository URL</th>
                        <td>
                            <input type="url" name="ail_github_updater_url" class="ail-input"
                                value="<?php echo esc_attr(get_option('ail_github_updater_url')); ?>"
                                placeholder="https://github.com/AzEvent/plugin-internal-links" />
                            <span class="ail-help-text">Full URL to GitHub repo for automatic updates.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">GitHub Token (Optional)</th>
                        <td>
                            <input type="password" name="ail_github_updater_token" class="ail-input"
                                value="<?php echo esc_attr(get_option('ail_github_updater_token')); ?>"
                                placeholder="ghp_..." />
                            <span class="ail-help-text">Required if repository is Private or to bypass rate
                                Limits.</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="ail-btn ail-btn-primary" name="submit" id="submit"><span>Save
                    Configuration</span></button>
        </p>
    </form>
</div>