<?php
/**
 * Admin Order Form V2 - Matrix-Based Pricing
 *
 * کلاس مدیریت فرم ثبت سفارش ویژه مدیر نسخه ۲
 * Class for managing admin order form with V2 pricing engine integration
 *
 * This version uses:
 * - V2 matrix-based pricing engine.
 * - Constraint manager for cascading filters.
 * - Modern wizard UI similar to order-form-v2.
 * - Customer search and creation.
 * - Optional SMS sending.
 *
 * @package Tabesh
 * @since 1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabesh Admin Order Form V2 Class
 *
 * کلاس فرم سفارش ویژه مدیر نسخه ۲ تابش
 * Handles the admin order form V2 shortcode with V2 pricing engine
 *
 * Features:
 * - Access control via user roles.
 * - V2 matrix-based pricing engine.
 * - Constraint manager integration.
 * - Modern wizard-style UI.
 * - Customer search and creation.
 * - Optional SMS notification.
 *
 * @since 1.1.0
 */
class Tabesh_Admin_Order_V2 {

	/**
	 * Settings key for allowed roles
	 * کلید تنظیمات برای نقش‌های مجاز
	 *
	 * @var string
	 */
	const SETTINGS_KEY_ALLOWED_ROLES = 'admin_order_form_allowed_roles';

	/**
	 * Settings key for allowed users
	 * کلید تنظیمات برای کاربران مجاز
	 *
	 * @var string
	 */
	const SETTINGS_KEY_ALLOWED_USERS = 'admin_order_form_allowed_users';

	/**
	 * Default allowed roles
	 * نقش‌های مجاز پیش‌فرض
	 *
	 * @var array
	 */
	private static $default_allowed_roles = array( 'administrator' );

