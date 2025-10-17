<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$webhook_url = get_option('spb_webhook_url', '');
$webhook_secret = get_option('spb_webhook_secret', '');
$rate_limit = get_option('spb_rate_limit', 100);
$api_enabled = get_option('spb_api_enabled', 'yes');
$default_expiration = get_option('spb_default_expiration', 'never');
?>

<div class="spb-section">
    <h2>⚙️ Plugin Settings</h2>
    <p>Configure global settings for the Simple Page Builder API.</p>
    
    <form method="post" action="">
        <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
        <input type="hidden" name="spb_action" value="save_settings">
        
        <table class="form-table">
            <!-- API Status -->
            <tr>
                <th scope="row">
                    <label for="api_enabled">API Status</label>
                </th>
                <td>
                    <select id="api_enabled" name="api_enabled" class="regular-text">
                        <option value="yes" <?php selected($api_enabled, 'yes'); ?>>✓ Enabled</option>
                        <option value="no" <?php selected($api_enabled, 'no'); ?>>✗ Disabled</option>
                    </select>
                    <p class="description">
                        Enable or disable API access globally. When disabled, all API requests will be rejected with a 503 error.
                    </p>
                    <?php if ($api_enabled === 'no'): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                            <strong>⚠️ Warning:</strong> API access is currently disabled. All API keys are temporarily inactive.
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- Rate Limit -->
            <tr>
                <th scope="row">
                    <label for="rate_limit">Rate Limit (per hour)</label>
                </th>
                <td>
                    <input type="number" id="rate_limit" name="rate_limit" value="<?php echo esc_attr($rate_limit); ?>" class="small-text" min="1" max="10000">
                    <span style="margin-left: 10px;">requests per API key per hour</span>
                    <p class="description">
                        Maximum number of requests each API key can make per hour. Recommended: 100-1000 depending on your needs.
                    </p>
                </td>
            </tr>
            
            <!-- Default Key Expiration -->
            <tr>
                <th scope="row">
                    <label for="default_expiration">Default Key Expiration</label>
                </th>
                <td>
                    <select id="default_expiration" name="default_expiration" class="regular-text">
                        <option value="30" <?php selected($default_expiration, '30'); ?>>30 days</option>
                        <option value="60" <?php selected($default_expiration, '60'); ?>>60 days</option>
                        <option value="90" <?php selected($default_expiration, '90'); ?>>90 days</option>
                        <option value="180" <?php selected($default_expiration, '180'); ?>>180 days (6 months)</option>
                        <option value="365" <?php selected($default_expiration, '365'); ?>>365 days (1 year)</option>
                        <option value="never" <?php selected($default_expiration, 'never'); ?>>Never expire</option>
                    </select>
                    <p class="description">
                        Default expiration period for new API keys. You can override this when generating individual keys.
                    </p>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h3>🔔 Webhook Configuration</h3>
        <p>Configure webhook notifications to receive real-time updates when pages are created.</p>
        
        <table class="form-table">
            <!-- Webhook URL -->
            <tr>
                <th scope="row">
                    <label for="webhook_url">Webhook URL</label>
                </th>
                <td>
                    <input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr($webhook_url); ?>" class="large-text code" placeholder="https://yoursite.com/webhook">
                    <p class="description">
                        URL where webhook notifications will be sent when pages are created. Must be a valid HTTPS URL for security.
                    </p>
                    <?php if (!empty($webhook_url)): ?>
                        <button type="button" class="button" id="spb-test-webhook" style="margin-top: 10px;">
                            🧪 Test Webhook
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- Webhook Secret -->
            <tr>
                <th scope="row">
                    <label for="webhook_secret">Webhook Secret</label>
                </th>
                <td>
                    <div style="background: #f0f0f0; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
                        <code style="font-size: 13px; word-break: break-all; display: block; background: #fff; padding: 10px; border-radius: 3px;">
                            <?php echo esc_html($webhook_secret); ?>
                        </code>
                        <button type="button" class="button" style="margin-top: 10px;" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_secret); ?>').then(() => alert('Secret copied to clipboard!'))">
                            📋 Copy Secret
                        </button>
                    </div>
                    <p class="description">
                        Use this secret to verify webhook signatures using HMAC-SHA256. Keep it secure and never share it publicly.
                    </p>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; color: #0073aa; font-weight: 600;">Show verification example</summary>
                        <pre style="margin-top: 10px; background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>// PHP Example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = '<?php echo esc_js($webhook_secret); ?>';

