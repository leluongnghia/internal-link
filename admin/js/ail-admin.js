jQuery(document).ready(function ($) {
    // Provider Selection via Cards
    function toggleProviderFields() {
        var provider = $('#ail_api_provider').val();

        // Hide all provider configuration blocks
        $('.ail-provider-group').hide();

        // Show the selected provider configuration block
        $('.ail-provider-' + provider).fadeIn(300);
    }

    $('.ail-provider-card').on('click', function () {
        // Remove active class from all
        $('.ail-provider-card').removeClass('active');
        // Add active class to clicked
        $(this).addClass('active');

        // Update hidden input
        var selectedProvider = $(this).data('provider');
        $('#ail_api_provider').val(selectedProvider);

        toggleProviderFields();
    });

    if ($('#ail_api_provider').length) {
        // Initial setup
        toggleProviderFields();
    }

    // Tab Interface Logic
    $('.ail-tab').on('click', function () {
        var targetTab = $(this).data('tab');

        // Update tabs state
        $('.ail-tab').removeClass('active');
        $(this).addClass('active');

        // Update content state
        $('.ail-tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');
    });

    // Select All Skills Logic
    $('#ail_skill_select_all').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('.ail-skill-checkbox').prop('checked', isChecked);
    });

    // Check "Select All" on load if all checked
    if ($('.ail-skill-checkbox').length > 0 && $('.ail-skill-checkbox:not(:checked)').length === 0) {
        $('#ail_skill_select_all').prop('checked', true);
    }

    // If any skill is unchecked, uncheck "Select All"
    $('.ail-skill-checkbox').on('change', function () {
        if (!$(this).is(':checked')) {
            $('#ail_skill_select_all').prop('checked', false);
        } else {
            // Check if all are checked
            var allChecked = true;
            $('.ail-skill-checkbox').each(function () {
                if (!$(this).is(':checked')) allChecked = false;
            });
            if (allChecked) $('#ail_skill_select_all').prop('checked', true);
        }
    });

    // Handle "None" checkbox behavior if necessary, or just rely on empty array

    // Manual Run Button Handler
    $(document).on('click', '.ail-run-linker', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var post_id = $btn.data('id');
        var nonce = $btn.data('nonce');

        if ($btn.hasClass('running')) return;

        $btn.addClass('running').text('Running AI...');

        $.ajax({
            url: ajaxurl, // global WP variable
            type: 'POST',
            data: {
                action: 'ail_manual_run',
                post_id: post_id,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    $btn.text('✅ Done!');
                    alert(response.data);
                    // Optional: reload page to see changes?
                    // location.reload();
                } else {
                    $btn.text('❌ Failed');
                    alert('Error: ' + response.data);
                }
            },
            error: function () {
                $btn.text('❌ Server Error');
                alert('Server connection failed.');
            },
            complete: function () {
                $btn.removeClass('running');
                setTimeout(function () {
                    $btn.text('✨ Run AI Linker');
                }, 3000);
            }
        });
    });
    // Interactive Scanner Button Handler
    $(document).on('click', '.ail-scan-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var post_id = $btn.data('id');
        var nonce = $btn.data('nonce');
        var $container = $btn.closest('.ail-scanner-container');
        var $resultsDiv = $container.find('.ail-scan-results');
        var $spinner = $container.find('.ail-scan-spinner');

        if ($btn.hasClass('running')) return;

        // Try to get content from editor if available
        var content = '';
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            // Gutenberg
            content = wp.data.select('core/editor').getEditedPostAttribute('content');
        } else if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
            // Classic Editor
            content = tinymce.activeEditor.getContent();
        }

        $btn.addClass('running').prop('disabled', true);
        $spinner.addClass('is-active');
        $resultsDiv.hide().empty();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ail_interactive_scan',
                post_id: post_id,
                nonce: nonce,
                content: content
            },
            success: function (response) {
                if (response.success && response.data && response.data.length > 0) {
                    var html = '<h4>Suggested Links:</h4><ul>';
                    $.each(response.data, function (index, item) {
                        html += '<li>';
                        html += 'Phrase: <span class="ail-suggestion-phrase">"' + item.exact_phrase + '"</span><br>';
                        html += 'Link: <a href="' + item.target_url + '" target="_blank">' + item.target_url + '</a>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    html += '<p><em>Manually add these links to your content.</em></p>';
                    $resultsDiv.html(html).slideDown();
                } else {
                    $resultsDiv.html('<p>No suggestions found or AI failed.</p>').slideDown();
                }
            },
            error: function () {
                $resultsDiv.html('<p style="color:red;">Server error occurred.</p>').slideDown();
            },
            complete: function () {
                $btn.removeClass('running').prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
