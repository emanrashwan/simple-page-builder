<?php
/**
 * Activity Log View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$log_table = $wpdb->prefix . 'spb_activity_log';
$keys_table = $wpdb->prefix . 'spb_api_keys';

// Get filter parameters
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : 'all';
$filter_key = isset($_GET['filter_key']) ? intval($_GET['filter_key']) : 0;

// Build query
$where_clauses = array();
if ($filter_status !== 'all') {
    $where_clauses[] = $wpdb->prepare("l.status = %s", $filter_status);
}
if ($filter_key > 0) {
    $where_clauses[] = $wpdb->prepare("l.api_key_id = %d", $filter_key);
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$logs = $wpdb->get_results("
    SELECT l.*, k.key_name, k.api_key_preview
    FROM {$log_table} l
    LEFT JOIN {$keys_table} k ON l.api_key_id = k.id
    {$where_sql}
    ORDER BY l.created_date DESC
    LIMIT 100
");

// Get all API keys for filter
$all_keys = $wpdb->get_results("SELECT id, key_name FROM {$keys_table} ORDER BY key_name");

// Calculate statistics
$total_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$log_table}");
$successful_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$log_table} WHERE status = 'success'");
$failed_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$log_table} WHERE status != 'success'");
$total_pages_created = $wpdb->get_var("SELECT SUM(pages_created) FROM {$log_table}");
$avg_response_time = $wpdb->get_var("SELECT AVG(response_time) FROM {$log_table}");
?>

<div class="spb-section">
    <h2>📊 Activity Statistics</h2>
    <div class="spb-stats-grid">
        <div class="spb-stat-card">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value"><?php echo number_format($total_requests); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">Successful</div>
            <div class="stat-value" style="color: #46b450;"><?php echo number_format($successful_requests); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">Failed</div>
            <div class="stat-value" style="color: #dc3232;"><?php echo number_format($failed_requests); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">Pages Created</div>
            <div class="stat-value" style="color: #00a0d2;"><?php echo number_format($total_pages_created); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">Avg Response Time</div>
            <div class="stat-value" style="font-size: 24px;"><?php echo number_format($avg_response_time, 3); ?>s</div>
        </div>
    </div>
</div>

<div class="spb-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">Recent API Activity</h2>
        <div>
            <button class="button spb-refresh-stats" onclick="location.reload()">🔄 Refresh</button>
        </div>
    </div>
    
    <!-- Filters -->
    <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
        <form method="get" action="">
            <input type="hidden" name="page" value="simple-page-builder">
            <input type="hidden" name="tab" value="activity-log">
            
            <div style="display: flex; gap: 15px; align-items: end;">
                <div>
                    <label for="filter_status" style="display: block; margin-bottom: 5px; font-weight: 600;">Status</label>
                    <select id="filter_status" name="filter_status" style="min-width: 150px;">
                        <option value="all" <?php selected($filter_status, 'all'); ?>>All Statuses</option>
                        <option value="success" <?php selected($filter_status, 'success'); ?>>Success</option>
                        <option value="failed" <?php selected($filter_status, 'failed'); ?>>Failed</option>
                        <option value="partial_success" <?php selected($filter_status, 'partial_success'); ?>>Partial Success</option>
                    </select>
                </div>
                
                <div>
                    <label for="filter_key" style="display: block; margin-bottom: 5px; font-weight: 600;">API Key</label>
                    <select id="filter_key" name="filter_key" style="min-width: 200px;">
                        <option value="0">All Keys</option>
                        <?php foreach ($all_keys as $key): ?>
                            <option value="<?php echo esc_attr($key->id); ?>" <?php selected($filter_key, $key->id); ?>>
                                <?php echo esc_html($key->key_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="button">🔍 Filter</button>
                    <?php if ($filter_status !== 'all' || $filter_key > 0): ?>
                        <a href="?page=simple-page-builder&tab=activity-log" class="button">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($logs)): ?>
        <div style="padding: 40px; text-align: center; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">
            <p style="font-size: 18px; color: #666; margin: 0;">📋 No activity logs found</p>
            <p style="color: #999;">Activity will appear here after API requests are made.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 15%;">Timestamp</th>
                        <th style="width: 18%;">API Key</th>
                        <th style="width: 20%;">Request ID</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Pages</th>
                        <th style="width: 12%;">Response Time</th>
                        <th style="width: 15%;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(date('M j, Y', strtotime($log->created_date))); ?></strong>
                                <br><span style="color: #999; font-size: 12px;"><?php echo esc_html(date('H:i:s', strtotime($log->created_date))); ?></span>
                            </td>
                            <td>
                                <strong><?php echo esc_html($log->key_name ?: 'Unknown'); ?></strong>
                                <br><code style="font-size: 11px;"><?php echo esc_html($log->api_key_preview); ?>***</code>
                            </td>
                            <td>
                                <code style="font-size: 11px;"><?php echo esc_html($log->request_id); ?></code>
                            </td>
                            <td>
                                <?php if ($log->status === 'success'): ?>
                                    <span class="spb-badge spb-badge-success">✓ Success</span>
                                <?php elseif ($log->status === 'partial_success'): ?>
                                    <span class="spb-badge spb-badge-warning">⚠ Partial</span>
                                <?php else: ?>
                                    <span class="spb-badge spb-badge-danger">✗ Failed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="font-size: 16px;"><?php echo esc_html($log->pages_created); ?></strong>
                            </td>
                            <td>
                                <?php 
                                $response_time = round($log->response_time, 3);
                                $time_color = $response_time < 1 ? '#46b450' : ($response_time < 3 ? '#ffb900' : '#dc3232');
                                ?>
                                <span style="color: <?php echo $time_color; ?>; font-weight: 600;">
                                    <?php echo esc_html($response_time); ?>s
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 11px;"><?php echo esc_html($log->ip_address); ?></code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 4px;">
            <strong>ℹ️ Note:</strong> Showing the most recent 100 requests. 
            <?php if ($filter_status !== 'all' || $filter_key > 0): ?>
                Filters are active.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
