<?php
/**
 * Admin Interface Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPB_Admin_Interface {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Simple Page Builder',
            'Page Builder',
            'manage_options',
            'simple-page-builder',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'tools_page_simple-page-builder') {
            return;
        }
        
        wp_enqueue_style('spb-admin-css', SPB_PLUGIN_URL . 'admin/css/admin-styles.css', array(), SPB_VERSION);
        wp_enqueue_script('spb-admin-js', SPB_PLUGIN_URL . 'admin/js/admin-scripts.js', array('jquery'), SPB_VERSION, true);
        
        wp_localize_script('spb-admin-js', 'spbAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spb_admin_nonce')
        ));
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_POST['spb_action']) || !current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('spb_admin_action', 'spb_nonce');
        
        switch ($_POST['spb_action']) {
            case 'generate_key':
                $this->handle_generate_key();
                break;
            case 'revoke_key':
                $this->handle_revoke_key();
                break;
            case 'save_settings':
                $this->handle_save_settings();
                break;
        }
    }
    
    /**
     * Handle generate API key
     */
    private function handle_generate_key() {
        $key_name = sanitize_text_field($_POST['key_name']);
        $expiration_date = !empty($_POST['expiration_date']) ? sanitize_text_field($_POST['expiration_date']) : null;
        
        if (empty($key_name)) {
            add_settings_error('spb_messages', 'spb_error', 'Key name is required', 'error');
            return;
        }
        
        $api_keys = SPB_API_Keys::get_instance();
        $result = $api_keys->generate_key($key_name, $expiration_date);
        
        if ($result['success']) {
            set_transient('spb_new_api_key', $result['api_key'], 300); // Store for 5 minutes
            add_settings_error('spb_messages', 'spb_success', 'API key generated successfully!', 'success');
        } else {
            add_settings_error('spb_messages', 'spb_error', $result['message'], 'error');
        }
    }
    
    /**
     * Handle revoke API key
     */
    private function handle_revoke_key() {
        $key_id = intval($_POST['key_id']);
        
        $api_keys = SPB_API_Keys::get_instance();
        if ($api_keys->revoke_key($key_id)) {
            add_settings_error('spb_messages', 'spb_success', 'API key revoked successfully', 'success');
        } else {
            add_settings_error('spb_messages', 'spb_error', 'Failed to revoke API key', 'error');
        }
    }
    
    /**
     * Handle save settings
     */
    private function handle_save_settings() {
        update_option('spb_webhook_url', esc_url_raw($_POST['webhook_url']));
        update_option('spb_rate_limit', intval($_POST['rate_limit']));
        update_option('spb_api_enabled', sanitize_text_field($_POST['api_enabled']));
        update_option('spb_default_expiration', sanitize_text_field($_POST['default_expiration']));
        
        add_settings_error('spb_messages', 'spb_success', 'Settings saved successfully', 'success');
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'api-keys';
        ?>
        <div class="wrap spb-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('spb_messages'); ?>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=simple-page-builder&tab=api-keys" class="nav-tab <?php echo $active_tab === 'api-keys' ? 'nav-tab-active' : ''; ?>">
                    API Keys
                </a>
                <a href="?page=simple-page-builder&tab=activity-log" class="nav-tab <?php echo $active_tab === 'activity-log' ? 'nav-tab-active' : ''; ?>">
                    Activity Log
                </a>
                <a href="?page=simple-page-builder&tab=created-pages" class="nav-tab <?php echo $active_tab === 'created-pages' ? 'nav-tab-active' : ''; ?>">
                    Created Pages
                </a>
                <a href="?page=simple-page-builder&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Settings
                </a>
                <a href="?page=simple-page-builder&tab=documentation" class="nav-tab <?php echo $active_tab === 'documentation' ? 'nav-tab-active' : ''; ?>">
                    API Documentation
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'api-keys':
                        $this->render_api_keys_tab();
                        break;
                    case 'activity-log':
                        $this->render_activity_log_tab();
                        break;
                    case 'created-pages':
                        $this->render_created_pages_tab();
                        break;
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'documentation':
                        $this->render_documentation_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API Keys tab
     */
    private function render_api_keys_tab() {
        require_once SPB_PLUGIN_DIR . 'admin/views/api-keys.php';
    }
    
    /**
     * Render Activity Log tab (old version - kept for reference)
     */
    private function render_api_keys_tab_old() {
        $api_keys = SPB_API_Keys::get_instance();
        $keys = $api_keys->get_all_keys();
        $new_api_key = get_transient('spb_new_api_key');
        
        if ($new_api_key) {
            delete_transient('spb_new_api_key');
            ?>
            <div class="notice notice-success">
                <p><strong>Your new API key has been generated!</strong></p>
                <p>Please copy it now as you won't be able to see it again:</p>
                <div style="background: #f0f0f0; padding: 15px; margin: 10px 0; font-family: monospace; word-break: break-all;">
                    <?php echo esc_html($new_api_key); ?>
                </div>
                <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($new_api_key); ?>')">Copy to Clipboard</button>
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
                        <th><label for="key_name">Key Name *</label></th>
                        <td>
                            <input type="text" id="key_name" name="key_name" class="regular-text" required>
                            <p class="description">A friendly name to identify this key (e.g., "Production Server", "Mobile App")</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="expiration_date">Expiration Date</label></th>
                        <td>
                            <input type="date" id="expiration_date" name="expiration_date">
                            <p class="description">Optional. Leave empty for no expiration.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Generate API Key', 'primary', 'submit', false); ?>
            </form>
        </div>
        
        <div class="spb-section">
            <h2>API Keys</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Key Name</th>
                        <th>Key Preview</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Requests</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keys)): ?>
                        <tr>
                            <td colspan="7">No API keys found. Generate your first key above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($keys as $key): ?>
                            <tr>
                                <td><strong><?php echo esc_html($key->key_name); ?></strong></td>
                                <td><code><?php echo esc_html($key->api_key_preview); ?>***</code></td>
                                <td>
                                    <?php if ($key->status === 'active'): ?>
                                        <span class="spb-badge spb-badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="spb-badge spb-badge-danger">Revoked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($key->created_date))); ?></td>
                                <td><?php echo $key->last_used ? esc_html(date('Y-m-d H:i', strtotime($key->last_used))) : 'Never'; ?></td>
                                <td><?php echo esc_html(number_format($key->request_count)); ?></td>
                                <td>
                                    <?php if ($key->status === 'active'): ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
                                            <input type="hidden" name="spb_action" value="revoke_key">
                                            <input type="hidden" name="key_id" value="<?php echo esc_attr($key->id); ?>">
                                            <button type="submit" class="button button-small" onclick="return confirm('Are you sure you want to revoke this API key?')">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render Activity Log tab
     */
    private function render_activity_log_tab() {
        require_once SPB_PLUGIN_DIR . 'admin/views/activity-log.php';
    }
    
    /**
     * Render Created Pages tab
     */
    private function render_created_pages_tab() {
        require_once SPB_PLUGIN_DIR . 'admin/views/created-pages.php';
    }
    
    /**
     * Render Settings tab
     */
    private function render_settings_tab() {
        require_once SPB_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render Documentation tab
     */
    private function render_documentation_tab() {
        require_once SPB_PLUGIN_DIR . 'admin/views/documentation.php';
    }
}

        $log_table = $wpdb->prefix . 'spb_activity_log';
        $keys_table = $wpdb->prefix . 'spb_api_keys';
        
        $logs = $wpdb->get_results("
            SELECT l.*, k.key_name, k.api_key_preview
            FROM {$log_table} l
            LEFT JOIN {$keys_table} k ON l.api_key_id = k.id
            ORDER BY l.created_date DESC
            LIMIT 100
        ");
        ?>
        <div class="spb-section">
            <h2>Recent API Activity</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>API Key</th>
                        <th>Endpoint</th>
                        <th>Status</th>
                        <th>Pages Created</th>
                        <th>Response Time</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7">No activity logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->created_date))); ?></td>
                                <td>
                                    <strong><?php echo esc_html($log->key_name); ?></strong><br>
                                    <code><?php echo esc_html($log->api_key_preview); ?>***</code>
                                </td>
                                <td><code><?php echo esc_html($log->endpoint); ?></code></td>
                                <td>
                                    <?php if ($log->status === 'success'): ?>
                                        <span class="spb-badge spb-badge-success">Success</span>
                                    <?php else: ?>
                                        <span class="spb-badge spb-badge-warning">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->pages_created); ?></td>
                                <td><?php echo esc_html(round($log->response_time, 3)); ?>s</td>
                                <td><?php echo esc_html($log->ip_address); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render Created Pages tab
     */
    private function render_created_pages_tab() {
        global $wpdb;
        $pages_table = $wpdb->prefix . 'spb_pages_created';
        $keys_table = $wpdb->prefix . 'spb_api_keys';
        
        $pages = $wpdb->get_results("
            SELECT p.*, k.key_name
            FROM {$pages_table} p
            LEFT JOIN {$keys_table} k ON p.api_key_id = k.id
            ORDER BY p.created_date DESC
            LIMIT 100
        ");
        ?>
        <div class="spb-section">
            <h2>Pages Created via API</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>URL</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pages)): ?>
                        <tr>
                            <td colspan="5">No pages created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><strong><?php echo esc_html($page->page_title); ?></strong></td>
                                <td><a href="<?php echo esc_url($page->page_url); ?>" target="_blank"><?php echo esc_html($page->page_url); ?></a></td>
                                <td><?php echo esc_html($page->key_name); ?></td>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($page->created_date))); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($page->page_id); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url($page->page_url); ?>" class="button button-small" target="_blank">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render Settings tab
     */
    private function render_settings_tab() {
        ?>
        <div class="spb-section">
            <h2>Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field('spb_admin_action', 'spb_nonce'); ?>
                <input type="hidden" name="spb_action" value="save_settings">
                
                <table class="form-table">
                    <tr>
                        <th><label for="webhook_url">Webhook URL</label></th>
                        <td>
                            <input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr(get_option('spb_webhook_url', '')); ?>" class="regular-text">
                            <p class="description">URL to send webhook notifications when pages are created</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Webhook Secret</label></th>
                        <td>
                            <code><?php echo esc_html(get_option('spb_webhook_secret', '')); ?></code>
                            <p class="description">Use this secret to verify webhook signatures</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rate_limit">Rate Limit (per hour)</label></th>
                        <td>
                            <input type="number" id="rate_limit" name="rate_limit" value="<?php echo esc_attr(get_option('spb_rate_limit', 100)); ?>" class="small-text">
                            <p class="description">Maximum requests per API key per hour</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="api_enabled">API Status</label></th>
                        <td>
                            <select id="api_enabled" name="api_enabled">
                                <option value="yes" <?php selected(get_option('spb_api_enabled', 'yes'), 'yes'); ?>>Enabled</option>
                                <option value="no" <?php selected(get_option('spb_api_enabled', 'yes'), 'no'); ?>>Disabled</option>
                            </select>
                            <p class="description">Enable or disable API access globally</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="default_expiration">Default Key Expiration</label></th>
                        <td>
                            <select id="default_expiration" name="default_expiration">
                                <option value="30" <?php selected(get_option('spb_default_expiration', 'never'), '30'); ?>>30 days</option>
                                <option value="60" <?php selected(get_option('spb_default_expiration', 'never'), '60'); ?>>60 days</option>
                                <option value="90" <?php selected(get_option('spb_default_expiration', 'never'), '90'); ?>>90 days</option>
                                <option value="never" <?php selected(get_option('spb_default_expiration', 'never'), 'never'); ?>>Never</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render Documentation tab
     */
    private function render_documentation_tab() {
        $site_url = get_site_url();
        $endpoint_url = $site_url . '/wp-json/pagebuilder/v1/create-pages';
        ?>
        <div class="spb-section">
            <h2>API Documentation</h2>
            
            <h3>Endpoint</h3>
            <pre><code>POST <?php echo esc_html($endpoint_url); ?></code></pre>
            
            <h3>Authentication</h3>
            <p>Include your API key in the request header:</p>
            <pre><code>X-API-Key: your_api_key_here</code></pre>
            <p>Or using Bearer token format:</p>
            <pre><code>Authorization: Bearer your_api_key_here</code></pre>
            
            <h3>Request Format</h3>
            <pre><code>{
  "pages": [
    {
      "title": "About Us",
      "content": "<p>This is the about page content</p>",
      "status": "publish",
      "slug": "about-us",
      "template": "default"
    },
    {
      "title": "Contact",
      "content": "<p>Contact us here</p>",
      "status": "draft"
    }
  ]
}</code></pre>
            
            <h3>Response Format</h3>
            <pre><code>{
  "success": true,
  "request_id": "req_abc123xyz",
  "total_requested": 2,
  "created": 2,
  "failed": 0,
  "pages": [
    {
      "id": 123,
      "title": "About Us",
      "url": "<?php echo esc_html($site_url); ?>/about-us",
      "status": "publish"
    }
  ],
  "response_time": 0.245
}</code></pre>
            
            <h3>cURL Example</h3>
            <pre><code>curl -X POST <?php echo esc_html($endpoint_url); ?> \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "pages": [
      {
        "title": "Test Page",
        "content": "<p>Test content</p>",
        "status": "publish"
      }
    ]
  }'</code></pre>
            
            <h3>Webhook Signature Verification</h3>
            <p>When you receive a webhook, verify the signature:</p>
            <pre><code>// PHP Example
$payload = file_get_contents('php://input');
$received_signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

$calculated_signature = hash_hmac('sha256', $payload, $secret);

if (hash_equals($calculated_signature, $received_signature)) {
    // Signature is valid
    $data = json_decode($payload, true);
    // Process webhook...
}</code></pre>
            
            <h3>Status Codes</h3>
            <ul>
                <li><strong>200 OK</strong> - Request successful</li>
                <li><strong>400 Bad Request</strong> - Invalid request format</li>
                <li><strong>401 Unauthorized</strong> - Invalid or missing API key</li>
                <li><strong>429 Too Many Requests</strong> - Rate limit exceeded</li>
                <li><strong>503 Service Unavailable</strong> - API is disabled</li>
            </ul>
        </div>
        <?php
    }
}
