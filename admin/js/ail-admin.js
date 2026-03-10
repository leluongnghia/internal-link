jQuery(document).ready(function ($) {
    function toggleProviderFields() {
        var provider = $('#ail_api_provider').val();

        // Hide all provider groups
        $('.ail-provider-group').closest('tr').hide();

        // Show selected provider group
        $('.ail-provider-' + provider).closest('tr').show();
    }

    // Initial check
    if ($('#ail_api_provider').length) {
        toggleProviderFields();

        // On change
        $('#ail_api_provider').on('change', function () {
            toggleProviderFields();
        });
    }

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
});
