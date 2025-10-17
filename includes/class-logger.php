<?php
/**
 * Logger Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPB_Logger {
    
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
     * Log API request
     */
    public function log_request($api_key_id, $endpoint, $status, $data = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_activity_log';
        
        $wpdb->insert(
            $table,
            array(
                'api_key_id' => $api_key_id,
                'endpoint' => $endpoint,
                'status' => $status,
                'request_data' => json_encode($data),
                'created_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs($limit = 50, $status = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_activity_log';
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}{$where} ORDER BY created_date DESC LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Export logs to CSV
     */
    public function export_logs_csv($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_activity_log';
        
        $logs = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_date DESC");
        
        if (empty($logs)) {
            return false;
        }
        
        $filename = 'spb-activity-log-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array(
            'ID',
            'API Key ID',
            'Request ID',
            'Endpoint',
            'Status',
            'Pages Created',
            'Response Time',
            'IP Address',
            'Created Date'
        ));
        
        // Add data rows
        foreach ($logs as $log) {
            fputcsv($output, array(
                $log->id,
                $log->api_key_id,
                $log->request_id,
                $log->endpoint,
                $log->status,
                $log->pages_created,
                $log->response_time,
                $log->ip_address,
                $log->created_date
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Clean old logs (older than 90 days)
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_activity_log';
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_date < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}
