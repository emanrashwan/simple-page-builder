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
            set_transient('spb_new_api_key', $result['api_key'], 300);
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
        $view_file = SPB_PLUGIN_DIR . 'admin/views/api-keys.php';
        if (file_exists($view_file)) {
            require_once $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: api-keys.php</p></div>';
        }
    }
    
    /**
     * Render Activity Log tab
     */
    private function render_activity_log_tab() {
        $view_file = SPB_PLUGIN_DIR . 'admin/views/activity-log.php';
        if (file_exists($view_file)) {
            require_once $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: activity-log.php</p></div>';
        }
    }
    
    /**
     * Render Created Pages tab
     */
    private function render_created_pages_tab() {
        $view_file = SPB_PLUGIN_DIR . 'admin/views/created-pages.php';
        if (file_exists($view_file)) {
            require_once $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: created-pages.php</p></div>';
        }
    }
    
    /**
     * Render Settings tab
     */
    private function render_settings_tab() {
        $view_file = SPB_PLUGIN_DIR . 'admin/views/settings.php';
        if (file_exists($view_file)) {
            require_once $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: settings.php</p></div>';
        }
    }
    
    /**
     * Render Documentation tab
     */
    private function render_documentation_tab() {
        $view_file = SPB_PLUGIN_DIR . 'admin/views/documentation.php';
        if (file_exists($view_file)) {
            require_once $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: documentation.php</p></div>';
        }
    }
}
