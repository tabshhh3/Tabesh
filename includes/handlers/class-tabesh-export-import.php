<?php
/**
 * Export/Import Handler Class
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_Export_Import
 *
 * Handles export and import functionality for Tabesh plugin data.
 * Supports exporting and importing orders, settings, customers, logs,
 * files, and other plugin-related data with validation and preview.
 *
 * @since 1.0.3
 */
class Tabesh_Export_Import {

	/**
	 * Available sections for export/import
	 *
	 * @var array
	 */
	private $available_sections = array(
		'orders'               => 'سفارشات',
		'settings'             => 'تنظیمات',
		'customers'            => 'مشتریان',
		'logs'                 => 'تاریخچه رویدادها',
		'files'                => 'فایل‌ها',
		'file_versions'        => 'نسخه‌های فایل',
		'upload_tasks'         => 'وظایف آپلود',
		'book_format_settings' => 'تنظیمات فرمت کتاب',
		'file_comments'        => 'نظرات فایل',
		'document_metadata'    => 'متادیتای اسناد',
		'download_tokens'      => 'توکن‌های دانلود',
		'security_logs'        => 'لاگ‌های امنیتی',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// No hooks needed - methods called directly via REST API.
	}

	/**
	 * Get available sections
	 *
	 * @return array
	 */
	public function get_available_sections() {
		return $this->available_sections;
	}

	/**
	 * Validate and sanitize section name
	 *
	 * @param string $section Section name.
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_section( $section ) {
		return isset( $this->available_sections[ $section ] );
	}

	/**
	 * Get table name for a section (with validation)
	 *
	 * @param string $section Section name.
	 * @return string|false Table name or false if invalid.
	 */
	private function get_table_name( $section ) {
		global $wpdb;

		if ( ! $this->is_valid_section( $section ) ) {
			return false;
		}

		// Special handling for customers (uses wp_users table).
		if ( $section === 'customers' ) {
			return $wpdb->users;
		}

		// Map section to table name with prefix.
		return $wpdb->prefix . 'tabesh_' . $section;
	}

	/**
	 * Safely execute SELECT * query on validated table
	 *
	 * @param string $section Section name.
	 * @return array Query results or empty array.
	 */
	private function get_table_data( $section ) {
		global $wpdb;

		$table = $this->get_table_name( $section );
		if ( ! $table ) {
			return array();
		}

		// Use esc_sql for table name since it's already validated from whitelist.
		$table_escaped = esc_sql( $table );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT * FROM {$table_escaped}", ARRAY_A );

		return $results ? $results : array();
	}

	/**
	 * Export data sections
	 *
	 * @param array $sections Sections to export
	 * @return array Export data
	 */
	public function export( $sections = array() ) {
		// If no sections specified, export all
		if ( empty( $sections ) ) {
			$sections = array_keys( $this->available_sections );
		}

		$export_data = array(
			'version'     => TABESH_VERSION,
			'export_date' => current_time( 'mysql' ),
			'site_url'    => get_site_url(),
			'sections'    => array(),
		);

		foreach ( $sections as $section ) {
			if ( ! isset( $this->available_sections[ $section ] ) ) {
				continue;
			}

			$method = 'export_' . $section;
			if ( method_exists( $this, $method ) ) {
				$export_data['sections'][ $section ] = $this->$method();
			}
		}

		return $export_data;
	}

