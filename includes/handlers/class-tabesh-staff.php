<?php
/**
 * Staff Management Class
 *
 * @package Tabesh
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Tabesh_Staff {

    /**
     * Constructor
     */
    public function __construct() {
        // Initialization
    }

    /**
     * Update order status via REST API
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_status_rest($request) {
        $params = $request->get_json_params();
        $order_id = intval($params['order_id'] ?? 0);
        $status = sanitize_text_field($params['status'] ?? '');

        if (!$order_id || !$status) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('پارامترهای ناقص', 'tabesh')
            ), 400);
        }

        // Get current order to track old status
        global $wpdb;
        $table = $wpdb->prefix . 'tabesh_orders';
        $current_order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $order_id
        ));

        if (!$current_order) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('سفارش یافت نشد', 'tabesh')
            ), 404);
        }

        $old_status = $current_order->status;

        // Update order status
        $order = Tabesh()->order;
        $result = $order->update_status($order_id, $status);

        if ($result) {
            // Log the status change with staff information
            $current_user = wp_get_current_user();
            $staff_user_id = get_current_user_id();
            
            $logs_table = $wpdb->prefix . 'tabesh_logs';
            $wpdb->insert(
                $logs_table,
                array(
                    'order_id' => $order_id,
                    'user_id' => $current_order->user_id,
                    'staff_user_id' => $staff_user_id,
                    'action' => 'status_change',
                    'old_status' => $old_status,
                    'new_status' => $status,
                    'description' => sprintf(
                        __('وضعیت توسط %s از "%s" به "%s" تغییر کرد', 'tabesh'),
                        $current_user->display_name,
                        $old_status,
                        $status
                    )
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s')
            );

            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('وضعیت با موفقیت به‌روزرسانی شد', 'tabesh'),
                'staff_name' => $current_user->display_name,
                'old_status' => $old_status,
                'new_status' => $status
            ), 200);
        }

        return new WP_REST_Response(array(
            'success' => false,
            'message' => __('خطا در به‌روزرسانی وضعیت', 'tabesh')
        ), 400);
    }
    
    /**
     * Search orders via REST API
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function search_orders_rest($request) {
        $query = sanitize_text_field($request->get_param('q') ?? '');
        $page = intval($request->get_param('page') ?? 1);
        $per_page = intval($request->get_param('per_page') ?? 3);
        
        if (empty($query)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('عبارت جستجو خالی است', 'tabesh')
            ), 400);
        }
        
        $results = $this->search_orders($query, $page, $per_page);
        
        return new WP_REST_Response(array(
            'success' => true,
            'results' => $results['orders'],
            'total' => $results['total'],
            'page' => $page,
            'per_page' => $per_page,
            'has_more' => $results['has_more']
        ), 200);
    }
    
    /**
     * Search orders by various criteria
     *
     * @param string $query Search query
     * @param int $page Page number
     * @param int $per_page Results per page
     * @return array Search results
     */
    public function search_orders($query, $page = 1, $per_page = 3) {
        global $wpdb;
        $table = $wpdb->prefix . 'tabesh_orders';
        
        $offset = ($page - 1) * $per_page;
        $query_like = '%' . $wpdb->esc_like($query) . '%';
        
        // Search in multiple fields
        $sql = $wpdb->prepare(
            "SELECT * FROM $table 
            WHERE archived = 0 
            AND (
                order_number LIKE %s 
                OR book_title LIKE %s 
                OR book_size LIKE %s
                OR paper_type LIKE %s
                OR print_type LIKE %s
                OR binding_type LIKE %s
            )
            ORDER BY 
                CASE 
                    WHEN order_number LIKE %s THEN 1
                    WHEN book_title LIKE %s THEN 2
                    WHEN book_size LIKE %s THEN 3
                    ELSE 4
                END,
                created_at DESC
            LIMIT %d OFFSET %d",
            $query_like, $query_like, $query_like, $query_like, $query_like, $query_like,
            $query_like, $query_like, $query_like,
            $per_page + 1, $offset
        );
        
        $results = $wpdb->get_results($sql);
        
        // Check if there are more results
        $has_more = count($results) > $per_page;
        if ($has_more) {
            array_pop($results); // Remove the extra result
        }
        
        // Get total count
        $count_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE archived = 0 
            AND (
                order_number LIKE %s 
                OR book_title LIKE %s 
                OR book_size LIKE %s
                OR paper_type LIKE %s
                OR print_type LIKE %s
                OR binding_type LIKE %s
            )",
            $query_like, $query_like, $query_like, $query_like, $query_like, $query_like
        );
        
        $total = $wpdb->get_var($count_sql);
        
        return array(
            'orders' => $results,
            'total' => intval($total),
            'has_more' => $has_more
        );
    }

    /**
     * Get assigned orders for staff
     *
     * @param int $staff_id
     * @return array
     */
    public function get_assigned_orders($staff_id = null) {
        if ($staff_id === null) {
            $staff_id = get_current_user_id();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'tabesh_orders';

        // For now, staff can see all active orders
        // In future, implement assignment system
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE archived = %d ORDER BY created_at DESC", 0
        ));
    }

    /**
     * Render staff panel shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_staff_panel($atts) {
        if (!current_user_can('edit_shop_orders')) {
            return '<p>' . __('شما اجازه دسترسی به این بخش را ندارید.', 'tabesh') . '</p>';
        }

        ob_start();
        include TABESH_PLUGIN_DIR . 'templates/frontend/staff-panel.php';
        return ob_get_clean();
    }
    
    /**
     * Update order sub-status via REST API
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_sub_status_rest($request) {
        $params = $request->get_json_params();
        $order_id = intval($params['order_id'] ?? 0);
        $sub_status_key = sanitize_text_field($params['sub_status_key'] ?? '');
        $completed = filter_var($params['completed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$order_id || !$sub_status_key) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('پارامترهای ناقص', 'tabesh')
            ), 400);
        }

        // Verify order exists
        global $wpdb;
        $table = $wpdb->prefix . 'tabesh_orders';
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $order_id
        ));

        if (!$order) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('سفارش یافت نشد', 'tabesh')
            ), 404);
        }

        // Update sub-status
        $order_handler = Tabesh()->order;
        $result = $order_handler->update_sub_status($order_id, $sub_status_key, $completed);

        if (!$result) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('خطا در به‌روزرسانی وضعیت فرعی', 'tabesh')
            ), 400);
        }

        // Check if all sub-statuses are completed
        $should_auto_complete = $order_handler->should_auto_complete_printing($order_id);
        $status_changed = false;
        $new_status = $order->status;

        if ($should_auto_complete && $order->status === 'processing') {
            // Auto-complete the printing status to "ready"
            $order_handler->update_status($order_id, 'ready');
            $status_changed = true;
            $new_status = 'ready';
            
            // Log the auto-completion
            $current_user = wp_get_current_user();
            $staff_user_id = get_current_user_id();
            
            $logs_table = $wpdb->prefix . 'tabesh_logs';
            $wpdb->insert(
                $logs_table,
                array(
                    'order_id' => $order_id,
                    'user_id' => $order->user_id,
                    'staff_user_id' => $staff_user_id,
                    'action' => 'status_auto_complete',
                    'old_status' => 'processing',
                    'new_status' => 'ready',
                    'description' => sprintf(
                        __('وضعیت به صورت خودکار از "در حال چاپ" به "آماده تحویل" تغییر کرد (تمام مراحل چاپ کامل شد - توسط %s)', 'tabesh'),
                        $current_user->display_name
                    )
                ),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s')
            );
        }

        // Get updated completion percentage
        $completion = $order_handler->get_sub_status_completion($order_id);
        $current_user = wp_get_current_user();

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('وضعیت فرعی با موفقیت به‌روزرسانی شد', 'tabesh'),
            'completion' => $completion,
            'status_changed' => $status_changed,
            'new_status' => $new_status,
            'staff_name' => $current_user->display_name
        ), 200);
    }
}
