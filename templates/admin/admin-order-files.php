<?php
/**
 * Admin Order Files Template
 * View and manage uploaded files for orders
 *
 * @package Tabesh
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$files_table = $wpdb->prefix . 'tabesh_files';
$orders_table = $wpdb->prefix . 'tabesh_orders';

// Get current page and filters
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

// Build query
$where_conditions = array("f.deleted_at IS NULL");
$query_params = array();

if (!empty($search)) {
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_conditions[] = "(f.original_filename LIKE %s OR f.stored_filename LIKE %s OR o.order_number LIKE %s OR o.book_title LIKE %s)";
    $query_params = array_merge($query_params, array($search_term, $search_term, $search_term, $search_term));
}

if (!empty($status_filter)) {
    $where_conditions[] = "f.status = %s";
    $query_params[] = $status_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "f.file_type = %s";
    $query_params[] = $type_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) FROM $files_table f LEFT JOIN $orders_table o ON f.order_id = o.id WHERE $where_clause";
if (!empty($query_params)) {
    $total_items = $wpdb->get_var($wpdb->prepare($count_query, ...$query_params));
} else {
    $total_items = $wpdb->get_var($count_query);
}

$total_pages = ceil($total_items / $per_page);

// Get files
$query_params_with_pagination = $query_params;
$query_params_with_pagination[] = $per_page;
$query_params_with_pagination[] = $offset;

$query = "SELECT f.*, o.order_number, o.book_title, u.display_name as user_name 
          FROM $files_table f 
          LEFT JOIN $orders_table o ON f.order_id = o.id 
          LEFT JOIN {$wpdb->users} u ON f.user_id = u.ID
          WHERE $where_clause 
          ORDER BY f.created_at DESC 
          LIMIT %d OFFSET %d";

$files = $wpdb->get_results($wpdb->prepare($query, ...$query_params_with_pagination));

// Status labels
$status_labels = array(
    'pending' => __('در انتظار بررسی', 'tabesh'),
    'approved' => __('تایید شده', 'tabesh'),
    'rejected' => __('رد شده', 'tabesh')
);

// Type labels
$type_labels = array(
    'text' => __('متن کتاب', 'tabesh'),
    'cover' => __('جلد کتاب', 'tabesh'),
    'documents' => __('مدارک', 'tabesh')
);
?>

<div class="wrap tabesh-admin-files" dir="rtl">
    <h1 class="wp-heading-inline"><?php esc_html_e('فایل‌های سفارشات', 'tabesh'); ?></h1>
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="tabesh-files-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="tabesh-files">
            
            <!-- Search -->
            <p class="search-box">
                <label class="screen-reader-text" for="file-search-input"><?php esc_html_e('جستجو', 'tabesh'); ?></label>
                <input type="search" id="file-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('جستجوی نام فایل، شماره سفارش...', 'tabesh'); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('جستجو', 'tabesh'); ?>">
            </p>

            <!-- Filter by Status -->
            <select name="status">
                <option value=""><?php esc_html_e('همه وضعیت‌ها', 'tabesh'); ?></option>
                <?php foreach ($status_labels as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($status_filter, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Filter by Type -->
            <select name="type">
                <option value=""><?php esc_html_e('همه انواع', 'tabesh'); ?></option>
                <?php foreach ($type_labels as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($type_filter, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" class="button" value="<?php esc_attr_e('فیلتر', 'tabesh'); ?>">
            
            <?php if (!empty($search) || !empty($status_filter) || !empty($type_filter)): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tabesh-files')); ?>" class="button">
                    <?php esc_html_e('پاک کردن فیلترها', 'tabesh'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Stats -->
    <div class="tabesh-files-stats">
        <span class="stat-item">
            <?php 
            // translators: %d is number of files
            echo esc_html(sprintf(__('مجموع: %d فایل', 'tabesh'), $total_items)); 
            ?>
        </span>
    </div>

    <!-- Files Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-file"><?php esc_html_e('فایل', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-order"><?php esc_html_e('سفارش', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-user"><?php esc_html_e('کاربر', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-type"><?php esc_html_e('نوع', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-size"><?php esc_html_e('حجم', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-version"><?php esc_html_e('نسخه', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e('وضعیت', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-date"><?php esc_html_e('تاریخ', 'tabesh'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php esc_html_e('عملیات', 'tabesh'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($files)): ?>
                <tr>
                    <td colspan="9" class="no-items">
                        <?php esc_html_e('فایلی یافت نشد.', 'tabesh'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <tr data-file-id="<?php echo esc_attr($file->id); ?>">
                        <td class="column-file">
                            <strong><?php echo esc_html($file->stored_filename); ?></strong>
                            <br>
                            <span class="description"><?php echo esc_html($file->original_filename); ?></span>
                        </td>
                        <td class="column-order">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=tabesh-orders&order_id=' . $file->order_id)); ?>">
                                #<?php echo esc_html($file->order_number); ?>
                            </a>
                            <br>
                            <span class="description"><?php echo esc_html($file->book_title ?: __('بدون عنوان', 'tabesh')); ?></span>
                        </td>
                        <td class="column-user">
                            <?php echo esc_html($file->user_name ?: __('نامشخص', 'tabesh')); ?>
                        </td>
                        <td class="column-type">
                            <?php echo esc_html($type_labels[$file->file_type] ?? $file->file_type); ?>
                        </td>
                        <td class="column-size">
                            <?php echo esc_html(size_format($file->file_size)); ?>
                        </td>
                        <td class="column-version">
                            v<?php echo esc_html($file->version); ?>
                        </td>
                        <td class="column-status">
                            <span class="status-badge status-<?php echo esc_attr($file->status); ?>">
                                <?php echo esc_html($status_labels[$file->status] ?? $file->status); ?>
                            </span>
                            <?php if ($file->status === 'rejected' && !empty($file->rejection_reason)): ?>
                                <br>
                                <small class="rejection-reason" title="<?php echo esc_attr($file->rejection_reason); ?>">
                                    <?php echo esc_html(wp_trim_words($file->rejection_reason, 10)); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($file->created_at))); ?>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="download">
                                    <a href="<?php echo esc_url(rest_url(TABESH_REST_NAMESPACE . '/download-file/' . $file->id)); ?>" class="btn-download" target="_blank">
                                        <?php esc_html_e('دانلود', 'tabesh'); ?>
                                    </a>
                                </span>
                                <?php if ($file->status === 'pending'): ?>
                                    | <span class="approve">
                                        <a href="#" class="btn-approve" data-file-id="<?php echo esc_attr($file->id); ?>">
                                            <?php esc_html_e('تایید', 'tabesh'); ?>
                                        </a>
                                    </span>
                                    | <span class="reject">
                                        <a href="#" class="btn-reject" data-file-id="<?php echo esc_attr($file->id); ?>">
                                            <?php esc_html_e('رد', 'tabesh'); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tabindex-pagination">
            <?php
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'total' => $total_pages,
                'current' => $current_page,
                'prev_text' => '&laquo; ' . __('قبلی', 'tabesh'),
                'next_text' => __('بعدی', 'tabesh') . ' &raquo;'
            );
            echo wp_kses_post(paginate_links($pagination_args));
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="tabesh-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <h3><?php esc_html_e('دلیل رد فایل', 'tabesh'); ?></h3>
        <textarea id="reject-reason" rows="4" placeholder="<?php esc_attr_e('دلیل رد فایل را وارد کنید...', 'tabesh'); ?>"></textarea>
        <div class="modal-actions">
            <button type="button" class="button button-primary" id="confirm-reject"><?php esc_html_e('تایید رد', 'tabesh'); ?></button>
            <button type="button" class="button" id="cancel-reject"><?php esc_html_e('انصراف', 'tabesh'); ?></button>
        </div>
    </div>
</div>

<style>
.tabesh-admin-files .tabesh-files-filters {
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.tabesh-admin-files .tabesh-files-stats {
    margin: 15px 0;
    padding: 10px 15px;
    background: #f0f0f1;
    border-radius: 4px;
}

.tabesh-admin-files .stat-item {
    margin-left: 20px;
    font-weight: 500;
}

.tabesh-admin-files .status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.tabesh-admin-files .status-pending {
    background: #fff3cd;
    color: #856404;
}

.tabesh-admin-files .status-approved {
    background: #d4edda;
    color: #155724;
}

.tabesh-admin-files .status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.tabesh-admin-files .rejection-reason {
    color: #721c24;
    font-style: italic;
}

.tabesh-admin-files .column-file {
    width: 20%;
}

.tabesh-admin-files .column-order {
    width: 15%;
}

.tabesh-admin-files .column-actions {
    width: 15%;
}

.tabesh-admin-files .tabindex-pagination {
    margin: 20px 0;
    text-align: center;
}

.tabesh-admin-files .tabindex-pagination .page-numbers {
    display: inline-block;
    padding: 5px 12px;
    margin: 0 3px;
    background: #f0f0f1;
    border-radius: 3px;
    text-decoration: none;
}

.tabesh-admin-files .tabindex-pagination .page-numbers.current {
    background: #2271b1;
    color: white;
}

/* Modal Styles */
.tabesh-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tabesh-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
}

