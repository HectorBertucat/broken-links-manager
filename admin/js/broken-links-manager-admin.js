(function($) {
    'use strict';

    $(function() {
        var scanInProgress = false;
        var currentPage = 1;
        
        $('#blm-scan-links').on('click', function() {
            if (!scanInProgress) {
                startScan();
            }
        });

        $('#blm-stop-scan').on('click', function() {
            scanInProgress = false;
            $('#blm-scan-links').show();
            $('#blm-stop-scan').hide();
        });

        function startScan() {
            scanInProgress = true;
            $('#blm-scan-links').hide();
            $('#blm-stop-scan').show();
            $('#blm-progress').show();
            scanBatch(0);
        }

        function scanBatch(offset) {
            if (!scanInProgress) return;

            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'scan_links',
                    security: broken_links_manager_ajax.security,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        updateProgress(response.data);
                        if (!response.data.is_complete && scanInProgress) {
                            scanBatch(response.data.offset);
                        } else {
                            scanComplete();
                        }
                    } else {
                        alert('Error: ' + response.data);
                        scanComplete();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                    scanComplete();
                }
            });
        }

        function updateProgress(data) {
            var postsPercentage = (data.offset / data.total_posts) * 100;
            var linksPercentage = (data.links_checked / data.total_links) * 100;

            $('#blm-posts-progress').val(postsPercentage);
            $('#blm-posts-progress-text').text(data.offset + ' / ' + data.total_posts);

            $('#blm-links-progress').val(linksPercentage);
            $('#blm-links-progress-text').text(data.links_checked + ' / ' + data.total_links);
        }

        function scanComplete() {
            scanInProgress = false;
            $('#blm-scan-links').show();
            $('#blm-stop-scan').hide();
            updateResults();
        }

        $('#blm-show-errors-only').on('change', function() {
            currentPage = 1;
            updateResults();
        });

        function updateResults() {
            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_broken_links',
                    security: broken_links_manager_ajax.security,
                    page: currentPage,
                    show_errors_only: $('#blm-show-errors-only').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        $('#blm-results-body').html(response.data.html);
                        updatePagination(response.data.total_pages, response.data.current_page);
                    } else {
                        alert('Error updating results: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while updating results: ' + textStatus + ' - ' + errorThrown);
                }
            });
        }

        function updatePagination(totalPages, currentPage) {
            var paginationHtml = '';
            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHtml += '<span class="page-numbers current">' + i + '</span>';
                } else {
                    paginationHtml += '<a href="#" class="page-numbers" data-page="' + i + '">' + i + '</a>';
                }
            }
            $('#blm-pagination').html(paginationHtml);
        }

        $(document).on('click', '#blm-pagination a', function(e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            updateResults();
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
                    var links = [];
                    selectedLinks.each(function() {
                        links.push({
                            post_id: $(this).data('post-id'),
                            url: $(this).data('url')
                        });
                    });
                    bulkRemoveLinks(links);
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

        function bulkRemoveLinks(links) {
            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bulk_remove_links',
                    security: broken_links_manager_ajax.security,
                    links: links
                },
                success: function(response) {
                    if (response.success) {
                        alert('Selected links removed successfully.');
                        updateResults();
                    } else {
                        alert('Failed to remove links: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                }
            });
        }

        $(document).on('click', '#blm-pagination a', function(e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            updateResults();
        });

        // Update logs every 5 seconds
        setInterval(updateLogs, 1000);

        // Initial update of results and logs
        updateResults();
        updateLogs();
    });

})(jQuery);