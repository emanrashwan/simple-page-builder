/**
 * Simple Page Builder Admin Scripts
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Copy to clipboard functionality
        $('.spb-copy-button').on('click', function() {
            const text = $(this).data('copy-text');
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Copied to clipboard!');
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        });
        
        // Fallback copy function for older browsers
        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                alert('Copied to clipboard!');
            } catch (err) {
                alert('Failed to copy. Please copy manually.');
            }
            
            document.body.removeChild(textArea);
        }
        
        // Confirm before revoking API key
        $('.revoke-api-key').on('click', function(e) {
            if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Auto-refresh activity log (optional)
        if ($('.spb-activity-log-auto-refresh').length) {
            setInterval(function() {
                location.reload();
            }, 30000); // Refresh every 30 seconds
        }
        
        // Filter functionality for activity log
        $('#spb-filter-status, #spb-filter-date').on('change', function() {
            filterActivityLog();
        });
        
        function filterActivityLog() {
            const status = $('#spb-filter-status').val();
            const date = $('#spb-filter-date').val();
            
            $('.wp-list-table tbody tr').each(function() {
                let show = true;
                
                if (status && status !== 'all') {
                    const rowStatus = $(this).find('.spb-badge').text().toLowerCase();
                    if (rowStatus !== status.toLowerCase()) {
                        show = false;
                    }
                }
                
                if (date) {
                    const rowDate = $(this).find('td:first').text();
                    if (!rowDate.includes(date)) {
                        show = false;
                    }
                }
                
                $(this).toggle(show);
            });
        }
        
        // Export logs functionality
        $('#spb-export-logs').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            $button.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: spbAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_export_logs',
                    nonce: spbAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.download_url;
                    } else {
                        alert('Export failed: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Export failed. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Export as CSV');
                }
            });
        });
        
        // Test webhook functionality
        $('#spb-test-webhook').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const webhookUrl = $('#webhook_url').val();
            
            if (!webhookUrl) {
                alert('Please enter a webhook URL first.');
                return;
            }
            
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: spbAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_test_webhook',
                    webhook_url: webhookUrl,
                    nonce: spbAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Webhook test successful! Check your webhook endpoint.');
                    } else {
                        alert('Webhook test failed: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Webhook test failed. Please check the URL and try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Webhook');
                }
            });
        });
        
        // Show/hide additional options
        $('.spb-toggle-options').on('click', function(e) {
            e.preventDefault();
            $(this).next('.spb-additional-options').slideToggle();
        });
        
        // Character counter for key name
        $('#key_name').on('input', function() {
            const length = $(this).val().length;
            const maxLength = 255;
            $('#key-name-counter').text(length + '/' + maxLength + ' characters');
        });
        
        // Date picker enhancements
        if ($('#expiration_date').length) {
            const today = new Date().toISOString().split('T')[0];
            $('#expiration_date').attr('min', today);
        }
        
        // Confirmation dialogs
        $('form[data-confirm]').on('submit', function(e) {
            const confirmMessage = $(this).data('confirm');
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Stats refresh
        $('.spb-refresh-stats').on('click', function(e) {
            e.preventDefault();
            location.reload();
        });
        
    });
    
})(jQuery);