$calculated = hash_hmac('sha256', $payload, $secret);
if (hash_equals($calculated, $signature)) {
    // Signature valid - process webhook
}</code></pre>
                    </details>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h3>🔒 Security Settings</h3>
        
        <table class="form-table">
            <tr>
                <th scope="row">Security Features</th>
                <td>
                    <div style="background: #f0f6fc; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            <li><strong>✓ API Key Hashing:</strong> All keys are hashed with SHA-256 before storage</li>
                            <li><strong>✓ Rate Limiting:</strong> Prevents API abuse with configurable limits</li>
                            <li><strong>✓ Request Logging:</strong> All requests logged with IP addresses</li>
                            <li><strong>✓ Key Expiration:</strong> Optional expiration dates for enhanced security</li>
                            <li><strong>✓ HMAC Signatures:</strong> Webhook payloads signed with SHA-256</li>
                            <li><strong>✓ One-time Display:</strong> API keys shown only once during generation</li>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <?php submit_button('💾 Save Settings', 'primary', 'submit', false); ?>
        </p>
    </form>
</div>

<div class="spb-section">
    <h2>🛠️ Advanced Options</h2>
    
    <h3>Database Maintenance</h3>
    <p>Clean up old activity logs to optimize database performance.</p>
    
    <form method="post" action="" style="margin-bottom: 20px;">
        <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
        <input type="hidden" name="spb_action" value="cleanup_logs">
        <button type="submit" class="button" onclick="return confirm('This will delete activity logs older than 90 days. Continue?')">
            🗑️ Clean Old Logs (90+ days)
        </button>
    </form>
    
    <h3>Regenerate Webhook Secret</h3>
    <p>Generate a new webhook secret. You'll need to update your webhook receiver with the new secret.</p>
    
    <form method="post" action="">
        <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
        <input type="hidden" name="spb_action" value="regenerate_webhook_secret">
        <button type="submit" class="button" onclick="return confirm('⚠️ This will invalidate your current webhook secret. All webhook receivers must be updated. Continue?')">
            🔄 Regenerate Webhook Secret
        </button>
    </form>
</div>

<div class="spb-section" style="background: #fff3cd; border-left: 4px solid #ffc107;">
    <h3>💡 Best Practices</h3>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li><strong>Rate Limits:</strong> Set appropriate rate limits based on your expected traffic. Too low may block legitimate requests.</li>
        <li><strong>Webhook Security:</strong> Always verify webhook signatures in your receiver to ensure authenticity.</li>
        <li><strong>Key Rotation:</strong> Regularly rotate API keys, especially for production environments.</li>
        <li><strong>Monitoring:</strong> Review activity logs regularly to detect unusual patterns or potential abuse.</li>
        <li><strong>HTTPS Only:</strong> Use HTTPS for webhook URLs to ensure secure transmission of data.</li>
        <li><strong>Key Management:</strong> Revoke unused or compromised keys immediately.</li>
    </ul>
</div>

<script>
jQuery(document).ready(function($) {
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
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spb_test_webhook',
                webhook_url: webhookUrl,
                nonce: '<?php echo wp_create_nonce('spb_test_webhook'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ Webhook test successful!\n\nYour webhook endpoint received the test payload.');
                } else {
                    alert('✗ Webhook test failed:\n\n' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('✗ Webhook test failed. Please check the URL and try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('🧪 Test Webhook');
            }
        });
    });
});
</script>
