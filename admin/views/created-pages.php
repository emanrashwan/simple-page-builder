<?php
/**
 * Created Pages View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$pages_table = $wpdb->prefix . 'spb_pages_created';
$keys_table = $wpdb->prefix . 'spb_api_keys';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filter_key = isset($_GET['filter_key']) ? intval($_GET['filter_key']) : 0;

// Build query
$where_clauses = array();
if (!empty($search)) {
    $where_clauses[] = $wpdb->prepare("p.page_title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
}
if ($filter_key > 0) {
    $where_clauses[] = $wpdb->prepare("p.api_key_id = %d", $filter_key);
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$pages = $wpdb->get_results("
    SELECT p.*, k.key_name, k.api_key_preview
    FROM {$pages_table} p
    LEFT JOIN {$keys_table} k ON p.api_key_id = k.id
    {$where_sql}
    ORDER BY p.created_date DESC
    LIMIT 100
");

// Get all API keys for filter
$all_keys = $wpdb->get_results("SELECT id, key_name FROM {$keys_table} ORDER BY key_name");

// Statistics
$total_pages = $wpdb->get_var("SELECT COUNT(*) FROM {$pages_table}");
$pages_today = $wpdb->get_var("SELECT COUNT(*) FROM {$pages_table} WHERE DATE(created_date) = CURDATE()");
$pages_this_week = $wpdb->get_var("SELECT COUNT(*) FROM {$pages_table} WHERE YEARWEEK(created_date) = YEARWEEK(NOW())");
$pages_this_month = $wpdb->get_var("SELECT COUNT(*) FROM {$pages_table} WHERE YEAR(created_date) = YEAR(NOW()) AND MONTH(created_date) = MONTH(NOW())");
?>

<div class="spb-section">
    <h2>📄 Page Statistics</h2>
    <div class="spb-stats-grid">
        <div class="spb-stat-card">
            <div class="stat-label">Total Pages</div>
            <div class="stat-value"><?php echo number_format($total_pages); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">Today</div>
            <div class="stat-value" style="color: #46b450;"><?php echo number_format($pages_today); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">This Week</div>
            <div class="stat-value" style="color: #00a0d2;"><?php echo number_format($pages_this_week); ?></div>
        </div>
        <div class="spb-stat-card">
            <div class="stat-label">This Month</div>
            <div class="stat-value" style="color: #826eb4;"><?php echo number_format($pages_this_month); ?></div>
        </div>
    </div>
</div>

<div class="spb-section">
    <h2>Pages Created via API</h2>
    
    <!-- Search and Filters -->
    <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
        <form method="get" action="">
            <input type="hidden" name="page" value="simple-page-builder">
            <input type="hidden" name="tab" value="created-pages">
            
            <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="search" style="display: block; margin-bottom: 5px; font-weight: 600;">Search Pages</label>
                    <input type="text" id="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search by page title..." class="regular-text">
                </div>
                
                <div>
                    <label for="filter_key" style="display: block; margin-bottom: 5px; font-weight: 600;">Created By</label>
                    <select id="filter_key" name="filter_key" style="min-width: 200px;">
                        <option value="0">All API Keys</option>
                        <?php foreach ($all_keys as $key): ?>
                            <option value="<?php echo esc_attr($key->id); ?>" <?php selected($filter_key, $key->id); ?>>
                                <?php echo esc_html($key->key_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="button">🔍 Search</button>
                    <?php if (!empty($search) || $filter_key > 0): ?>
                        <a href="?page=simple-page-builder&tab=created-pages" class="button">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($pages)): ?>
        <div style="padding: 40px; text-align: center; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">
            <p style="font-size: 18px; color: #666; margin: 0;">📄 No pages found</p>
            <?php if (!empty($search) || $filter_key > 0): ?>
                <p style="color: #999;">Try adjusting your search or filters.</p>
            <?php else: ?>
                <p style="color: #999;">Pages created via API will appear here.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 25%;">Page Title</th>
                        <th style="width: 25%;">URL</th>
                        <th style="width: 15%;">Created By</th>
                        <th style="width: 15%;">Created Date</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <?php 
                        $page_exists = get_post($page->page_id);
                        $page_status = $page_exists ? get_post_status($page->page_id) : 'deleted';
                        ?>
                        <tr <?php if (!$page_exists) echo 'style="opacity: 0.5;"'; ?>>
                            <td>
                                <strong><?php echo esc_html($page->page_id); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo esc_html($page->page_title); ?></strong>
                                <?php if ($page_exists): ?>
                                    <br>
                                    <?php if ($page_status === 'publish'): ?>
                                        <span class="spb-badge spb-badge-success" style="font-size: 11px;">Published</span>
                                    <?php elseif ($page_status === 'draft'): ?>
                                        <span class="spb-badge spb-badge-warning" style="font-size: 11px;">Draft</span>
                                    <?php else: ?>
                                        <span class="spb-badge spb-badge-info" style="font-size: 11px;"><?php echo ucfirst($page_status); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <br><span class="spb-badge spb-badge-danger" style="font-size: 11px;">Deleted</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page_exists): ?>
                                    <a href="<?php echo esc_url($page->page_url); ?>" target="_blank" style="word-break: break-all;">
                                        <?php echo esc_html($page->page_url); ?> 🔗
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999; text-decoration: line-through;"><?php echo esc_html($page->page_url); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($page->key_name ?: 'Unknown'); ?></strong>
                                <br><code style="font-size: 11px;"><?php echo esc_html($page->api_key_preview); ?>***</code>
                            </td>
                            <td>
                                <strong><?php echo esc_html(date('M j, Y', strtotime($page->created_date))); ?></strong>
                                <br><span style="color: #999; font-size: 12px;"><?php echo esc_html(date('H:i:s', strtotime($page->created_date))); ?></span>
                            </td>
                            <td>
                                <?php if ($page_exists): ?>
                                    <a href="<?php echo get_edit_post_link($page->page_id); ?>" class="button button-small">
                                        ✏️ Edit
                                    </a>
                                    <a href="<?php echo esc_url($page->page_url); ?>" class="button button-small" target="_blank">
                                        👁️ View
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">Page deleted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 15px; padding: 12px; background: #f0f6fc; border
