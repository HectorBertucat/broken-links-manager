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
                    if (response.success) {
                        alert(response.data);
                        updateResults();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                },
                complete: function() {
                    $('#blm-scan-links').prop('disabled', false).text('Scan for Broken Links');
                    updateResults();
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
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                }
            });
        });

        $('#blm-select-all').on('change', function() {
            $('.blm-link-checkbox').prop('checked', this.checked);
        });

        $('#blm-apply-bulk-action').on('click', function() {
            var action = $('#blm-bulk-action').val();
            if (action === 'remove') {
                var selectedLinks = $('.blm-link-checkbox:checked');
                if (selectedLinks.length === 0) {
                    alert('Please select at least one link to remove.');
                    return;
                }

                if (confirm('Are you sure you want to remove the selected links?')) {
                    selectedLinks.each(function() {
                        var postId = $(this).data('post-id');
                        var url = $(this).data('url');
                        removeLink(postId, url, $(this).closest('tr'));
                    });
                }
            }
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
                    if (response.success) {
                        alert(response.data);
                        updateResults();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                },
                complete: function() {
                    $('#blm-bulk-remove').prop('disabled', false).text('Remove All Links with Selected Status Code');
                }
            });
        });

        function updateResults() {
            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_broken_links',
                    security: broken_links_manager_ajax.security
                },
                success: function(response) {
                    if (response.success) {
                        $('#blm-results-body').html(response.data);
                    } else {
                        alert('Error updating results: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while updating results: ' + textStatus + ' - ' + errorThrown);
                }
            });
        }

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
                    } else {
                        console.error('Error updating logs: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('An error occurred while updating logs: ' + textStatus + ' - ' + errorThrown);
                }
            });
        }

        function removeLink(postId, url, row) {
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
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                }
            });
        }

        // Update logs every 5 seconds
        setInterval(updateLogs, 5000);

        // Initial update of results and logs
        updateResults();
        updateLogs();
    });

})(jQuery);