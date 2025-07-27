jQuery(document).ready(function($) {
    // Handle inline update checking in plugins page
    $(document).on('click', '#event-platform-check-updates', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var originalText = $btn.text();
        
        // Disable button and show loading
        $btn.text('Checking...').addClass('checking');
        
        // Make AJAX request
        $.ajax({
            url: eventPlatformAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_plugin_updates',
                nonce: eventPlatformAjax.nonce
            },
            success: function(response) {
                $btn.removeClass('checking');
                
                if (response.success) {
                    if (response.data.has_update) {
                        $btn.text('Update Available!').addClass('has-update');
                        
                        // Show notification
                        showUpdateNotification(response.data.update_info);
                    } else {
                        $btn.text('Up to Date').addClass('up-to-date');
                        
                        // Show success notification
                        showNotification('Plugin is up to date!', 'success');
                    }
                } else {
                    $btn.text('Check Failed').addClass('check-failed');
                    showNotification('Failed to check for updates: ' + response.data.message, 'error');
                }
                
                // Reset button after 3 seconds
                setTimeout(function() {
                    $btn.text(originalText).removeClass('checking has-update up-to-date check-failed');
                }, 3000);
            },
            error: function() {
                $btn.removeClass('checking').text('Check Failed').addClass('check-failed');
                showNotification('Error checking for updates. Please try again.', 'error');
                
                // Reset button after 3 seconds
                setTimeout(function() {
                    $btn.text(originalText).removeClass('check-failed');
                }, 3000);
            }
        });
    });
    
    // Show update notification
    function showUpdateNotification(updateInfo) {
        var notification = $('<div class="event-platform-notification update-available">' +
            '<div class="notification-content">' +
            '<h4>🎉 New Update Available!</h4>' +
            '<div class="update-details">' + updateInfo + '</div>' +
            '<div class="notification-actions">' +
            '<a href="' + admin_url('admin.php?page=event-platform-updates') + '" class="button button-primary">View Details</a>' +
            '<button class="button button-secondary dismiss-notification">Dismiss</button>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(notification);
        
        // Auto-hide after 10 seconds
        setTimeout(function() {
            notification.fadeOut();
        }, 10000);
        
        // Handle dismiss button
        notification.find('.dismiss-notification').on('click', function() {
            notification.fadeOut();
        });
    }
    
    // Show simple notification
    function showNotification(message, type) {
        var notification = $('<div class="event-platform-notification ' + type + '">' +
            '<div class="notification-content">' +
            '<p>' + message + '</p>' +
            '<button class="dismiss-notification">×</button>' +
            '</div>' +
            '</div>');
        
        $('body').append(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            notification.fadeOut();
        }, 5000);
        
        // Handle dismiss button
        notification.find('.dismiss-notification').on('click', function() {
            notification.fadeOut();
        });
    }
}); 