	/**
	 * Constructor
	 * سازنده
	 */
	public function __construct() {
		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets
	 * بارگذاری فایل‌های CSS و JavaScript برای فرانت‌اند
	 */
	public function enqueue_assets() {
		// Only enqueue on pages with admin order form V2 shortcode or admin dashboard shortcode.
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// Check for direct shortcode usage or admin dashboard shortcode.
		if ( ! has_shortcode( $post->post_content, 'tabesh_admin_order_form_v2' ) &&
			! has_shortcode( $post->post_content, 'tabesh_admin_dashboard' ) ) {
			return;
		}

		// Check if user has access before loading assets.
		if ( ! $this->user_has_access() ) {
			return;
		}

		// Get file version for cache busting.
		$get_file_version = function ( $file_path ) {
			if ( WP_DEBUG && file_exists( $file_path ) ) {
				$mtime = @filemtime( $file_path );
				return $mtime !== false ? $mtime : TABESH_VERSION;
			}
			return TABESH_VERSION;
		};

		// Enqueue CSS.
		wp_enqueue_style(
			'tabesh-admin-order-v2',
			TABESH_PLUGIN_URL . 'assets/css/admin-order-form-v2.css',
			array(),
			$get_file_version( TABESH_PLUGIN_DIR . 'assets/css/admin-order-form-v2.css' )
		);

		// Enqueue JS.
		wp_enqueue_script(
			'tabesh-admin-order-v2',
			TABESH_PLUGIN_URL . 'assets/js/admin-order-form-v2.js',
			array( 'jquery' ),
			$get_file_version( TABESH_PLUGIN_DIR . 'assets/js/admin-order-form-v2.js' ),
			true
		);

		// Get constraint manager for available book sizes.
		$constraint_manager = new Tabesh_Constraint_Manager();
		$available_sizes    = $constraint_manager->get_available_book_sizes();

		// Get scalar settings.
		$min_quantity  = Tabesh()->get_setting( 'min_quantity', 10 );
		$max_quantity  = Tabesh()->get_setting( 'max_quantity', 10000 );
		$quantity_step = Tabesh()->get_setting( 'quantity_step', 10 );

		// Localize script with necessary data.
		wp_localize_script(
			'tabesh-admin-order-v2',
			'tabeshAdminOrderV2',
			array(
				'restUrl'        => rest_url( TABESH_REST_NAMESPACE ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'availableSizes' => $available_sizes,
				'settings'       => array(
					'minQuantity'  => intval( $min_quantity ),
					'maxQuantity'  => intval( $max_quantity ),
					'quantityStep' => intval( $quantity_step ),
				),
				'strings'        => array(
					'selectUser'         => __( 'انتخاب کاربر', 'tabesh' ),
					'createNewUser'      => __( 'ایجاد کاربر جدید', 'tabesh' ),
					'searchUsers'        => __( 'جستجوی کاربران...', 'tabesh' ),
					'noResults'          => __( 'کاربری یافت نشد', 'tabesh' ),
					'calculating'        => __( 'در حال محاسبه قیمت...', 'tabesh' ),
					'submitting'         => __( 'در حال ثبت سفارش...', 'tabesh' ),
					'success'            => __( 'سفارش با موفقیت ثبت شد', 'tabesh' ),
					'error'              => __( 'خطا در ثبت سفارش', 'tabesh' ),
					'fillAllFields'      => __( 'لطفاً تمام فیلدهای الزامی را پر کنید', 'tabesh' ),
					'selectCustomer'     => __( 'لطفاً یک مشتری را انتخاب یا ایجاد کنید', 'tabesh' ),
					'invalidMobile'      => __( 'فرمت شماره موبایل نامعتبر است', 'tabesh' ),
					'userCreated'        => __( 'کاربر با موفقیت ایجاد شد', 'tabesh' ),
					'searching'          => __( 'در حال جستجو...', 'tabesh' ),
					'selectOption'       => __( 'انتخاب کنید...', 'tabesh' ),
					'bookTitle'          => __( 'عنوان کتاب را وارد کنید', 'tabesh' ),
					'selectBookSize'     => __( 'قطع کتاب را انتخاب کنید', 'tabesh' ),
					'selectPaperType'    => __( 'نوع کاغذ را انتخاب کنید', 'tabesh' ),
					'selectPaperWeight'  => __( 'گرماژ کاغذ را انتخاب کنید', 'tabesh' ),
					'selectPrintType'    => __( 'نوع چاپ را انتخاب کنید', 'tabesh' ),
					'enterPageCount'     => __( 'تعداد صفحات معتبر وارد کنید', 'tabesh' ),
					'enterQuantity'      => __( 'تیراژ معتبر وارد کنید', 'tabesh' ),
					'selectBindingType'  => __( 'نوع صحافی را انتخاب کنید', 'tabesh' ),
					'selectCoverWeight'  => __( 'گرماژ جلد را انتخاب کنید', 'tabesh' ),
					'orderCreated'       => __( 'سفارش با موفقیت ایجاد شد', 'tabesh' ),
					'viewOrder'          => __( 'مشاهده سفارش', 'tabesh' ),
					'createAnother'      => __( 'ثبت سفارش جدید', 'tabesh' ),
					'loadingOptions'     => __( 'در حال بارگذاری گزینه‌ها...', 'tabesh' ),
					'noOptionsAvailable' => __( 'هیچ گزینه‌ای موجود نیست', 'tabesh' ),
				),
			)
		);
	}

	/**
	 * Render the admin order form V2 shortcode
	 * رندر شورتکد فرم سفارش ویژه مدیر نسخه ۲
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts = array() ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'title' => __( 'ثبت سفارش جدید (ویژه مدیر)', 'tabesh' ),
			),
			$atts,
			'tabesh_admin_order_form_v2'
		);

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return $this->render_login_required_message();
		}

		// Check access permission.
		if ( ! $this->user_has_access() ) {
			return $this->render_access_denied_message();
		}

		// Check if V2 pricing engine is enabled.
		$pricing_engine = new Tabesh_Pricing_Engine();
		if ( ! $pricing_engine->is_enabled() ) {
			return $this->render_v2_not_enabled_message();
		}

		// Output buffer for template.
		ob_start();
		include TABESH_PLUGIN_DIR . 'templates/frontend/admin-order-form-v2.php';
		return ob_get_clean();
	}

	/**
	 * Check if current user has access to admin order form
	 * بررسی دسترسی کاربر فعلی به فرم سفارش مدیر
	 *
	 * Access is granted if:
	 * 1. User has manage_woocommerce capability (admins always have access).
	 * 2. User's role is in allowed roles list.
	 * 3. User's ID is in allowed users list.
	 *
	 * @return bool True if user has access.
	 */
	public function user_has_access() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Admins always have access.
		if ( user_can( $user, 'manage_woocommerce' ) ) {
			return true;
		}

		// Check if user role is in allowed roles.
		$allowed_roles = $this->get_allowed_roles();
		if ( ! empty( $allowed_roles ) ) {
			foreach ( $user->roles as $role ) {
				if ( in_array( $role, $allowed_roles, true ) ) {
					return true;
				}
			}
		}

		// Check if user ID is in allowed users list.
		$allowed_users = $this->get_allowed_users();
		if ( ! empty( $allowed_users ) && in_array( $user_id, $allowed_users, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get allowed roles from settings
	 * دریافت نقش‌های مجاز از تنظیمات
	 *
	 * @return array Allowed roles.
	 */
	public function get_allowed_roles() {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_settings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM {$wpdb->prefix}tabesh_settings WHERE setting_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::SETTINGS_KEY_ALLOWED_ROLES
			)
		);

		if ( $result ) {
			$roles = json_decode( $result, true );
			if ( is_array( $roles ) && ! empty( $roles ) ) {
				return $roles;
			}
		}

		return self::$default_allowed_roles;
	}

	/**
	 * Get allowed users from settings
	 * دریافت کاربران مجاز از تنظیمات
	 *
	 * @return array Allowed user IDs.
	 */
	public function get_allowed_users() {
		global $wpdb;
		$table = $wpdb->prefix . 'tabesh_settings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM {$wpdb->prefix}tabesh_settings WHERE setting_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::SETTINGS_KEY_ALLOWED_USERS
			)
		);

		if ( $result ) {
			$users = json_decode( $result, true );
			if ( is_array( $users ) && ! empty( $users ) ) {
				return array_map( 'intval', $users );
			}
		}

		return array();
	}

