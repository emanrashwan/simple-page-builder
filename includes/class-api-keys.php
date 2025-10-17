<?php
/**
 * API Keys Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPB_API_Keys {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'spb_api_keys';
    }
    
    /**
     * Generate a new API key
     */
    public function generate_key($key_name, $expiration_date = null, $permissions = array('create_pages')) {
        global $wpdb;
        
        // Generate random API key
        $api_key = $this->generate_random_key(64);
        
        // Hash the API key for storage
        $api_key_hash = $this->hash_key($api_key);
        
        // Store preview (first 8 characters)
        $api_key_preview = substr($api_key, 0, 8);
        
        // Insert into database
        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'key_name' => sanitize_text_field($key_name),
                'api_key_hash' => $api_key_hash,
                'api_key_preview' => $api_key_preview,
                'status' => 'active',
                'permissions' => json_encode($permissions),
                'created_date' => current_time('mysql'),
                'expiration_date' => $expiration_date ? date('Y-m-d H:i:s', strtotime($expiration_date)) : null,
                'created_by' => get_current_user_id(),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($inserted) {
            return array(
                'success' => true,
                'api_key' => $api_key,
                'key_id' => $wpdb->insert_id,
                'message' => 'API key generated successfully'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Failed to generate API key'
        );
    }
    
    /**
     * Validate an API key
     */
    public function validate_key($api_key) {
        global $wpdb;
        
        if (empty($api_key)) {
            return false;
        }
        
        $api_key_hash = $this->hash_key($api_key);
        
        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE api_key_hash = %s AND status = 'active'",
            $api_key_hash
        ));
        
        if (!$key_data) {
            return false;
        }
        
        // Check expiration
        if ($key_data->expiration_date && strtotime($key_data->expiration_date) < time()) {
            return false;
        }
        
        // Check rate limit
        if (!$this->check_rate_limit($key_data->id)) {
            return false;
        }
        
        // Update last used and request count
        $wpdb->update(
            $this->table_name,
            array(
                'last_used' => current_time('mysql'),
                'request_count' => $key_data->request_count + 1
            ),
            array('id' => $key_data->id),
            array('%s', '%d'),
            array('%d')
        );
        
        return $key_data;
    }
    
    /**
     * Check rate limit for API key
     */
    private function check_rate_limit($key_id) {
        global $wpdb;
        
        $rate_limit = get_option('spb_rate_limit', 100);
        $log_table = $wpdb->prefix . 'spb_activity_log';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$log_table} 
            WHERE api_key_id = %d 
            AND created_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $key_id
        ));
        
        return $count < $rate_limit;
    }
    
    /**
     * Revoke an API key
     */
    public function revoke_key($key_id) {
        global $wpdb;
        
        $updated = $wpdb->update(
            $this->table_name,
            array('status' => 'revoked'),
            array('id' => $key_id),
            array('%s'),
            array('%d')
        );
        
        return $updated !== false;
    }
    
    /**
     * Get all API keys
     */
    public function get_all_keys($status = 'all') {
        global $wpdb;
        
        $where = '';
        if ($status !== 'all') {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name}{$where} ORDER BY created_date DESC"
        );
    }
    
    /**
     * Get key by ID
     */
    public function get_key($key_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $key_id
        ));
    }
    
    /**
     * Generate random key
     */
    private function generate_random_key($length = 64) {
        return 'spb_' . bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash API key
     */
    private function hash_key($api_key) {
        return hash('sha256', $api_key);
    }
    
    /**
     * Get key statistics
     */
    public function get_key_stats($key_id) {
        global $wpdb;
        $log_table = $wpdb->prefix . 'spb_activity_log';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_requests,
                SUM(pages_created) as total_pages_created,
                AVG(response_time) as avg_response_time
            FROM {$log_table}
            WHERE api_key_id = %d",
            $key_id
        ));
        
        return $stats;
    }
}
