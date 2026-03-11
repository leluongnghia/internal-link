<?php
/**
 * Provide a admin area view for the Plugin Manual Links
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/admin/partials
 */
?>
<div class="wrap">
    <h1>Manual Internal Links</h1>
    <p>Configure manual exact-match links in case AI is not fulfilling the link budget.</p>

    <div class="ail-manual-links-container"
        style="margin-top:20px; background:#fff; padding:20px; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,.04);">
        <form method="post" action="options.php">
            <?php
            settings_fields('ail_options_group');
            $manual_rules = get_option('ail_manual_links', []);
            if (!is_array($manual_rules))
                $manual_rules = [];
            ?>

            <table class="form-table">
                <thead>
                    <tr>
                        <th style="width: 40%">Exact Keyword / Phrase</th>
                        <th style="width: 40%">Target URL</th>
                        <th style="width: 20%">Action</th>
                    </tr>
                </thead>
                <tbody id="ail-manual-rules-table">
                    <?php
                    if (!empty($manual_rules)) {
                        foreach ($manual_rules as $index => $rule) {
                            $phrase = esc_attr($rule['phrase'] ?? '');
                            $url = esc_url($rule['url'] ?? '');
                            echo '<tr class="ail-rule-row">';
                            echo '<td><input type="text" name="ail_manual_links[' . $index . '][phrase]" value="' . $phrase . '" class="regular-text" style="width: 100%" placeholder="e.g. SEO services" /></td>';
                            echo '<td><input type="url" name="ail_manual_links[' . $index . '][url]" value="' . $url . '" class="regular-text" style="width: 100%" placeholder="https://yoursite.com/seo/" /></td>';
                            echo '<td><button type="button" class="button ail-remove-rule" style="color: #a00;">Remove</button></td>';
                            echo '</tr>';
                        }
                    } else {
                        // Empty row
                        echo '<tr class="ail-rule-row">';
                        echo '<td><input type="text" name="ail_manual_links[0][phrase]" value="" class="regular-text" style="width: 100%" placeholder="e.g. SEO services" /></td>';
                        echo '<td><input type="url" name="ail_manual_links[0][url]" value="" class="regular-text" style="width: 100%" placeholder="https://yoursite.com/seo/" /></td>';
                        echo '<td><button type="button" class="button ail-remove-rule" style="color: #a00;">Remove</button></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button ail-add-rule-btn" style="margin-top: 10px;">+ Add Another
                    Rule</button>
            </p>

            <?php submit_button('Save Manual Rules'); ?>
        </form>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        let ruleCount = <?php echo max(1, count($manual_rules)); ?>;

        $('.ail-add-rule-btn').on('click', function (e) {
            e.preventDefault();
            let newRow = `<tr class="ail-rule-row">
                <td><input type="text" name="ail_manual_links[${ruleCount}][phrase]" value="" class="regular-text" style="width: 100%" placeholder="e.g. new keyword" /></td>
                <td><input type="url" name="ail_manual_links[${ruleCount}][url]" value="" class="regular-text" style="width: 100%" placeholder="https://..." /></td>
                <td><button type="button" class="button ail-remove-rule" style="color: #a00;">Remove</button></td>
            </tr>`;
            $('#ail-manual-rules-table').append(newRow);
            ruleCount++;
        });

        $(document).on('click', '.ail-remove-rule', function (e) {
            e.preventDefault();
            if ($('.ail-rule-row').length > 1) {
                $(this).closest('tr').remove();
            } else {
                $(this).closest('tr').find('input').val(''); // Clear the last one
            }
        });
    });
</script>