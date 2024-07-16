(function($) {
    'use strict';

    $(function() {
        $('#blm-scan-links').on('click', function() {
            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'scan_links',
                    security: broken_links_manager_ajax.security
                },
                beforeSend: function() {
                    $('#blm-scan-links').prop('disabled', true).text('Scanning...');
                },
                success: function(response) {
                    alert(response.data);
                    location.reload();
                },
                error: function() {
                    alert('An error occurred while scanning links.');
                },
                complete: function() {
                    $('#blm-scan-links').prop('disabled', false).text('Scan for Broken Links');
                }
            });
        });

        $('.blm-remove-link').on('click', function() {
            var postId = $(this).data('post-id');
            var url = $(this).data('url');
            var row = $(this).closest('tr');

            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_link',
                    security: broken_links_manager_ajax.security,
                    post_id: postId,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Failed to remove link: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while removing the link.');
                }
            });
        });

        $('#blm-bulk-remove').on('click', function() {
            var statusCode = $('#blm-status-code').val();

            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bulk_remove_links',
                    security: broken_links_manager_ajax.security,
                    status_code: statusCode
                },
                beforeSend: function() {
                    $('#blm-bulk-remove').prop('disabled', true).text('Removing...');
                },
                success: function(response) {
                    alert(response.data);
                    location.reload();
                },
                error: function() {
                    alert('An error occurred while removing links.');
                },
                complete: function() {
                    $('#blm-bulk-remove').prop('disabled', false).text('Remove All Links with Selected Status Code');
                }
            });
        });

        function updateLogs() {
            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_logs',
                    security: broken_links_manager_ajax.security
                },
                success: function(response) {
                    if (response.success) {
                        $('#blm-log-display').val(response.data.join('\n'));
                    }
                }
            });
        }

        // Update logs every 5 seconds
        setInterval(updateLogs, 5000);
    });

})(jQuery);