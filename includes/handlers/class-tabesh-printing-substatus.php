<?php
/**
 * Printing Substatus Management Class
 *
 * Handles advanced printing workflow sub-statuses for internal staff management.
 * This provides granular tracking of printing stages while keeping customer-facing
 * interface simple.
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_Printing_Substatus
 *
 * Manages printing workflow sub-statuses for internal staff operations.
 */
class Tabesh_Printing_Substatus {

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'tabesh_printing_substatus';
	}

	/**
	 * Initialize printing sub-statuses when order moves to printing stage.
	 *
	 * @param int $order_id Order ID.
	 * @return int|false Sub-status record ID or false on failure.
	 */
	public function initialize_printing_substatus( $order_id ) {
		global $wpdb;

		$order_id = intval( $order_id );
		if ( ! $order_id ) {
			return false;
		}

		// Check if sub-status already exists
		$existing = $this->get_printing_substatus( $order_id );
		if ( $existing ) {
			return $existing->id;
		}

		// Get order details to extract printing information
		$order_table = $wpdb->prefix . 'tabesh_orders';
		$order       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$order_table} WHERE id = %d",
				$order_id
			)
		);

		if ( ! $order ) {
			return false;
		}

		// Extract printing details from order
		$details = $this->extract_printing_details( $order );

		// Prepare additional services from extras
		$extras              = maybe_unserialize( $order->extras );
		$additional_services = array();
		if ( is_array( $extras ) ) {
			foreach ( $extras as $extra ) {
				$additional_services[] = array(
					'service'   => sanitize_text_field( $extra ),
					'completed' => 0,
				);
			}
		}

		// Insert new sub-status record
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'order_id'                 => $order_id,
				'cover_printing'           => 0,
				'cover_printing_details'   => $details['cover_printing_details'],
				'cover_lamination'         => 0,
				'cover_lamination_details' => $details['cover_lamination_details'],
				'text_printing'            => 0,
				'text_printing_details'    => $details['text_printing_details'],
				'binding'                  => 0,
				'binding_details'          => $details['binding_details'],
				'additional_services'      => wp_json_encode( $additional_services ),
				'completed_at'             => null,
				'completed_by'             => null,
				'created_at'               => current_time( 'mysql' ),
				'updated_at'               => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			// Log the initialization
			$this->log_substatus_change( $order_id, 'initialized', __( 'فرایند چاپ راه‌اندازی شد', 'tabesh' ) );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Extract printing details from order data
	 *
	 * @param object $order Order object.
	 * @return array Printing details
	 */
	private function extract_printing_details( $order ) {
		$details = array(
			'cover_printing_details'   => '',
			'cover_lamination_details' => '',
			'text_printing_details'    => '',
			'binding_details'          => '',
		);

		// Cover printing details (paper weight)
		if ( ! empty( $order->cover_paper_weight ) ) {
			$details['cover_printing_details'] = sprintf(
				__( 'کاغذ %s گرمی', 'tabesh' ),
				sanitize_text_field( $order->cover_paper_weight )
			);
		}

		// Cover lamination details
		if ( ! empty( $order->lamination_type ) ) {
			$lamination = sanitize_text_field( $order->lamination_type );
			if ( $lamination !== 'بدون سلفون' ) {
				$details['cover_lamination_details'] = $lamination;
			}
		}

		// Text printing details (paper type and weight)
		if ( ! empty( $order->paper_type ) && ! empty( $order->paper_weight ) ) {
			$details['text_printing_details'] = sprintf(
				__( 'کاغذ %1$s %2$s گرمی', 'tabesh' ),
				sanitize_text_field( $order->paper_type ),
				sanitize_text_field( $order->paper_weight )
			);
		}

		// Binding details
		if ( ! empty( $order->binding_type ) ) {
			$details['binding_details'] = sanitize_text_field( $order->binding_type );
		}

		return $details;
	}

	/**
	 * Get printing sub-status details for an order
	 *
	 * @param int $order_id Order ID
	 * @return object|null Sub-status object or null
	 */
	public function get_printing_substatus( $order_id ) {
		global $wpdb;

		$order_id = intval( $order_id );
		if ( ! $order_id ) {
			return null;
		}

		$substatus = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE order_id = %d",
				$order_id
			)
		);

		return $substatus;
	}

	/**
	 * Update a specific printing sub-status
	 *
	 * @param int    $order_id      Order ID.
	 * @param string $substatus_key Sub-status key. (e.g., 'cover_printing')
	 * @param int    $value         Value. (0 or 1)
	 * @return bool Success
	 */
	public function update_printing_substatus( $order_id, $substatus_key, $value ) {
		global $wpdb;

		$order_id      = intval( $order_id );
		$substatus_key = sanitize_text_field( $substatus_key );
		$value         = intval( $value );

		if ( ! $order_id || ! $substatus_key ) {
			return false;
		}

		// Validate substatus key
		$valid_keys = array( 'cover_printing', 'cover_lamination', 'text_printing', 'binding' );
		if ( ! in_array( $substatus_key, $valid_keys, true ) ) {
			return false;
		}

		// Check if sub-status exists
		$existing = $this->get_printing_substatus( $order_id );
		if ( ! $existing ) {
			// Initialize if not exists
			$this->initialize_printing_substatus( $order_id );
		}

		// Update the specific sub-status
		$result = $wpdb->update(
			$this->table_name,
			array(
				$substatus_key => $value,
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'order_id' => $order_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			// Log the change
			$label_map = array(
				'cover_printing'   => __( 'چاپ جلد', 'tabesh' ),
				'cover_lamination' => __( 'سلفون جلد', 'tabesh' ),
				'text_printing'    => __( 'چاپ متن کتاب', 'tabesh' ),
				'binding'          => __( 'صحافی', 'tabesh' ),
			);

			$status_text = $value ? __( 'انجام شد', 'tabesh' ) : __( 'لغو شد', 'tabesh' );
			$description = sprintf(
				'%s: %s',
				$label_map[ $substatus_key ] ?? $substatus_key,
				$status_text
			);

			$this->log_substatus_change( $order_id, 'updated', $description );

			// Check if all sub-statuses are completed
			if ( $value === 1 ) {
				$this->check_and_auto_complete( $order_id );
			}

			return true;
		}

		return false;
	}

	/**
	 * Update additional service status
	 *
	 * @param int    $order_id      Order ID.
	 * @param string $service_name  Service name
	 * @param int    $completed     Completed status (0 or 1)
	 * @return bool Success
	 */
	public function update_additional_service( $order_id, $service_name, $completed ) {
		global $wpdb;

		$order_id     = intval( $order_id );
		$service_name = sanitize_text_field( $service_name );
		$completed    = intval( $completed );

		if ( ! $order_id || ! $service_name ) {
			return false;
		}

		$substatus = $this->get_printing_substatus( $order_id );
		if ( ! $substatus ) {
			return false;
		}

		$services = json_decode( $substatus->additional_services, true );
		if ( ! is_array( $services ) ) {
			$services = array();
		}

		// Find and update the service
		$found = false;
		foreach ( $services as &$service ) {
			if ( $service['service'] === $service_name ) {
				$service['completed'] = $completed;
				$found                = true;
				break;
			}
		}

		if ( ! $found ) {
			// Add new service
			$services[] = array(
				'service'   => $service_name,
				'completed' => $completed,
			);
		}

		// Update database
		$result = $wpdb->update(
			$this->table_name,
			array(
				'additional_services' => wp_json_encode( $services ),
				'updated_at'          => current_time( 'mysql' ),
			),
			array( 'order_id' => $order_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			$this->log_substatus_change(
				$order_id,
				'service_updated',
				sprintf(
					'%s: %s',
					$service_name,
					$completed ? __( 'انجام شد', 'tabesh' ) : __( 'لغو شد', 'tabesh' )
				)
			);

			// Check if all completed
			if ( $completed === 1 ) {
				$this->check_and_auto_complete( $order_id );
			}

			return true;
		}

		return false;
	}

	/**
	 * Check if all printing sub-statuses are completed
	 *
	 * @param int $order_id Order ID
	 * @return bool True if all completed
	 */
	public function check_printing_completion( $order_id ) {
		$substatus = $this->get_printing_substatus( $order_id );
		if ( ! $substatus ) {
			return false;
		}

		// Check main sub-statuses
		$main_completed = (
			$substatus->cover_printing == 1 &&
			$substatus->text_printing == 1 &&
			$substatus->binding == 1
		);

		// Cover lamination is optional - only check if there's a lamination type
		if ( ! empty( $substatus->cover_lamination_details ) && $substatus->cover_lamination_details !== 'بدون سلفون' ) {
			$main_completed = $main_completed && $substatus->cover_lamination == 1;
		}

		// Check additional services
		$services           = json_decode( $substatus->additional_services, true );
		$services_completed = true;
		if ( is_array( $services ) && ! empty( $services ) ) {
			foreach ( $services as $service ) {
				if ( empty( $service['completed'] ) ) {
					$services_completed = false;
					break;
				}
			}
		}

		return $main_completed && $services_completed;
	}

	/**
	 * Check and auto-complete printing status when all sub-statuses done
	 *
	 * @param int $order_id Order ID
	 * @return bool Success
	 */
	private function check_and_auto_complete( $order_id ) {
		global $wpdb;

		if ( ! $this->check_printing_completion( $order_id ) ) {
			return false;
		}

		$current_user_id = get_current_user_id();

		// Mark sub-status as completed
		$result = $wpdb->update(
			$this->table_name,
			array(
				'completed_at' => current_time( 'mysql' ),
				'completed_by' => $current_user_id,
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'order_id' => $order_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			// Update main order status from 'processing' to 'ready'
			$order_table = $wpdb->prefix . 'tabesh_orders';
			$wpdb->update(
				$order_table,
				array( 'status' => 'ready' ),
				array( 'id' => $order_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Log completion
			$this->log_substatus_change( $order_id, 'completed', __( 'تمام مراحل چاپ تکمیل شد', 'tabesh' ) );

			// Trigger notification
			$this->send_completion_notification( $order_id );

			// Fire action hook for other integrations
			do_action( 'tabesh_printing_completed', $order_id, $current_user_id );

			return true;
		}

		return false;
	}

	/**
	 * Get completion percentage for an order
	 *
	 * @param int $order_id Order ID
	 * @return int Percentage (0-100)
	 */
	public function get_completion_percentage( $order_id ) {
		$substatus = $this->get_printing_substatus( $order_id );
		if ( ! $substatus ) {
			return 0;
		}

		$completed_count = 0;
		$total_count     = 0;

		// Count main sub-statuses (always count these)
		$main_statuses = array( 'cover_printing', 'text_printing', 'binding' );
		foreach ( $main_statuses as $status ) {
			++$total_count;
			if ( $substatus->$status == 1 ) {
				++$completed_count;
			}
		}

		// Count cover lamination if applicable
		if ( ! empty( $substatus->cover_lamination_details ) && $substatus->cover_lamination_details !== 'بدون سلفون' ) {
			++$total_count;
			if ( $substatus->cover_lamination == 1 ) {
				++$completed_count;
			}
		}

		// Count additional services
		$services = json_decode( $substatus->additional_services, true );
		if ( is_array( $services ) && ! empty( $services ) ) {
			foreach ( $services as $service ) {
				++$total_count;
				if ( ! empty( $service['completed'] ) ) {
					++$completed_count;
				}
			}
		}

		if ( $total_count === 0 ) {
			return 0;
		}

		return intval( ( $completed_count / $total_count ) * 100 );
	}

	/**
	 * Send completion notification to customer
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	private function send_completion_notification( $order_id ) {
		$notifications = Tabesh()->notifications;
		if ( ! $notifications ) {
			return;
		}

		global $wpdb;
		$order_table = $wpdb->prefix . 'tabesh_orders';
		$order       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$order_table} WHERE id = %d",
				$order_id
			)
		);

		if ( ! $order ) {
			return;
		}

		// Get user phone
		$user_phone = get_user_meta( $order->user_id, 'billing_phone', true );
		if ( empty( $user_phone ) ) {
			return;
		}

		// Send SMS
		$message = sprintf(
			__( 'سفارش شماره %s با موفقیت چاپ شد و آماده مرحله بعدی است.', 'tabesh' ),
			$order->order_number
		);

		// Use reflection to call private send_sms method
		$reflection = new ReflectionClass( $notifications );
		$method     = $reflection->getMethod( 'send_sms' );
		$method->setAccessible( true );
		$method->invoke( $notifications, $user_phone, $message );
	}

	/**
	 * Log sub-status change
	 *
	 * @param int    $order_id    Order ID
	 * @param string $action      Action type
	 * @param string $description Description
	 * @return void
	 */
	private function log_substatus_change( $order_id, $action, $description ) {
		global $wpdb;

		$current_user_id = get_current_user_id();
		$logs_table      = $wpdb->prefix . 'tabesh_logs';

		$wpdb->insert(
			$logs_table,
			array(
				'order_id'    => $order_id,
				'user_id'     => $current_user_id,
				'action'      => 'printing_substatus_' . sanitize_text_field( $action ),
				'description' => sanitize_text_field( $description ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
	}
}
