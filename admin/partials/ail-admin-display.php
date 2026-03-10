<?php
/**
 * Provide a admin area view for the plugin
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */
?>

<div class="wrap">
    <h1>AI Internal Linker Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields('ail_options_group'); ?>
        <?php do_settings_sections('ail_options_group'); ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">AI Provider</th>
                <td>
                    <select name="ail_api_provider" id="ail_api_provider">
                        <option value="openai" <?php selected(get_option('ail_api_provider'), 'openai'); ?>>OpenAI
                        </option>
                        <option value="gemini" <?php selected(get_option('ail_api_provider'), 'gemini'); ?>>Google
                            Gemini</option>
                        <option value="grok" <?php selected(get_option('ail_api_provider'), 'grok'); ?>>Grok (xAI)
                        </option>
                    </select>
                </td>
            </tr>

            <!-- OpenAI Settings -->
            <tr valign="top" class="ail-provider-group ail-provider-openai">
                <th scope="row">OpenAI Context</th>
                <td>
                    <p><strong>Model:</strong></p>
                    <select name="ail_openai_model" class="regular-text">
                        <?php $o_model = get_option('ail_openai_model', 'gpt-4o'); ?>
                        <option value="gpt-4o" <?php selected($o_model, 'gpt-4o'); ?>>GPT-4o</option>
                        <option value="gpt-4-turbo" <?php selected($o_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                        <option value="gpt-4o-mini" <?php selected($o_model, 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                    </select>
                    <br><br>
                    <p><strong>API Key:</strong></p>
                    <input type="password" name="ail_openai_key"
                        value="<?php echo esc_attr(get_option('ail_openai_key')); ?>" class="regular-text" />
                </td>
            </tr>

            <!-- Gemini Settings -->
            <tr valign="top" class="ail-provider-group ail-provider-gemini">
                <th scope="row">Gemini Context</th>
                <td>
                    <p><strong>Model:</strong></p>
                    <select name="ail_gemini_model" class="regular-text">
                        <?php $g_model = get_option('ail_gemini_model', 'gemini-1.5-pro'); ?>
                        <option value="gemini-1.5-pro" <?php selected($g_model, 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro
                        </option>
                        <option value="gemini-1.5-flash" <?php selected($g_model, 'gemini-1.5-flash'); ?>>Gemini 1.5
                            Flash</option>
                        <option value="gemini-1.0-pro" <?php selected($g_model, 'gemini-1.0-pro'); ?>>Gemini 1.0 Pro
                        </option>
                    </select>
                    <br><br>
                    <p><strong>API Key:</strong></p>
                    <input type="password" name="ail_gemini_key"
                        value="<?php echo esc_attr(get_option('ail_gemini_key')); ?>" class="regular-text" />
                </td>
            </tr>

            <!-- Grok Settings -->
            <tr valign="top" class="ail-provider-group ail-provider-grok">
                <th scope="row">Grok Context</th>
                <td>
                    <p><strong>Model:</strong></p>
                    <select name="ail_grok_model" class="regular-text">
                        <?php $x_model = get_option('ail_grok_model', 'grok-beta'); ?>
                        <option value="grok-beta" <?php selected($x_model, 'grok-beta'); ?>>Grok Beta</option>
                        <option value="grok-2" <?php selected($x_model, 'grok-2'); ?>>Grok 2</option>
                    </select>
                    <br><br>
                    <p><strong>API Key:</strong></p>
                    <input type="password" name="ail_grok_key"
                        value="<?php echo esc_attr(get_option('ail_grok_key')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Link Source Strategy</th>
                <td>
                    <select name="ail_link_source">
                        <option value="category" <?php selected(get_option('ail_link_source'), 'category'); ?>>Same
                            Category (Recommended)</option>
                        <option value="tag" <?php selected(get_option('ail_link_source'), 'tag'); ?>>Same Tag</option>
                        <option value="all" <?php selected(get_option('ail_link_source'), 'all'); ?>>All Content
                            (Slow)</option>
                    </select>
                    <p class="description">Where should the AI look for internal link candidates?</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Max Links per Post</th>
                <td>
                    <input type="number" name="ail_max_links"
                        value="<?php echo esc_attr(get_option('ail_max_links', 5)); ?>" min="1" max="10" />
                    <p class="description">Maximum number of internal links to inject.</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Optimization Skills</th>
                <td>
                    <fieldset>
                        <label><input type="checkbox" id="ail_skill_select_all"> <strong>Select All</strong></label><br>
                        <hr style="margin: 5px 0; max-width: 300px;">

                        <?php
                        // Dynamically look for skills
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

                        // Get current saved skills (array)
                        // If option doesn't exist (false), default to ALL skills.
                        $saved_skills = get_option('ail_selected_skill');
                        if ($saved_skills === false) {
                            $selected_skills = $all_skills;
                        } else {
                            $selected_skills = is_array($saved_skills) ? $saved_skills : array();
                        }

                        // Display checkboxes
                        if ($has_skills) {
                            foreach ($all_skills as $filename) {
                                $checked = in_array($filename, $selected_skills) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="ail_selected_skill[]" value="' . esc_attr($filename) . '" class="ail-skill-checkbox" ' . $checked . '> ' . esc_html($filename) . '</label><br>';
                            }
                        }

                        if (!$has_skills) {
                            echo '<p class="description">No skills found in <code>/aprg-skills/</code> folder.</p>';
                        }
                        ?>
                    </fieldset>
                    <p class="description">Select "Antigravity Awesome Skills" to enhance the AI's SEO knowledge.</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Auto-Link on Save</th>
                <td>
                    <input type="checkbox" name="ail_auto_on_save" value="1" <?php checked(get_option('ail_auto_on_save'), 1); ?> />
                    <label>Automatically inject internal links when saving/publishing a post?</label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Background Processing</th>
                <td>
                    <input type="checkbox" name="ail_background_mode" value="1" <?php checked(get_option('ail_background_mode'), 1); ?> />
                    <label>Run in background (Async mode)? Useful if saving posts is too slow.</label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>