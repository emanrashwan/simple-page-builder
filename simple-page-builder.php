<?php
/**
 * Plugin Name: Simple Page Builder
 * Plugin URI: https://github.com/emanrashwan/simple-page-builder
 * Description: Create bulk pages via secure REST API with advanced authentication and webhook notifications
 * Version: 1.0.0
 * Author: emanrashwan
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-page-builder
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SPB_VERSION', '1.0.0');
define('SPB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Simple_Page_Builder {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once SPB_PLUGIN_DIR . 'includes/class-api-keys.php';
        require_once SPB_PLUGIN_DIR . 'includes/class-logger.php';
        require_once SPB_PLUGIN_DIR . 'includes/class-api-endpoint.php';
        require_once SPB_PLUGIN_DIR . 'includes/class-webhook.php';
        require_once SPB_PLUGIN_DIR . 'includes/class-admin-interface.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        SPB_API_Keys::get_instance();
        SPB_Logger::get_instance();
        SPB_API_Endpoint::get_instance();
        SPB_Webhook::get_instance();
        
        if (is_admin()) {
            SPB_Admin_Interface::get_instance();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // API Keys table
        $api_keys_table = $wpdb->prefix . 'spb_api_keys';
        $sql1 = "CREATE TABLE IF NOT EXISTS $api_keys_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            key_name varchar(255) NOT NULL,
            api_key_hash varchar(255) NOT NULL,
            api_key_preview varchar(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            permissions text,
            created_date datetime NOT NULL,
            expiration_date datetime DEFAULT NULL,
            last_used datetime DEFAULT NULL,
            request_count bigint(20) UNSIGNED DEFAULT 0,
            created_by bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            KEY api_key_hash (api_key_hash),
            KEY status (status)
        ) $charset_collate;";
        
        // Activity Log table
        $activity_log_table = $wpdb->prefix . 'spb_activity_log';
        $sql2 = "CREATE TABLE IF NOT EXISTS $activity_log_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            api_key_id bigint(20) UNSIGNED NOT NULL,
            request_id varchar(50) NOT NULL,
            endpoint varchar(255) NOT NULL,
            status varchar(20) NOT NULL,
            pages_created int(11) DEFAULT 0,
            response_time float DEFAULT 0,
            ip_address varchar(45),
            request_data longtext,
            response_data longtext,
            created_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY api_key_id (api_key_id),
            KEY status (status),
            KEY created_date (created_date)
        ) $charset_collate;";
        
        // Pages Created table
        $pages_table = $wpdb->prefix . 'spb_pages_created';
        $sql3 = "CREATE TABLE IF NOT EXISTS $pages_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            page_id bigint(20) UNSIGNED NOT NULL,
            page_title text NOT NULL,
            page_url text NOT NULL,
            api_key_id bigint(20) UNSIGNED NOT NULL,
            request_id varchar(50) NOT NULL,
            created_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY page_id (page_id),
            KEY api_key_id (api_key_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'spb_webhook_url' => '',
            'spb_webhook_secret' => wp_generate_password(32, false),
            'spb_rate_limit' => 100,
            'spb_api_enabled' => 'yes',
            'spb_default_expiration' => 'never',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function spb_init() {
    return Simple_Page_Builder::get_instance();
}

// Start the plugin
spb_init();