	/**
	 * Import data sections
	 *
	 * @param array  $data Import data
	 * @param array  $sections Sections to import
	 * @param string $mode Import mode: 'merge' or 'replace'
	 * @return array Result with success status and message
	 */
	public function import( $data, $sections = array(), $mode = 'merge' ) {
		global $wpdb;

		// Validate import data
		$validation = $this->validate_import_data( $data );
		if ( ! $validation['valid'] ) {
			return array(
				'success' => false,
				'message' => $validation['message'],
			);
		}

		// If no sections specified, import all available in data
		if ( empty( $sections ) ) {
			$sections = array_keys( $data['sections'] );
		}

		$results = array();
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $sections as $section ) {
				if ( ! isset( $data['sections'][ $section ] ) || ! isset( $this->available_sections[ $section ] ) ) {
					continue;
				}

				$method = 'import_' . $section;
				if ( method_exists( $this, $method ) ) {
					$result              = $this->$method( $data['sections'][ $section ], $mode );
					$results[ $section ] = $result;

					if ( ! $result['success'] ) {
						throw new Exception( $result['message'] );
					}
				}
			}

			$wpdb->query( 'COMMIT' );

			return array(
				'success' => true,
				'message' => __( 'درونریزی با موفقیت انجام شد', 'tabesh' ),
				'results' => $results,
			);

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );

			return array(
				'success' => false,
				/* translators: %s: Error message */
				'message' => sprintf( __( 'خطا در درونریزی: %s', 'tabesh' ), $e->getMessage() ),
				'results' => $results,
			);
		}
	}

	/**
	 * Validate import file data
	 *
	 * @param array $data Import data
	 * @return array Validation result
	 */
	public function validate_import_data( $data ) {
		// Check if data is array
		if ( ! is_array( $data ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'فرمت فایل نامعتبر است', 'tabesh' ),
			);
		}

		// Check required fields
		if ( ! isset( $data['version'] ) || ! isset( $data['export_date'] ) || ! isset( $data['sections'] ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'فایل فاقد اطلاعات ضروری است', 'tabesh' ),
			);
		}

		// Check version compatibility.
		if ( version_compare( $data['version'], TABESH_VERSION, '>' ) ) {
			return array(
				'valid'   => false,
				/* translators: 1: File version, 2: Current plugin version */
				'message' => sprintf(
					__( 'این فایل با نسخه %1$s ساخته شده و با نسخه فعلی (%2$s) سازگار نیست', 'tabesh' ),
					$data['version'],
					TABESH_VERSION
				),
			);
		}

		return array(
			'valid'   => true,
			'message' => __( 'فایل معتبر است', 'tabesh' ),
		);
	}

	/**
	 * Get export preview
	 *
	 * @param array $sections Sections to preview
	 * @return array Preview data
	 */
	public function get_export_preview( $sections = array() ) {
		global $wpdb;

		if ( empty( $sections ) ) {
			$sections = array_keys( $this->available_sections );
		}

		$preview = array();

		foreach ( $sections as $section ) {
			if ( ! isset( $this->available_sections[ $section ] ) ) {
				continue;
			}

			$table = $wpdb->prefix . 'tabesh_' . $section;

			// Special handling for customers (wp_users table)
			if ( $section === 'customers' ) {
				$preview[ $section ] = array(
					'count' => $this->get_customers_count(),
					'label' => $this->available_sections[ $section ],
				);
				continue;
			}

			// Check if table exists.
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( $table_exists ) {
				$count               = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$preview[ $section ] = array(
					'count' => (int) $count,
					'label' => $this->available_sections[ $section ],
				);
			}
		}

		return $preview;
	}

	/**
	 * Get import preview
	 *
	 * @param array $data Import data
	 * @return array Preview data
	 */
	public function get_import_preview( $data ) {
		$validation = $this->validate_import_data( $data );
		if ( ! $validation['valid'] ) {
			return array(
				'valid'   => false,
				'message' => $validation['message'],
			);
		}

		$preview = array(
			'valid'       => true,
			'version'     => $data['version'],
			'export_date' => $data['export_date'],
			'site_url'    => isset( $data['site_url'] ) ? $data['site_url'] : '',
			'sections'    => array(),
		);

		foreach ( $data['sections'] as $section => $section_data ) {
			if ( ! isset( $this->available_sections[ $section ] ) ) {
				continue;
			}

			$preview['sections'][ $section ] = array(
				'label' => $this->available_sections[ $section ],
				'count' => is_array( $section_data ) ? count( $section_data ) : 0,
			);
		}

		return $preview;
	}

	// ==================== EXPORT METHODS ====================

	/**
	 * Export orders
	 *
	 * @return array Orders data
	 */
	private function export_orders() {
		return $this->get_table_data( 'orders' );
	}

	/**
	 * Export settings
	 *
	 * @return array Settings data
	 */
	private function export_settings() {
		return $this->get_table_data( 'settings' );
	}

	/**
	 * Export customers (users related to orders)
	 *
	 * @return array Customers data
	 */
	private function export_customers() {
		global $wpdb;

		// Get unique user IDs from orders.
		$order_table         = $this->get_table_name( 'orders' );
		$order_table_escaped = esc_sql( $order_table );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$order_table_escaped}" );

		if ( empty( $user_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

		// Get user data (excluding password)
		$users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, user_login, user_email, user_registered, display_name 
                FROM {$wpdb->users} 
                WHERE ID IN ($placeholders)",
				$user_ids
			),
			ARRAY_A
		);

		// Get user meta
		foreach ( $users as &$user ) {
			$user['meta'] = get_user_meta( $user['ID'] );
		}

		return $users ? $users : array();
	}

	/**
	 * Export logs
	 *
	 * @return array Logs data
	 */
	private function export_logs() {
		return $this->get_table_data( 'logs' );
	}

	/**
	 * Export files
	 *
	 * @return array Files data
	 */
	private function export_files() {
		return $this->get_table_data( 'files' );
	}

	/**
	 * Export file versions
	 *
	 * @return array File versions data
	 */
	private function export_file_versions() {
		return $this->get_table_data( 'file_versions' );
	}

	/**
	 * Export upload tasks
	 *
	 * @return array Upload tasks data
	 */
	private function export_upload_tasks() {
		return $this->get_table_data( 'upload_tasks' );
	}

	/**
	 * Export book format settings
	 *
	 * @return array Book format settings data
	 */
	private function export_book_format_settings() {
		return $this->get_table_data( 'book_format_settings' );
	}

	/**
	 * Export file comments
	 *
	 * @return array File comments data
	 */
	private function export_file_comments() {
		return $this->get_table_data( 'file_comments' );
	}

	/**
	 * Export document metadata
	 *
	 * @return array Document metadata
	 */
	private function export_document_metadata() {
		return $this->get_table_data( 'document_metadata' );
	}

	/**
	 * Export download tokens
	 *
	 * @return array Download tokens data
	 */
	private function export_download_tokens() {
		return $this->get_table_data( 'download_tokens' );
	}

	/**
	 * Export security logs
	 *
	 * @return array Security logs data
	 */
	private function export_security_logs() {
		return $this->get_table_data( 'security_logs' );
	}

	// ==================== IMPORT METHODS ====================

	/**
	 * Import orders
	 *
	 * @param array  $data Orders data.
	 * @param string $mode Import mode.
	 * @return array Result
	 */
	private function import_orders( $data, $mode ) {
		global $wpdb;
		$table         = $this->get_table_name( 'orders' );
		$table_escaped = esc_sql( $table );

		if ( $mode === 'replace' ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "TRUNCATE TABLE {$table_escaped}" );
		}

		$imported = 0;
		foreach ( $data as $order ) {
			// Remove id for insert.
			$order_id = isset( $order['id'] ) ? intval( $order['id'] ) : null;
			unset( $order['id'] );

			// Sanitize order data.
			$sanitized_order = array();
			foreach ( $order as $key => $value ) {
				$sanitized_order[ sanitize_key( $key ) ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}

			if ( $mode === 'merge' && $order_id ) {
				// Check if order exists.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$table_escaped} WHERE id = %d",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$order_id
					)
				);

				if ( $exists ) {
					$wpdb->update( $table, $sanitized_order, array( 'id' => $order_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				} else {
					$sanitized_order['id'] = $order_id;
					$wpdb->insert( $table, $sanitized_order ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}
			} else {
				$wpdb->insert( $table, $sanitized_order ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			++$imported;
		}

		return array(
			'success' => true,
			/* translators: %d: Number of orders */
			'message' => sprintf( __( '%d سفارش وارد شد', 'tabesh' ), $imported ),
		);
	}

	/**
	 * Import settings
	 *
	 * @param array  $data Settings data.
	 * @param string $mode Import mode.
	 * @return array Result
	 */
	private function import_settings( $data, $mode ) {
		global $wpdb;
		$table         = $this->get_table_name( 'settings' );
		$table_escaped = esc_sql( $table );

		if ( $mode === 'replace' ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "TRUNCATE TABLE {$table_escaped}" );
		}

		$imported = 0;
		foreach ( $data as $setting ) {
			$setting_key = sanitize_text_field( $setting['setting_key'] );
			unset( $setting['id'] );

			// Sanitize setting data.
			$sanitized_setting = array();
			foreach ( $setting as $key => $value ) {
				$sanitized_setting[ sanitize_key( $key ) ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}

			if ( $mode === 'merge' ) {
				// Check if setting exists.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$table_escaped} WHERE setting_key = %s",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$setting_key
					)
				);

				if ( $exists ) {
					$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$table,
						$sanitized_setting,
						array( 'setting_key' => $setting_key )
					);
				} else {
					$wpdb->insert( $table, $sanitized_setting ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}
			} else {
				$wpdb->insert( $table, $sanitized_setting ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			++$imported;
		}

		return array(
			'success' => true,
			/* translators: %d: Number of settings */
			'message' => sprintf( __( '%d تنظیم وارد شد', 'tabesh' ), $imported ),
		);
	}

	/**
	 * Import customers
	 *
	 * @param array  $data Customers data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_customers( $data, $mode ) {
		$imported = 0;

		foreach ( $data as $user_data ) {
			$user_id = $user_data['ID'];
			$meta    = isset( $user_data['meta'] ) ? $user_data['meta'] : array();
			unset( $user_data['meta'] );

			// Check if user exists
			$existing_user = get_user_by( 'id', $user_id );

			if ( $existing_user && $mode === 'merge' ) {
				// Update user data (excluding password)
				wp_update_user(
					array(
						'ID'           => $user_id,
						'user_email'   => $user_data['user_email'],
						'display_name' => $user_data['display_name'],
					)
				);

				// Update meta
				foreach ( $meta as $key => $value ) {
					if ( is_array( $value ) && count( $value ) === 1 ) {
						update_user_meta( $user_id, $key, $value[0] );
					}
				}

				++$imported;
			} elseif ( ! $existing_user ) {
				// Note: We don't create new users from import for security reasons
				// Users must already exist in the system
				continue;
			}
		}

		return array(
			'success' => true,
			'message' => sprintf( __( '%d مشتری بروزرسانی شد', 'tabesh' ), $imported ),
		);
	}

	/**
	 * Import logs
	 *
	 * @param array  $data Logs data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_logs( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_logs', $mode, 'لاگ' );
	}

	/**
	 * Import files
	 *
	 * @param array  $data Files data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_files( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_files', $mode, 'فایل' );
	}

	/**
	 * Import file versions
	 *
	 * @param array  $data File versions data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_file_versions( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_file_versions', $mode, 'نسخه فایل' );
	}

	/**
	 * Import upload tasks
	 *
	 * @param array  $data Upload tasks data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_upload_tasks( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_upload_tasks', $mode, 'وظیفه آپلود' );
	}

	/**
	 * Import book format settings
	 *
	 * @param array  $data Book format settings data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_book_format_settings( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_book_format_settings', $mode, 'تنظیم فرمت کتاب' );
	}

	/**
	 * Import file comments
	 *
	 * @param array  $data File comments data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_file_comments( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_file_comments', $mode, 'نظر فایل' );
	}

	/**
	 * Import document metadata
	 *
	 * @param array  $data Document metadata
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_document_metadata( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_document_metadata', $mode, 'متادیتای سند' );
	}

	/**
	 * Import download tokens
	 *
	 * @param array  $data Download tokens data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_download_tokens( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_download_tokens', $mode, 'توکن دانلود' );
	}

	/**
	 * Import security logs
	 *
	 * @param array  $data Security logs data
	 * @param string $mode Import mode
	 * @return array Result
	 */
	private function import_security_logs( $data, $mode ) {
		return $this->import_simple_table( $data, 'tabesh_security_logs', $mode, 'لاگ امنیتی' );
	}

	// ==================== HELPER METHODS ====================

	/**
	 * Import data into a simple table
	 *
	 * @param array  $data Table data.
	 * @param string $table_name Table name (without prefix).
	 * @param string $mode Import mode.
	 * @param string $label Label for messages.
	 * @return array Result
	 */
	private function import_simple_table( $data, $table_name, $mode, $label ) {
		global $wpdb;
		$table = $wpdb->prefix . $table_name;

		// Sanitize table name using esc_sql since it's from whitelist.
		$table_escaped = esc_sql( $table );

		if ( $mode === 'replace' ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "TRUNCATE TABLE {$table_escaped}" );
		}

		$imported = 0;
		foreach ( $data as $row ) {
			// Remove auto-increment ID.
			unset( $row['id'] );

			// Sanitize all values before inserting.
			$sanitized_row = array();
			foreach ( $row as $key => $value ) {
				$sanitized_row[ sanitize_key( $key ) ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}

			$wpdb->insert( $table, $sanitized_row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			++$imported;
		}

		return array(
			'success' => true,
			/* translators: 1: Number of records, 2: Section label */
			'message' => sprintf( __( '%1$d %2$s وارد شد', 'tabesh' ), $imported, $label ),
		);
	}

	/**
	 * Get count of customers with orders
	 *
	 * @return int
	 */
	private function get_customers_count() {
		global $wpdb;
		$order_table         = $this->get_table_name( 'orders' );
		$order_table_escaped = esc_sql( $order_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$order_table_escaped}" );
	}

	// ========================================================================
	// CLEANUP AND DELETION METHODS
	// ========================================================================

	/**
	 * Get preview of data to be cleaned up
	 *
	 * @return array Preview data with counts
	 */
	public function get_cleanup_preview() {
		global $wpdb;

		$preview = array();

		// Orders count
		$orders_table = $wpdb->prefix . 'tabesh_orders';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$preview['orders'] = array(
			'total'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$orders_table}" ),
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'archived' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$orders_table} WHERE status = %s", 'archived' ) ),
		);

		// Files count
		$files_table = $wpdb->prefix . 'tabesh_files';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$preview['files'] = array(
			'records' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$files_table}" ),
		);

		// File versions count
		$versions_table = $wpdb->prefix . 'tabesh_file_versions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$preview['file_versions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$versions_table}" );

		// Logs count
		$logs_table = $wpdb->prefix . 'tabesh_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$preview['logs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$logs_table}" );

		// Security logs count
		$security_logs_table = $wpdb->prefix . 'tabesh_security_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$preview['security_logs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$security_logs_table}" );

		// Upload tasks count
		$tasks_table = $wpdb->prefix . 'tabesh_upload_tasks';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$preview['upload_tasks'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tasks_table}" );

		// Physical files check
		$upload_dir = $this->get_upload_directory();
		$preview['physical_files'] = $this->count_physical_files( $upload_dir );

		return $preview;
	}

	/**
	 * Delete orders based on options
	 *
	 * @param array $options Deletion options.
	 * @return array Result with count and message
	 */
	public function delete_orders( $options = array() ) {
		global $wpdb;

		$defaults = array(
			'all'         => false,
			'archived'    => false,
			'user_id'     => 0,
			'older_than'  => 0, // Days.
		);

		$options      = wp_parse_args( $options, $defaults );
		$orders_table = $wpdb->prefix . 'tabesh_orders';
		$where_parts  = array();
		$where_values = array();

		// Build WHERE clause based on options
		if ( $options['archived'] && ! $options['all'] ) {
			$where_parts[]  = 'status = %s';
			$where_values[] = 'archived';
		}

		if ( $options['user_id'] > 0 ) {
			$where_parts[]  = 'user_id = %d';
			$where_values[] = $options['user_id'];
		}

		if ( $options['older_than'] > 0 ) {
			$where_parts[]  = 'created_at < DATE_SUB(NOW(), INTERVAL %d DAY)';
			$where_values[] = $options['older_than'];
		}

		// Build final query
		$where_clause = '';
		if ( ! empty( $where_parts ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_parts );
		}

		$query = "DELETE FROM {$orders_table} {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		// Execute deletion
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$deleted = $wpdb->query( $query );

		// Log the action
		$this->log_cleanup_action( 'delete_orders', $options, $deleted );

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf( '%d سفارش حذف شد', $deleted ),
		);
	}

	/**
	 * Delete files based on options
	 *
	 * @param array $options Deletion options.
	 * @return array Result with count and message
	 */
	public function delete_files( $options = array() ) {
		global $wpdb;

		$defaults = array(
			'database'  => false,
			'physical'  => false,
			'orphans'   => false,
		);

		$options = wp_parse_args( $options, $defaults );
		$deleted = array(
			'database_records' => 0,
			'physical_files'   => 0,
		);

		$files_table = $wpdb->prefix . 'tabesh_files';

		if ( $options['orphans'] ) {
			$result  = $this->delete_orphan_files();
			$deleted = $result['deleted'];
		} else {
			// Get file paths before deletion if we need to delete physical files
			if ( $options['physical'] ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$file_paths = $wpdb->get_col( "SELECT file_path FROM {$files_table}" );
				$deleted['physical_files'] = $this->delete_physical_files( $file_paths );
			}

			// Delete database records
			if ( $options['database'] ) {
				// Also delete related data
				$versions_table = $wpdb->prefix . 'tabesh_file_versions';
				$comments_table = $wpdb->prefix . 'tabesh_file_comments';

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( "DELETE FROM {$versions_table}" );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( "DELETE FROM {$comments_table}" );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted['database_records'] = $wpdb->query( "DELETE FROM {$files_table}" );
			}
		}

		// Log the action
		$this->log_cleanup_action( 'delete_files', $options, $deleted );

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf(
				'%d رکورد از دیتابیس و %d فایل فیزیکی حذف شد',
				$deleted['database_records'],
				$deleted['physical_files']
			),
		);
	}

	/**
	 * Delete orphan files (files without database records or vice versa)
	 *
	 * @return array Result with counts
	 */
	public function delete_orphan_files() {
		global $wpdb;

		$deleted = array(
			'database_records' => 0,
			'physical_files'   => 0,
		);

		$files_table = $wpdb->prefix . 'tabesh_files';
		$upload_dir  = $this->get_upload_directory();

		// Find database records without physical files
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$db_files = $wpdb->get_results( "SELECT id, file_path FROM {$files_table}", ARRAY_A );

		$orphan_records = array();
		foreach ( $db_files as $file ) {
			$full_path = $upload_dir . $file['file_path'];
			if ( ! file_exists( $full_path ) ) {
				$orphan_records[] = $file['id'];
			}
		}

		// Delete orphan database records
		if ( ! empty( $orphan_records ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $orphan_records ), '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted['database_records'] = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$files_table} WHERE id IN ($placeholders)",
					$orphan_records
				)
			);
		}

		// Find physical files without database records
		if ( is_dir( $upload_dir ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$db_paths = $wpdb->get_col( "SELECT file_path FROM {$files_table}" );
			$db_paths = array_flip( $db_paths );

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $upload_dir, RecursiveDirectoryIterator::SKIP_DOTS )
			);

			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$relative_path = str_replace( $upload_dir, '', $file->getPathname() );
					if ( ! isset( $db_paths[ $relative_path ] ) ) {
						// This is an orphan physical file
						if ( wp_delete_file( $file->getPathname() ) ) {
							++$deleted['physical_files'];
						}
					}
				}
			}
		}

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf(
				'%d رکورد یتیم و %d فایل یتیم حذف شد',
				$deleted['database_records'],
				$deleted['physical_files']
			),
		);
	}

	/**
	 * Delete logs based on options
	 *
	 * @param array $options Deletion options.
	 * @return array Result with count and message
	 */
	public function delete_logs( $options = array() ) {
		global $wpdb;

		$defaults = array(
			'all'         => false,
			'older_than'  => 0, // Days.
			'type'        => '', // 'regular' or 'security' or 'all'.
		);

		$options = wp_parse_args( $options, $defaults );
		$deleted = 0;

		if ( empty( $options['type'] ) || $options['type'] === 'regular' || $options['type'] === 'all' ) {
			$logs_table = $wpdb->prefix . 'tabesh_logs';

			if ( $options['older_than'] > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted += $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$logs_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
						$options['older_than']
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted += $wpdb->query( "DELETE FROM {$logs_table}" );
			}
		}

		if ( empty( $options['type'] ) || $options['type'] === 'security' || $options['type'] === 'all' ) {
			$security_logs_table = $wpdb->prefix . 'tabesh_security_logs';

			if ( $options['older_than'] > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted += $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$security_logs_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
						$options['older_than']
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$deleted += $wpdb->query( "DELETE FROM {$security_logs_table}" );
			}
		}

		// Log the action (not in security logs as we might be deleting them)
		$this->log_cleanup_action( 'delete_logs', $options, $deleted );

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf( '%d رکورد لاگ حذف شد', $deleted ),
		);
	}

	/**
	 * Reset settings to default values
	 *
	 * @return array Result with message
	 */
	public function reset_settings() {
		global $wpdb;

		$settings_table = $wpdb->prefix . 'tabesh_settings';

		// Delete all current settings
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query( "DELETE FROM {$settings_table}" );

		// Settings will be recreated with defaults on next access
		// Clear any cached settings
		wp_cache_delete( 'tabesh_settings', 'tabesh' );

		// Log the action
		$this->log_cleanup_action( 'reset_settings', array(), $deleted );

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => 'تنظیمات به حالت پیش‌فرض بازگردانی شد',
		);
	}

	/**
	 * Factory reset - delete everything
	 *
	 * @param string $confirm_key Confirmation key (must be 'RESET' to proceed).
	 * @return array Result with message
	 */
	public function factory_reset( $confirm_key ) {
		if ( $confirm_key !== 'RESET' ) {
			return array(
				'success' => false,
				'message' => 'کلید تأیید نادرست است. برای ریست کامل باید کلمه RESET را وارد کنید.',
			);
		}

		global $wpdb;

		$deleted = array();

		// Delete all orders
		$deleted['orders'] = $this->delete_orders( array( 'all' => true ) );

		// Delete all files
		$deleted['files'] = $this->delete_files(
			array(
				'database' => true,
				'physical' => true,
			)
		);

		// Delete all logs
		$deleted['logs'] = $this->delete_logs( array( 'type' => 'all' ) );

		// Delete upload tasks
		$tasks_table = $wpdb->prefix . 'tabesh_upload_tasks';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted['upload_tasks'] = $wpdb->query( "DELETE FROM {$tasks_table}" );

		// Delete download tokens
		$tokens_table = $wpdb->prefix . 'tabesh_download_tokens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted['download_tokens'] = $wpdb->query( "DELETE FROM {$tokens_table}" );

		// Delete document metadata
		$metadata_table = $wpdb->prefix . 'tabesh_document_metadata';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted['document_metadata'] = $wpdb->query( "DELETE FROM {$metadata_table}" );

		// Reset settings last
		$deleted['settings'] = $this->reset_settings();

		// Log the factory reset
		$this->log_cleanup_action( 'factory_reset', array( 'confirm_key' => $confirm_key ), $deleted );

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => 'ریست کامل انجام شد. تمام داده‌های افزونه حذف شدند.',
		);
	}

	/**
	 * Delete user data (GDPR compliance)
	 *
	 * @param int $user_id User ID.
	 * @return array Result with counts
	 */
	public function delete_user_data( $user_id ) {
		global $wpdb;

		$deleted = array();

		// Delete user orders
		$deleted['orders'] = $this->delete_orders( array( 'user_id' => $user_id ) );

		// Delete user files
		$files_table = $wpdb->prefix . 'tabesh_files';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$file_paths = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT file_path FROM {$files_table} WHERE user_id = %d",
				$user_id
			)
		);

		// Delete physical files
		$deleted['physical_files'] = $this->delete_physical_files( $file_paths );

		// Delete database records
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted['file_records'] = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$files_table} WHERE user_id = %d",
				$user_id
			)
		);

		// Delete user logs
		$logs_table = $wpdb->prefix . 'tabesh_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted['logs'] = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$logs_table} WHERE user_id = %d",
				$user_id
			)
		);

		// Log the action
		$this->log_cleanup_action( 'delete_user_data', array( 'user_id' => $user_id ), $deleted );

		return array(
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf( 'تمام داده‌های کاربر %d حذف شد', $user_id ),
		);
	}

	// ========================================================================
	// HELPER METHODS
	// ========================================================================

	/**
	 * Get upload directory path
	 *
	 * @return string Upload directory path
	 */
	private function get_upload_directory() {
		$upload_dir = wp_upload_dir();
		// Try tabesh-files first (used by file-security), fallback to plugin-files
		$tabesh_dir = $upload_dir['basedir'] . '/tabesh-files/';
		if ( is_dir( $tabesh_dir ) ) {
			return $tabesh_dir;
		}
		return $upload_dir['basedir'] . '/plugin-files/';
	}

	/**
	 * Count physical files in directory
	 *
	 * @param string $directory Directory path.
	 * @return int File count
	 */
	private function count_physical_files( $directory ) {
		$count = 0;
		if ( ! is_dir( $directory ) ) {
			return $count;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Delete physical files
	 *
	 * @param array $file_paths Array of relative file paths.
	 * @return int Number of files deleted
	 */
	private function delete_physical_files( $file_paths ) {
		$deleted    = 0;
		$upload_dir = $this->get_upload_directory();

		foreach ( $file_paths as $file_path ) {
			$full_path = $upload_dir . $file_path;
			if ( file_exists( $full_path ) && wp_delete_file( $full_path ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Log cleanup action to security logs
	 *
	 * @param string $action Action name.
	 * @param array  $options Options used.
	 * @param mixed  $result Result of the action.
	 * @return void
	 */
	private function log_cleanup_action( $action, $options, $result ) {
		global $wpdb;

		$security_logs_table = $wpdb->prefix . 'tabesh_security_logs';
		$user                = wp_get_current_user();

		$log_data = array(
			'user_id'     => $user->ID,
			'action'      => 'cleanup_' . $action,
			'description' => wp_json_encode(
				array(
					'options' => $options,
					'result'  => $result,
				)
			),
			'ip_address'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'created_at'  => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $security_logs_table, $log_data );
	}
}
