<?php
/**
 * REST API Endpoint Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPB_API_Endpoint {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('pagebuilder/v1', '/create-pages', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_pages'),
            'permission_callback' => array($this, 'authenticate_request'),
        ));
        
        // Health check endpoint (no auth required)
        register_rest_route('pagebuilder/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'status_check'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Authenticate API request
     */
    public function authenticate_request($request) {
        // Check if API is globally enabled
        if (get_option('spb_api_enabled', 'yes') !== 'yes') {
            return new WP_Error(
                'api_disabled',
                'API access is currently disabled',
                array('status' => 503)
            );
        }
        
        // Get API key from header
        $api_key = $request->get_header('X-API-Key');
        
        if (!$api_key) {
            // Also check Authorization header format: Bearer <api_key>
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
                $api_key = $matches[1];
            }
        }
        
        if (empty($api_key)) {
            return new WP_Error(
                'missing_api_key',
                'API key is required. Please provide it in X-API-Key header or Authorization: Bearer header',
                array('status' => 401)
            );
        }
        
        // Validate API key
        $api_keys = SPB_API_Keys::get_instance();
        $key_data = $api_keys->validate_key($api_key);
        
        if (!$key_data) {
            return new WP_Error(
                'invalid_api_key',
                'Invalid or expired API key',
                array('status' => 401)
            );
        }
        
        // Store key data in request for later use
        $request->set_param('_api_key_data', $key_data);
        
        return true;
    }
    
    /**
     * Create pages endpoint
     */
    public function create_pages($request) {
        $start_time = microtime(true);
        $request_id = 'req_' . bin2hex(random_bytes(8));
        
        $key_data = $request->get_param('_api_key_data');
        $pages_data = $request->get_json_params();
        
        // Validate request
        if (empty($pages_data) || !isset($pages_data['pages']) || !is_array($pages_data['pages'])) {
            return new WP_Error(
                'invalid_request',
                'Invalid request format. Expected: {"pages": [{"title": "...", "content": "...", "status": "..."}]}',
                array('status' => 400)
            );
        }
        
        $created_pages = array();
        $errors = array();
        
        foreach ($pages_data['pages'] as $index => $page_data) {
            // Validate required fields
            if (empty($page_data['title'])) {
                $errors[] = array(
                    'index' => $index,
                    'error' => 'Title is required'
                );
                continue;
            }
            
            // Prepare page data
            $new_page = array(
                'post_title'   => sanitize_text_field($page_data['title']),
                'post_content' => isset($page_data['content']) ? wp_kses_post($page_data['content']) : '',
                'post_status'  => isset($page_data['status']) ? sanitize_text_field($page_data['status']) : 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            );
            
            // Add optional fields
            if (isset($page_data['slug'])) {
                $new_page['post_name'] = sanitize_title($page_data['slug']);
            }
            
            if (isset($page_data['template'])) {
                $template = sanitize_text_field($page_data['template']);
            }
            
            if (isset($page_data['featured_image_url'])) {
                $image_id = $this->upload_image_from_url($page_data['featured_image_url']);
                if ($image_id) {
                    $new_page['meta_input']['_thumbnail_id'] = $image_id;
                }
            }
            
            // Create page
            $page_id = wp_insert_post($new_page, true);
            
            if (is_wp_error($page_id)) {
                $errors[] = array(
                    'index' => $index,
                    'title' => $page_data['title'],
                    'error' => $page_id->get_error_message()
                );
                continue;
            }
            
            // Set template if provided
            if (isset($template)) {
                update_post_meta($page_id, '_wp_page_template', $template);
            }
            
            // Add to created pages array
            $page_url = get_permalink($page_id);
            $created_pages[] = array(
                'id' => $page_id,
                'title' => $page_data['title'],
                'url' => $page_url,
                'status' => get_post_status($page_id)
            );
            
            // Log created page
            $this->log_created_page($page_id, $page_data['title'], $page_url, $key_data->id, $request_id);
        }
        
        $response_time = microtime(true) - $start_time;
        
        // Log API activity
        $this->log_activity(
            $key_data->id,
            $request_id,
            '/wp-json/pagebuilder/v1/create-pages',
            empty($errors) ? 'success' : 'partial_success',
            count($created_pages),
            $response_time,
            $this->get_client_ip(),
            $pages_data,
            array(
                'created' => $created_pages,
                'errors' => $errors
            )
        );
        
        // Send webhook notification
        if (!empty($created_pages)) {
            SPB_Webhook::get_instance()->send_notification($request_id, $key_data->key_name, $created_pages);
        }
        
        // Prepare response
        $response = array(
            'success' => true,
            'request_id' => $request_id,
            'total_requested' => count($pages_data['pages']),
            'created' => count($created_pages),
            'failed' => count($errors),
            'pages' => $created_pages,
            'response_time' => round($response_time, 3)
        );
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return rest_ensure_response($response);
    }
    
    /**
     * Status check endpoint
     */
    public function status_check() {
        return rest_ensure_response(array(
            'status' => 'operational',
            'version' => SPB_VERSION,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Log created page
     */
    private function log_created_page($page_id, $title, $url, $api_key_id, $request_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_pages_created';
        
        $wpdb->insert(
            $table,
            array(
                'page_id' => $page_id,
                'page_title' => $title,
                'page_url' => $url,
                'api_key_id' => $api_key_id,
                'request_id' => $request_id,
                'created_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Log API activity
     */
    private function log_activity($api_key_id, $request_id, $endpoint, $status, $pages_created, $response_time, $ip, $request_data, $response_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_activity_log';
        
        $wpdb->insert(
            $table,
            array(
                'api_key_id' => $api_key_id,
                'request_id' => $request_id,
                'endpoint' => $endpoint,
                'status' => $status,
                'pages_created' => $pages_created,
                'response_time' => $response_time,
                'ip_address' => $ip,
                'request_data' => json_encode($request_data),
                'response_data' => json_encode($response_data),
                'created_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }
    
    /**
     * Upload image from URL
     */
    private function upload_image_from_url($image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $image_id = media_sideload_image($image_url, 0, null, 'id');
        
        if (is_wp_error($image_id)) {
            return false;
        }
        
        return $image_id;
    }
}