	/**
	 * Render login required message
	 * رندر پیام نیاز به ورود
	 *
	 * @return string HTML message.
	 */
	private function render_login_required_message() {
		ob_start();
		?>
		<div class="tabesh-message error" dir="rtl">
			<p>
				<strong><?php echo esc_html__( 'خطا:', 'tabesh' ); ?></strong>
				<?php echo esc_html__( 'برای دسترسی به این فرم باید وارد حساب کاربری خود شوید.', 'tabesh' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="button">
					<?php echo esc_html__( 'ورود به حساب کاربری', 'tabesh' ); ?>
				</a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render access denied message
	 * رندر پیام عدم دسترسی
	 *
	 * @return string HTML message.
	 */
	private function render_access_denied_message() {
		ob_start();
		?>
		<div class="tabesh-message error" dir="rtl">
			<p>
				<strong><?php echo esc_html__( 'خطا:', 'tabesh' ); ?></strong>
				<?php echo esc_html__( 'شما دسترسی لازم برای استفاده از این فرم را ندارید.', 'tabesh' ); ?>
			</p>
			<p>
				<?php echo esc_html__( 'این فرم فقط برای مدیران و کاربران مجاز در دسترس است.', 'tabesh' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render V2 not enabled message
	 * رندر پیام غیرفعال بودن موتور قیمت‌گذاری V2
	 *
	 * @return string HTML message.
	 */
	private function render_v2_not_enabled_message() {
		ob_start();
		?>
		<div class="tabesh-message error" dir="rtl">
			<p>
				<strong><?php echo esc_html__( 'خطا:', 'tabesh' ); ?></strong>
				<?php echo esc_html__( 'موتور قیمت‌گذاری نسخه ۲ فعال نیست.', 'tabesh' ); ?>
			</p>
			<?php if ( current_user_can( 'manage_woocommerce' ) ) : ?>
			<p>
				<?php echo esc_html__( 'لطفاً ابتدا از پنل تنظیمات، موتور قیمت‌گذاری نسخه ۲ را فعال کنید:', 'tabesh' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=tabesh-product-pricing' ) ); ?>" class="button button-primary">
					<?php echo esc_html__( 'رفتن به تنظیمات قیمت‌گذاری', 'tabesh' ); ?>
				</a>
			</p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
