<?php
/**
 * Print Substeps Management Class
 *
 * Handles detailed tracking of printing process substeps for orders in "processing" status.
 * Substeps are automatically generated from order specifications and allow staff to track
 * progress through individual printing stages.
 *
 * @package Tabesh
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tabesh_Print_Substeps {

	/**
	 * Get substeps for an order
	 *
	 * @param int $order_id Order ID
	 * @return array Array of substep objects
	 */
	public function get_order_substeps( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_print_substeps';

		// Get existing substeps
		$substeps = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE order_id = %d ORDER BY display_order ASC",
				$order_id
			)
		);

		// If no substeps exist, generate them
		if ( empty( $substeps ) ) {
			$this->generate_substeps_for_order( $order_id );
			$substeps = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE order_id = %d ORDER BY display_order ASC",
					$order_id
				)
			);
		}

		return $substeps;
	}

	/**
	 * Generate substeps for an order based on its specifications
	 *
	 * @param int $order_id Order ID
	 * @return bool Success status
	 */
	public function generate_substeps_for_order( $order_id ) {
		global $wpdb;
		$orders_table   = $wpdb->prefix . 'tabesh_orders';
		$substeps_table = $wpdb->prefix . 'tabesh_print_substeps';

		// Get order details
		$order = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $orders_table WHERE id = %d",
				$order_id
			)
		);

		if ( ! $order ) {
			return false;
		}

		// Check if substeps already exist
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $substeps_table WHERE order_id = %d",
				$order_id
			)
		);

		if ( $existing > 0 ) {
			// Substeps already exist, don't regenerate
			return true;
		}

		$substeps      = array();
		$display_order = 0;

		// 1. چاپ جلد - Cover Printing
		if ( ! empty( $order->cover_paper_weight ) ) {
			$substeps[] = array(
				'order_id'        => $order_id,
				'substep_key'     => 'cover_printing',
				'substep_title'   => 'چاپ جلد',
				'substep_details' => sprintf( 'گرماژ کاغذ: %s گرم', esc_html( $order->cover_paper_weight ) ),
				'is_completed'    => 0,
				'display_order'   => $display_order++,
			);
		}

		// 2. سلفون جلد - Cover Lamination
		if ( ! empty( $order->lamination_type ) && $order->lamination_type !== 'بدون سلفون' ) {
			$substeps[] = array(
				'order_id'        => $order_id,
				'substep_key'     => 'cover_lamination',
				'substep_title'   => 'سلفون جلد',
				'substep_details' => sprintf( 'نوع سلفون: %s', esc_html( $order->lamination_type ) ),
				'is_completed'    => 0,
				'display_order'   => $display_order++,
			);
		}

		// 3. چاپ متن کتاب - Book Content Printing
		if ( ! empty( $order->paper_type ) && ! empty( $order->paper_weight ) ) {
			$details = sprintf(
				'نوع کاغذ: %s - گرماژ: %s گرم',
				esc_html( $order->paper_type ),
				esc_html( $order->paper_weight )
			);

			$substeps[] = array(
				'order_id'        => $order_id,
				'substep_key'     => 'content_printing',
				'substep_title'   => 'چاپ متن کتاب',
				'substep_details' => $details,
				'is_completed'    => 0,
				'display_order'   => $display_order++,
			);
		}

		// 4. صحافی - Binding
		if ( ! empty( $order->binding_type ) ) {
			$substeps[] = array(
				'order_id'        => $order_id,
				'substep_key'     => 'binding',
				'substep_title'   => 'صحافی',
				'substep_details' => sprintf( 'نوع صحافی: %s', esc_html( $order->binding_type ) ),
				'is_completed'    => 0,
				'display_order'   => $display_order++,
			);
		}

		// 5. خدمات اضافی - Additional Services
		$extras = maybe_unserialize( $order->extras );
		if ( ! empty( $extras ) && is_array( $extras ) ) {
			$extras_list = implode( ', ', array_map( 'esc_html', $extras ) );
			$substeps[]  = array(
				'order_id'        => $order_id,
				'substep_key'     => 'extras',
				'substep_title'   => 'خدمات اضافی',
				'substep_details' => $extras_list,
				'is_completed'    => 0,
				'display_order'   => $display_order++,
			);
		}

		// 6. بستهبندی - Packaging (Always the last step)
		$substeps[] = array(
			'order_id'        => $order_id,
			'substep_key'     => 'packaging',
			'substep_title'   => 'بستهبندی',
			'substep_details' => 'آمادهسازی نهایی و بستهبندی سفارش',
			'is_completed'    => 0,
			'display_order'   => $display_order++,
		);

		// Insert substeps into database
		foreach ( $substeps as $substep ) {
			$wpdb->insert( $substeps_table, $substep );
		}

		return true;
	}

	/**
	 * Update substep status
	 *
	 * @param int  $substep_id Substep ID
	 * @param bool $is_completed Completion status
	 * @return bool Success status
	 */
	public function update_substep_status( $substep_id, $is_completed ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_print_substeps';

		// Get substep details before updating
		$substep = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$substep_id
			)
		);

		if ( ! $substep ) {
			return false;
		}

		$data = array(
			'is_completed' => $is_completed ? 1 : 0,
		);

		$staff_user_id = get_current_user_id();

		if ( $is_completed ) {
			$data['completed_at'] = current_time( 'mysql' );
			$data['completed_by'] = $staff_user_id;
		} else {
			$data['completed_at'] = null;
			$data['completed_by'] = null;
		}

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $substep_id ),
			array( '%d', '%s', '%d' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			// Log the substep change for tracking history
			$this->log_substep_change( $substep->order_id, $substep_id, $substep->substep_title, $is_completed, $staff_user_id );
		}

		return $result !== false;
	}

	/**
	 * Log substep change to history
	 *
	 * @param int    $order_id Order ID
	 * @param int    $substep_id Substep ID
	 * @param string $substep_title Substep title
	 * @param bool   $is_completed New completion status
	 * @param int    $staff_user_id User ID who made the change
	 * @return bool Success status
	 */
	private function log_substep_change( $order_id, $substep_id, $substep_title, $is_completed, $staff_user_id ) {
		global $wpdb;
		$logs_table   = $wpdb->prefix . 'tabesh_logs';
		$orders_table = $wpdb->prefix . 'tabesh_orders';

		// Get order customer user_id
		$customer_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $orders_table WHERE id = %d",
				$order_id
			)
		);

		if ( ! $customer_id ) {
			return false;
		}

		// Prepare log entry
		$action      = 'substep_change';
		$description = $is_completed
			? sprintf( __( 'مرحله "%s" تکمیل شد', 'tabesh' ), $substep_title )
			: sprintf( __( 'تکمیل مرحله "%s" لغو شد', 'tabesh' ), $substep_title );

		// Get staff user info for display
		$staff_user = get_userdata( $staff_user_id );
		$staff_name = $staff_user ? $staff_user->display_name : __( 'نامشخص', 'tabesh' );

		$old_status = $is_completed ? 'incomplete' : 'completed';
		$new_status = $is_completed ? 'completed' : 'incomplete';

		// Insert log entry
		$result = $wpdb->insert(
			$logs_table,
			array(
				'order_id'      => $order_id,
				'user_id'       => $customer_id,
				'staff_user_id' => $staff_user_id,
				'action'        => $action,
				'old_status'    => $old_status,
				'new_status'    => $new_status,
				'description'   => $description,
				'details'       => json_encode(
					array(
						'substep_id'    => $substep_id,
						'substep_title' => $substep_title,
						'staff_name'    => $staff_name,
					)
				),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Calculate print progress percentage for an order
	 *
	 * @param int $order_id Order ID
	 * @return int Progress percentage (0-100)
	 */
	public function calculate_print_progress( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_print_substeps';

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE order_id = %d",
				$order_id
			)
		);

		if ( $total == 0 ) {
			return 0;
		}

		$completed = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE order_id = %d AND is_completed = 1",
				$order_id
			)
		);

		return intval( ( $completed / $total ) * 100 );
	}

	/**
	 * Check if all substeps are completed for an order
	 *
	 * @param int $order_id Order ID
	 * @return bool True if all substeps completed
	 */
	public function are_all_substeps_completed( $order_id ) {
		return $this->calculate_print_progress( $order_id ) === 100;
	}

	/**
	 * REST API endpoint handler for updating substep status
	 *
	 * @param WP_REST_Request $request REST request object
	 * @return WP_REST_Response REST response
	 */
	public function update_substep_rest( $request ) {
		// Get request data
		$substep_id   = intval( $request->get_param( 'substep_id' ) );
		$is_completed = filter_var( $request->get_param( 'is_completed' ), FILTER_VALIDATE_BOOLEAN );

		if ( $substep_id <= 0 ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'شناسه زیرمجموعه نامعتبر است', 'tabesh' ),
				),
				400
			);
		}

		// Get substep details to find order_id
		global $wpdb;
		$substeps_table = $wpdb->prefix . 'tabesh_print_substeps';
		$substep        = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $substeps_table WHERE id = %d",
				$substep_id
			)
		);

		if ( ! $substep ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'زیرمجموعه یافت نشد', 'tabesh' ),
				),
				404
			);
		}

		// Update substep status
		$result = $this->update_substep_status( $substep_id, $is_completed );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'خطا در بهروزرسانی وضعیت', 'tabesh' ),
				),
				500
			);
		}

		// Calculate progress
		$progress      = $this->calculate_print_progress( $substep->order_id );
		$all_completed = $this->are_all_substeps_completed( $substep->order_id );

		// Log substep completion for tracking (without changing order status)
		if ( $all_completed ) {
			$logs_table   = $wpdb->prefix . 'tabesh_logs';
			$orders_table = $wpdb->prefix . 'tabesh_orders';

			// Get current order info
			$current_order = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $orders_table WHERE id = %d",
					$substep->order_id
				)
			);

			$staff_user_id = get_current_user_id();

			// Only log if this is the first time all substeps are completed
			$existing_log = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $logs_table 
                 WHERE order_id = %d 
                 AND action = %s",
					$substep->order_id,
					'substeps_completed'
				)
			);

			if ( $existing_log == 0 ) {
				$wpdb->insert(
					$logs_table,
					array(
						'order_id'      => $substep->order_id,
						'user_id'       => $current_order->user_id,
						'staff_user_id' => $staff_user_id,
						'action'        => 'substeps_completed',
						'description'   => __( 'تمام مراحل چاپ تکمیل شد - آماده برای تغییر وضعیت به "آماده تحویل"', 'tabesh' ),
					),
					array( '%d', '%d', '%d', '%s', '%s' )
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'وضعیت با موفقیت بهروزرسانی شد', 'tabesh' ),
				'data'    => array(
					'order_id'      => $substep->order_id,
					'progress'      => $progress,
					'all_completed' => $all_completed,
				),
			),
			200
		);
	}

	/**
	 * Delete substeps for an order
	 * Used when order status changes away from "processing"
	 *
	 * @param int $order_id Order ID
	 * @return bool Success status
	 */
	public function delete_order_substeps( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_print_substeps';

		$result = $wpdb->delete(
			$table,
			array( 'order_id' => $order_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get substep change history for an order
	 *
	 * @param int $order_id Order ID
	 * @return array Array of log entries
	 */
	public function get_substep_history( $order_id ) {
		global $wpdb;
		$logs_table = $wpdb->prefix . 'tabesh_logs';

		$history = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, u.display_name as staff_name 
             FROM $logs_table l 
             LEFT JOIN {$wpdb->users} u ON l.staff_user_id = u.ID
             WHERE l.order_id = %d AND l.action = 'substep_change'
             ORDER BY l.created_at DESC",
				$order_id
			)
		);

		return $history ? $history : array();
	}
}
