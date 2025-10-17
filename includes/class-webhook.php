<?php
/**
 * Webhook Notification Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPB_Webhook {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor
    }
    
    /**
     * Send webhook notification
     */
    public function send_notification($request_id, $api_key_name, $pages_created) {
        $webhook_url = get_option('spb_webhook_url', '');
        
        if (empty($webhook_url)) {
            return false;
        }
        
        // Prepare payload
        $payload = array(
            'event' => 'pages_created',
            'timestamp' => current_time('c'), // ISO 8601 format
            'request_id' => $request_id,
            'api_key_name' => $api_key_name,
            'total_pages' => count($pages_created),
            'pages' => $pages_created
        );
        
        // Generate signature
        $signature = $this->generate_signature($payload);
        
        // Send webhook with retry logic
        $max_retries = 2;
        $retry_count = 0;
        $success = false;
        
        while ($retry_count <= $max_retries && !$success) {
            $response = $this->send_webhook_request($webhook_url, $payload, $signature);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $success = true;
                $this->log_webhook_delivery($request_id, 'success', $webhook_url, $payload, $response);
            } else {
                $retry_count++;
                
                if ($retry_count <= $max_retries) {
                    // Exponential backoff: wait 2^retry_count seconds
                    sleep(pow(2, $retry_count));
                } else {
                    // Log failure after all retries
                    $error_message = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
                    $this->log_webhook_delivery($request_id, 'failed', $webhook_url, $payload, $error_message);
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Send webhook HTTP request
     */
    private function send_webhook_request($url, $payload, $signature) {
        $args = array(
            'method' => 'POST',
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'User-Agent' => 'Simple-Page-Builder-Webhook/1.0'
            ),
            'body' => json_encode($payload)
        );
        
        return wp_remote_post($url, $args);
    }
    
    /**
     * Generate HMAC-SHA256 signature
     */
    private function generate_signature($payload) {
        $secret = get_option('spb_webhook_secret', '');
        $json_payload = json_encode($payload);
        
        return hash_hmac('sha256', $json_payload, $secret);
    }
    
    /**
     * Verify webhook signature (for documentation)
     */
    public static function verify_signature($payload, $received_signature, $secret) {
        $json_payload = is_array($payload) ? json_encode($payload) : $payload;
        $calculated_signature = hash_hmac('sha256', $json_payload, $secret);
        
        return hash_equals($calculated_signature, $received_signature);
    }
    
    /**
     * Log webhook delivery
     */
    private function log_webhook_delivery($request_id, $status, $url, $payload, $response) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_webhook_log';
        
        // Create table if it doesn't exist
        $this->ensure_webhook_log_table();
        
        $response_data = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
        
        $wpdb->insert(
            $table,
            array(
                'request_id' => $request_id,
                'webhook_url' => $url,
                'status' => $status,
                'payload' => json_encode($payload),
                'response' => is_string($response_data) ? $response_data : json_encode($response_data),
                'created_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Ensure webhook log table exists
     */
    private function ensure_webhook_log_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_webhook_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id varchar(50) NOT NULL,
            webhook_url text NOT NULL,
            status varchar(20) NOT NULL,
            payload longtext,
            response longtext,
            created_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY request_id (request_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get webhook logs
     */
    public function get_webhook_logs($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_webhook_log';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_date DESC LIMIT %d",
            $limit
        ));
    }
}
