<?php
/**
 * React Dashboard Handler
 * Handles enqueuing and rendering of the React admin dashboard
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_React_Dashboard
 */
class Tabesh_React_Dashboard {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_react_dashboard' ) );
	}

	/**
	 * Check if we should load the React dashboard
	 *
	 * @return bool
	 */
	private function should_load_react_dashboard() {
		global $post;

		// Check if we're on a page/post with the admin dashboard shortcode.
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'tabesh_admin_dashboard' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue React dashboard assets
	 */
	public function enqueue_react_dashboard() {
		if ( ! $this->should_load_react_dashboard() ) {
			return;
		}

		// Check if user has permission.
		$admin = Tabesh()->admin;
		if ( ! $admin || ! $admin->user_has_admin_dashboard_access( get_current_user_id() ) ) {
			return;
		}

		// Check if React dashboard is enabled in settings.
		$use_react_dashboard = Tabesh()->get_setting( 'use_react_dashboard', '0' );

		// If React dashboard is not enabled, don't load React assets.
		if ( '1' !== $use_react_dashboard ) {
			return;
		}

		// Enqueue built React app.
		$dist_path = TABESH_PLUGIN_DIR . 'assets/dist/admin-dashboard/';
		$dist_url  = TABESH_PLUGIN_URL . 'assets/dist/admin-dashboard/';

		// Check if built files exist.
		if ( ! file_exists( $dist_path . 'admin-dashboard.js' ) ) {
			// Development fallback or error message.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Tabesh: React dashboard build files not found. Run: cd assets/react && npm run build' );
			}
			return;
		}

		// Enqueue CSS.
		if ( file_exists( $dist_path . 'admin-dashboard.css' ) ) {
			wp_enqueue_style(
				'tabesh-react-dashboard',
				$dist_url . 'admin-dashboard.css',
				array(),
				TABESH_VERSION
			);
		}

		// Enqueue Dashicons (WordPress icons).
		wp_enqueue_style( 'dashicons' );

		// Enqueue JS.
		wp_enqueue_script(
			'tabesh-react-dashboard',
			$dist_url . 'admin-dashboard.js',
			array(),
			TABESH_VERSION,
			true
		);

		// Pass configuration to React app.
		$this->localize_react_config();
	}

	/**
	 * Pass configuration data to React app via window object
	 */
	private function localize_react_config() {
		$current_user = wp_get_current_user();
		$admin        = Tabesh()->admin;

		$config = array(
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'restUrl'         => rest_url( TABESH_REST_NAMESPACE ),
			'restNamespace'   => TABESH_REST_NAMESPACE,
			'currentUserId'   => $current_user->ID,
			'currentUserRole' => ! empty( $current_user->roles ) ? $current_user->roles[0] : '',
			'isAdmin'         => $admin ? $admin->user_has_admin_dashboard_access( $current_user->ID ) : false,
			'canEditOrders'   => current_user_can( 'edit_shop_orders' ),
			'avatarUrl'       => get_avatar_url( $current_user->ID ),
			'userName'        => $current_user->display_name,
			'userEmail'       => $current_user->user_email,
		);

		// Output inline script before React app loads.
		wp_add_inline_script(
			'tabesh-react-dashboard',
			'window.tabeshConfig = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}

	/**
	 * Render React dashboard root element
	 *
	 * @return string
	 */
	public function render_dashboard() {
		// Check permission.
		$admin = Tabesh()->admin;
		if ( ! $admin || ! $admin->user_has_admin_dashboard_access( get_current_user_id() ) ) {
			return '<div class="tabesh-access-denied">' . esc_html__( 'شما دسترسی لازم برای مشاهده این صفحه را ندارید.', 'tabesh' ) . '</div>';
		}

		// Check if React dashboard is enabled in settings.
		$use_react_dashboard = Tabesh()->get_setting( 'use_react_dashboard', '0' );

		// If React dashboard is not enabled, use PHP template.
		if ( '1' !== $use_react_dashboard ) {
			return $this->render_php_dashboard();
		}

		// Check if React build exists.
		$dist_path = TABESH_PLUGIN_DIR . 'assets/dist/admin-dashboard/admin-dashboard.js';
		if ( ! file_exists( $dist_path ) ) {
			// Log error if debug mode is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Tabesh: React dashboard build files not found. Falling back to PHP template.' );
			}
			// Fallback to PHP template if React build doesn't exist.
			return $this->render_php_dashboard();
		}

		// Return root div for React to mount.
		return '<div id="tabesh-admin-dashboard-root"></div>';
	}

	/**
	 * Render PHP dashboard template (fallback)
	 *
	 * @return string
	 */
	private function render_php_dashboard() {
		// Validate template file exists.
		$template_path = TABESH_PLUGIN_DIR . 'templates/admin/shortcode-admin-dashboard.php';

		if ( ! file_exists( $template_path ) ) {
			// Log error if debug mode is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Tabesh: PHP dashboard template file not found at ' . $template_path );
			}
			return '<div class="tabesh-error">' .
				'<p>' . esc_html__( 'خطا: فایل قالب داشبورد یافت نشد.', 'tabesh' ) . '</p>' .
				'<p>' . esc_html__( 'Error: Dashboard template file not found.', 'tabesh' ) . '</p>' .
				'</div>';
		}

		// Load the PHP template using output buffering.
		// Note: The template file includes its own permission checks and ABSPATH validation.
		ob_start();
		include $template_path;
		return ob_get_clean();
	}
}
