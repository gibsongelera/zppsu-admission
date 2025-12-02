$(document).ready(function() {
    let processingRequest = false;

    // Handle status update button clicks
    $(document).on('click', '.update-status', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (processingRequest) return;

        const button = $(this);
        const id = button.data('id');
        const status = button.data('status');
        const row = button.closest('tr');
        const statusCell = row.find('.status-cell');
        const originalContent = statusCell.html();

        // Debug info
        console.log('[DEBUG] Status action clicked:', { id, status });
        console.log('[DEBUG] Row:', row.get(0));
        console.log('[DEBUG] Status cell:', statusCell.get(0));

        processingRequest = true;
        statusCell.html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');

        $('.dropdown-menu').dropdown('hide');

        $.ajax({
            type: 'POST',
            url: '/zppsu_admission/admin/teacher_log/update_status.php',
            data: {
                id: id,
                status: status
            },
            success: function(response) {
                // Debug: log raw response
                console.log('[DEBUG] AJAX raw response:', response);
                let data;
                try {
                    if (typeof response === 'string') {
                        data = JSON.parse(response);
                    } else {
                        data = response;
                    }
                    if (data.success) {
                        // Always show a concise success message and hide SMS details
                        const message = data.message || 'succesfully send the message';
                        showNotification(message, 'success');
                        statusCell.html(getStatusBadgeHtml(status));
                        console.log('[DEBUG] Status updated to:', status);
                        
                        // Auto-refresh the page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        // Show debug info if available
                        let msg = data.message || 'Failed to update status';
                        if (data.debug) {
                            msg += ' | Debug: ' + JSON.stringify(data.debug);
                        }
                        showNotification(msg, 'error');
                        console.error('[DEBUG] Error:', msg);
                        statusCell.html(originalContent);
                    }
                } catch (e) {
                    statusCell.html(originalContent);
                    showNotification('Error parsing response: ' + e.message, 'error');
                    console.error('[DEBUG] Error parsing response:', e, response);
                }
            },
            error: function(xhr) {
                statusCell.html(originalContent);
                showNotification('Server error: ' + (xhr.responseText || 'Failed to update status'), 'error');
                console.error('[DEBUG] AJAX error:', xhr);
            },
            complete: function() {
                processingRequest = false;
                console.log('[DEBUG] AJAX request complete');
            }
        });
    });

    // Handle delete record
    $(document).on('click', '.delete-record', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (processingRequest) return;

        const link = $(this);
        const row = link.closest('tr');
        
        if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            processingRequest = true;
            row.addClass('table-warning');
            
            // Show loading state
            const statusCell = row.find('.status-cell');
            const originalContent = statusCell.html();
            statusCell.html('<span class="spinner-border spinner-border-sm" role="status"></span> Deleting...');
            
            $('.dropdown-menu').dropdown('hide');
            
            $.ajax({
                type: 'GET',
                url: '/zppsu_admission/admin/teacher_log/delete.php',
                data: { id: link.data('id') },
                dataType: 'json',
                success: function(response) {
                    try {
                        const data = (typeof response === 'string') ? JSON.parse(response) : response;
                        if (data && data.success) {
                            showNotification(data.message || 'Record deleted successfully', 'success');
                            row.fadeOut(400, function() {
                                $(this).remove();
                            });
                            
                            // Auto-refresh the page after 2 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            throw new Error(data.message || 'Failed to delete record');
                        }
                    } catch (e) {
                        row.removeClass('table-warning');
                        statusCell.html(originalContent);
                        showNotification(e.message, 'error');
                        console.error('[DEBUG] Delete error:', e, response);
                    }
                },
                error: function(xhr) {
                    row.removeClass('table-warning');
                    statusCell.html(originalContent);
                    showNotification('Server error: ' + (xhr.responseText || 'Failed to delete record'), 'error');
                    console.error('[DEBUG] Delete AJAX error:', xhr);
                },
                complete: function() {
                    processingRequest = false;
                }
            });
        }
    });

    // Show notification messages from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');

    if (successMsg) {
        showNotification(decodeURIComponent(successMsg), 'success');
        // Clean URL after showing message
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (errorMsg) {
        showNotification(decodeURIComponent(errorMsg), 'error');
        // Clean URL after showing message
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Add keyboard shortcuts
    $(document).keydown(function(e) {
        // F5 or Ctrl+R to refresh
        if (e.keyCode === 116 || (e.ctrlKey && e.keyCode === 82)) {
            e.preventDefault();
            location.reload();
        }
    });
    
    // Add auto-refresh every 5 minutes to keep data fresh
    setInterval(function() {
        // Only auto-refresh if no actions are being processed
        if (!processingRequest) {
            location.reload();
        }
    }, 300000); // 5 minutes
});

function getStatusBadgeHtml(status) {
    const badges = {
        'Approved': { class: 'success', icon: 'check' },
        'Pending': { class: 'warning', icon: 'clock' },
        'Rejected': { class: 'danger', icon: 'times' }
    };
    
    const badge = badges[status] || { class: 'secondary', icon: 'question' };
        return `<span class="badge badge-${badge.class} status-badge"><i class="fas fa-${badge.icon} mr-1"></i>${status}</span>`;
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    $('.alert').remove();
    
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      'alert-info';
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'error' ? 'exclamation-triangle' : 
                 'info-circle';
    
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;">
            <i class="fas fa-${icon} mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);
    
    $('body').append(alert);
    
    // Auto-dismiss after 5 seconds for success, 8 seconds for error
    const dismissTime = type === 'error' ? 8000 : 5000;
    setTimeout(function() {
        alert.alert('close');
    }, dismissTime);
    
    // Slide up animation when closing
    alert.on('close.bs.alert', function() {
        $(this).slideUp(200, function() {
            $(this).remove();
        });
    });
}

// Add loading overlay function
function showLoadingOverlay(message = 'Processing...') {
    if ($('#loading-overlay').length === 0) {
        const overlay = $(`
            <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998; display: flex; align-items: center; justify-content: center;">
                <div class="text-center text-white">
                    <div class="spinner-border mb-3" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div>${message}</div>
                </div>
            </div>
        `);
        $('body').append(overlay);
    }
}

function hideLoadingOverlay() {
    $('#loading-overlay').fadeOut(200, function() {
        $(this).remove();
    });
}
