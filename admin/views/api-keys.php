<?php
/**
 * API Keys View
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_keys = SPB_API_Keys::get_instance();
$keys = $api_keys->get_all_keys();
$new_api_key = get_transient('spb_new_api_key');

if ($new_api_key) {
    delete_transient('spb_new_api_key');
    ?>
    <div class="notice notice-success spb-api-key-display">
        <h3>✅ Your new API key has been generated!</h3>
        <p><strong>⚠️ Important:</strong> Copy this key now as you won't be able to see it again!</p>
        <div class="spb-api-key-value">
            <?php echo esc_html($new_api_key); ?>
        </div>
        <button type="button" class="button button-primary spb-copy-button" onclick="navigator.clipboard.writeText('<?php echo esc_js($new_api_key); ?>').then(() => alert('Copied to clipboard!'))">
            📋 Copy to Clipboard
        </button>
        <p style="margin-top: 15px; color: #856404;">
            <strong>Security Note:</strong> Store this key securely. Treat it like a password and never share it publicly.
        </p>
    </div>
    <?php
}
?>

<div class="spb-section">
    <h2>Generate New API Key</h2>
    <form method="post" action="">
        <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
        <input type="hidden" name="spb_action" value="generate_key">
        
        <table class="form-table">
            <tr>
                <th><label for="key_name">Key Name <span style="color: red;">*</span></label></th>
                <td>
                    <input type="text" id="key_name" name="key_name" class="regular-text" required placeholder="e.g., Production Server, Mobile App">
                    <p class="description">A friendly name to identify this key. Choose something descriptive.</p>
                    <p class="description" id="key-name-counter" style="color: #666; font-size: 12px;">0/255 characters</p>
                </td>
            </tr>
            <tr>
                <th><label for="expiration_date">Expiration Date</label></th>
                <td>
                    <input type="date" id="expiration_date" name="expiration_date" min="<?php echo date('Y-m-d'); ?>">
                    <p class="description">Optional. Leave empty for no expiration. Keys will automatically stop working after this date.</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <?php submit_button('🔑 Generate API Key', 'primary', 'submit', false); ?>
        </p>
    </form>
</div>

<div class="spb-section">
    <h2>API Keys <span class="spb-badge spb-badge-info" style="font-size: 12px; vertical-align: middle;"><?php echo count($keys); ?> Total</span></h2>
    
    <?php if (empty($keys)): ?>
        <div style="padding: 40px; text-align: center; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">
            <p style="font-size: 18px; color: #666; margin: 0;">🔑 No API keys found</p>
            <p style="color: #999;">Generate your first API key above to start using the API.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;">Key Name</th>
                    <th style="width: 15%;">Key Preview</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 15%;">Created</th>
                    <th style="width: 15%;">Last Used</th>
                    <th style="width: 10%;">Requests</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keys as $key): ?>
                    <?php 
                    $stats = $api_keys->get_key_stats($key->id);
                    $is_expired = $key->expiration_date && strtotime($key->expiration_date) < time();
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($key->key_name); ?></strong>
                            <?php if ($is_expired): ?>
                                <br><span style="color: #dc3232; font-size: 12px;">⚠️ Expired</span>
                            <?php elseif ($key->expiration_date): ?>
                                <br><span style="color: #999; font-size: 12px;">Expires: <?php echo date('Y-m-d', strtotime($key->expiration_date)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code style="font-size: 12px;"><?php echo esc_html($key->api_key_preview); ?>***************</code>
                        </td>
                        <td>
                            <?php if ($key->status === 'active' && !$is_expired): ?>
                                <span class="spb-badge spb-badge-success">✓ Active</span>
                            <?php else: ?>
                                <span class="spb-badge spb-badge-danger">✗ Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html(date('M j, Y', strtotime($key->created_date))); ?>
                            <br><span style="color: #999; font-size: 12px;"><?php echo esc_html(date('H:i', strtotime($key->created_date))); ?></span>
                        </td>
                        <td>
                            <?php if ($key->last_used): ?>
                                <?php echo esc_html(date('M j, Y', strtotime($key->last_used))); ?>
                                <br><span style="color: #999; font-size: 12px;"><?php echo esc_html(date('H:i', strtotime($key->last_used))); ?></span>
                            <?php else: ?>
                                <span style="color: #999;">Never used</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html(number_format($key->request_count)); ?></strong>
                            <?php if ($stats && $stats->total_requests > 0): ?>
                                <br><span style="color: #999; font-size: 12px;">
                                    <?php echo esc_html($stats->total_pages_created); ?> pages
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($key->status === 'active' && !$is_expired): ?>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
                                    <input type="hidden" name="spb_action" value="revoke_key">
                                    <input type="hidden" name="key_id" value="<?php echo esc_attr($key->id); ?>">
                                    <button type="submit" class="button button-small revoke-api-key" onclick="return confirm('⚠️ Are you sure you want to revoke this API key?\n\nThis action cannot be undone and all applications using this key will immediately lose access.')">
                                        🚫 Revoke
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 4px;">
            <strong>💡 Tip:</strong> For better security, regularly rotate your API keys and revoke unused ones.
        </div>
    <?php endif; ?>
</div>