.tabesh-modal .modal-content {
    position: relative;
    background: white;
    padding: 25px;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
}

.tabesh-modal .modal-content h3 {
    margin: 0 0 15px;
}

.tabesh-modal .modal-content textarea {
    width: 100%;
    margin-bottom: 15px;
}

.tabesh-modal .modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentFileId = null;
    var restUrl = '<?php echo esc_js(rest_url(TABESH_REST_NAMESPACE)); ?>';
    var nonce = '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>';

    // Approve file
    $(document).on('click', '.btn-approve', function(e) {
        e.preventDefault();
        var fileId = $(this).data('file-id');
        
        if (!confirm('<?php echo esc_js(__('آیا از تایید این فایل اطمینان دارید؟', 'tabesh')); ?>')) {
            return;
        }

        $.ajax({
            url: restUrl + '/approve-file/' + fileId,
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('خطا در ارتباط با سرور', 'tabesh')); ?>');
            }
        });
    });

    // Open reject modal
    $(document).on('click', '.btn-reject', function(e) {
        e.preventDefault();
        currentFileId = $(this).data('file-id');
        $('#reject-reason').val('');
        $('#reject-modal').show();
    });

    // Close reject modal
    $('#cancel-reject, #reject-modal .modal-overlay').on('click', function() {
        $('#reject-modal').hide();
        currentFileId = null;
    });

    // Confirm reject
    $('#confirm-reject').on('click', function() {
        var reason = $('#reject-reason').val().trim();
        
        if (!reason) {
            alert('<?php echo esc_js(__('لطفا دلیل رد فایل را وارد کنید', 'tabesh')); ?>');
            return;
        }

        $.ajax({
            url: restUrl + '/reject-file/' + currentFileId,
            method: 'POST',
            headers: { 'X-WP-Nonce': nonce },
            data: { reason: reason },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('خطا در ارتباط با سرور', 'tabesh')); ?>');
            }
        });
    });
});
</script>
