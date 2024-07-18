(function($) {
    'use strict';

    $(function() {
        var preScanInProgress = false;
        var linkCheckInProgress = false;
        var scanInProgress = false;
        var currentPage = 1;

        $('#blm-pre-scan').on('click', function() {
            if (!preScanInProgress) {
                startPreScan();
            }
        });

        $('#blm-stop-pre-scan').on('click', function() {
            stopScan('pre-scan');
        });

        $('#blm-check-links').on('click', function() {
            if (!scanInProgress) {
                startLinkCheck();
            }
        });

        $('#blm-stop-check-links').on('click', function() {
            stopScan('check-links');
        });
        
        $('#blm-scan-links').on('click', function() {
            if (!scanInProgress) {
                startScan();
            }
        });

        $('#blm-stop-scan').on('click', function() {
            if (!preScanInProgress || !scanInProgress) {
                stopScan('pre-scan');
                stopScan('check-links');
            }
        });

        function startPreScan() {
            preScanInProgress = true;
            $('#blm-pre-scan').hide();
            $('#blm-stop-pre-scan').show();
            $('#blm-progress').show();
            $('#blm-pre-scan-progress').show();
            $('#blm-link-check-progress').hide();
            preScanBatch(0);
        }

        function preScanBatch(offset) {
            if (!preScanInProgress) return;

            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pre_scan_links',
                    security: broken_links_manager_ajax.security,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        updatePreScanProgress(response.data);
                        if (!response.data.is_complete && preScanInProgress) {
                            preScanBatch(response.data.offset);
                        } else {
                            preScanComplete();
                        }
                    } else {
                        alert('Error: ' + response.data);
                        preScanComplete();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                    preScanComplete();
                }
            });
        }

        function updatePreScanProgress(data) {
            var postsPercentage = (data.offset / data.total_posts) * 100;
            $('#blm-posts-progress').val(postsPercentage);
            $('#blm-posts-progress-text').text(data.offset + ' / ' + data.total_posts);
        }

        function preScanComplete() {
            preScanInProgress = false;
            $('#blm-pre-scan').hide();
            $('#blm-stop-pre-scan').hide();
            $('#blm-check-links').show();
            console.log('Pre-scan complete. You can now check the links.');
        }

        function startLinkCheck() {
            linkCheckInProgress = true;
            $('#blm-check-links').hide();
            $('#blm-stop-check-links').show();
            $('#blm-progress').show();
            $('#blm-pre-scan-progress').hide();
            $('#blm-link-check-progress').show();
            checkLinksBatch();
        }

        function checkLinksBatch() {
            if (!linkCheckInProgress) return;

            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'check_links_batch',
                    security: broken_links_manager_ajax.security
                },
                success: function(response) {
                    if (response.success) {
                        updateLinkCheckProgress(response.data);
                        if (!response.data.is_complete && linkCheckInProgress) {
                            checkLinksBatch();
                        } else {
                            linkCheckComplete();
                        }
                    } else {
                        alert('Error: ' + response.data);
                        linkCheckComplete();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred: ' + textStatus + ' - ' + errorThrown);
                    linkCheckComplete();
                }
            });
        }

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

        function updateLinkCheckProgress(data) {
            var totalLinks = (data.links_checked + data.links_remaining)/10;
            console.log('Total links:', totalLinks);
            var linksPercentage = totalLinks > 0 ? (data.links_checked / totalLinks) * 100 : 0;
            console.log('Links percentage:', linksPercentage);
            $('#blm-links-progress').val(linksPercentage);
            $('#blm-links-progress-text').text(data.links_checked + ' / ' + totalLinks);
        }

        function linkCheckComplete() {
            linkCheckInProgress = false;
            $('#blm-check-links').hide();
            $('#blm-stop-check-links').hide();
            console.log('Link check complete.');
            updateResults();
        }

        function stopScan(type) {
            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'stop_scan',
                    security: broken_links_manager_ajax.security
                },
                success: function(response) {
                    if (response.success) {
                        scanInProgress = false;
                        if (type === 'pre-scan') {
                            $('#blm-pre-scan').show();
                            $('#blm-stop-pre-scan').hide();
                        } else if (type === 'check-links') {
                            $('#blm-check-links').show();
                            $('#blm-stop-check-links').hide();
                        }
                        alert('Scan stopped.');
                    } else {
                        alert('Error stopping scan: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred while stopping the scan: ' + textStatus + ' - ' + errorThrown);
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
                    console.log('AJAX response received:', response);
                    if (response.success) {
                        if (response.data.html) {
                            $('#blm-results-body').html(response.data.html);
                            console.log('Table updated with new data');
                        } else {
                            console.log('No HTML content in the response');
                            $('#blm-results-body').html('<tr><td colspan="7">No links found.</td></tr>');
                        }
                        updatePagination(response.data.total_pages, response.data.current_page);
                    } else {
                        console.error('Error in AJAX response:', response.data);
                        alert('Error updating results: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
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
            var id = $(this).data('id');
            var row = $(this).closest('tr');

            $.ajax({
                url: broken_links_manager_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_link',
                    security: broken_links_manager_ajax.security,
                    id: id,
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
                            id: $(this).data('id'),
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
            console.log('Removing selected links:', links);
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
                        console.log('Selected links removed successfully.');
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