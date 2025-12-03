<?php
/**
 * Shortcode Admin Dashboard Template - Super Panel Redesign
 *
 * Modern, dynamic, and responsive admin dashboard
 * TradingView/MetaTrader inspired design with Neumorphism UI
 *
 * Shows different content based on user role:
 * - Admin users (manage_woocommerce): Full super dashboard with all features
 * - Regular users (customers, subscribers): Their own orders
 *
 * @package Tabesh
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// SECURITY: Check access using secure method that bypasses cache
$admin           = Tabesh()->admin;
$current_user_id = get_current_user_id();
$is_admin        = $admin->user_has_admin_dashboard_access( $current_user_id );

// Log unauthorized access attempts
if ( ! $is_admin ) {
	// Log the security event
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(
			sprintf(
				'Tabesh Security: Unauthorized access attempt to admin dashboard by user ID %d',
				$current_user_id
			)
		);
	}

	// Log to database for security audit
	global $wpdb;
	$logs_table = $wpdb->prefix . 'tabesh_logs';
	$result     = $wpdb->insert(
		$logs_table,
		array(
			'user_id'     => $current_user_id,
			'action'      => 'unauthorized_access_attempt',
			'description' => 'Attempted to access admin dashboard without permission',
		),
		array( '%d', '%s', '%s' )
	);

	// Log database errors
	if ( $result === false && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Tabesh Security: Failed to log unauthorized access attempt - ' . $wpdb->last_error );
	}
}

if ( $is_admin ) {
	// Admin view: Show full super dashboard
	$stats        = $admin->get_statistics();
	$all_orders   = $admin->get_orders( '', false );
	$current_user = wp_get_current_user();
	$avatar_url   = get_avatar_url( $current_user->ID );

	// Status labels
	$status_labels = array(
		'pending'    => 'در انتظار',
		'confirmed'  => 'تایید شده',
		'processing' => 'در حال چاپ',
		'ready'      => 'آماده تحویل',
		'completed'  => 'تحویل شده',
		'cancelled'  => 'لغو شده',
	);

	// Calculate progress based on status
	$status_progress = array(
		'pending'    => 10,
		'confirmed'  => 25,
		'processing' => 50,
		'ready'      => 80,
		'completed'  => 100,
		'cancelled'  => 0,
	);

	// Enqueue admin dashboard assets
	wp_enqueue_style(
		'tabesh-admin-dashboard',
		TABESH_PLUGIN_URL . 'assets/css/admin-dashboard.css',
		array(),
		TABESH_VERSION
	);

	wp_enqueue_script(
		'tabesh-admin-dashboard',
		TABESH_PLUGIN_URL . 'assets/js/admin-dashboard.js',
		array( 'jquery' ),
		TABESH_VERSION,
		true
	);

	wp_localize_script(
		'tabesh-admin-dashboard',
		'tabeshAdminData',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'restUrl' => rest_url( TABESH_REST_NAMESPACE ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'debug'   => WP_DEBUG,
			'strings' => array(
				'loading'             => __( 'در حال بارگذاری...', 'tabesh' ),
				'error'               => __( 'خطا در پردازش درخواست', 'tabesh' ),
				'success'             => __( 'عملیات با موفقیت انجام شد', 'tabesh' ),
				'confirmStatusChange' => __( 'آیا از تغییر وضعیت این سفارش اطمینان دارید؟', 'tabesh' ),
			),
		)
	);

	// Enqueue admin order creator assets
	wp_enqueue_style(
		'tabesh-admin-order-creator',
		TABESH_PLUGIN_URL . 'assets/css/admin-order-creator.css',
		array(),
		TABESH_VERSION
	);

	wp_enqueue_script(
		'tabesh-admin-order-creator',
		TABESH_PLUGIN_URL . 'assets/js/admin-order-creator.js',
		array( 'jquery' ),
		TABESH_VERSION,
		true
	);

	$paper_types = Tabesh()->get_setting( 'paper_types', array() );

	wp_localize_script(
		'tabesh-admin-order-creator',
		'tabeshAdminOrderCreator',
		array(
			'restUrl'  => rest_url( TABESH_REST_NAMESPACE ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'settings' => array(
				'paperTypes' => $paper_types,
			),
			'strings'  => array(
				'selectUser'    => __( 'انتخاب کاربر', 'tabesh' ),
				'createNewUser' => __( 'ایجاد کاربر جدید', 'tabesh' ),
				'searchUsers'   => __( 'جستجوی کاربران...', 'tabesh' ),
				'noResults'     => __( 'کاربری یافت نشد', 'tabesh' ),
				'calculating'   => __( 'در حال محاسبه قیمت...', 'tabesh' ),
				'submitting'    => __( 'در حال ثبت سفارش...', 'tabesh' ),
				'success'       => __( 'سفارش با موفقیت ثبت شد', 'tabesh' ),
				'error'         => __( 'خطا در ثبت سفارش', 'tabesh' ),
			),
		)
	);
	?>

	<div class="tabesh-admin-dashboard" dir="rtl" data-theme="light">
		<!-- Header Section -->
		<header class="admin-dashboard-header">
			<div class="header-profile-section">
				<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $current_user->display_name ); ?>" class="header-avatar">
				<div class="header-info">
					<h1><?php esc_html_e( 'سوپر پنل مدیریت سفارشات', 'tabesh' ); ?></h1>
					<p><?php echo esc_html( $current_user->display_name ); ?> - <?php esc_html_e( 'مدیر سیستم', 'tabesh' ); ?></p>
				</div>
			</div>
			<div class="header-actions">
				<button class="header-btn theme-toggle-btn" aria-label="<?php esc_attr_e( 'تغییر تم', 'tabesh' ); ?>">
					🌙 <span><?php esc_html_e( 'حالت تاریک', 'tabesh' ); ?></span>
				</button>
				<?php Tabesh()->admin_order_creator->render_new_order_button(); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=tabesh-settings' ) ); ?>" class="header-btn">
					⚙️ <span><?php esc_html_e( 'تنظیمات', 'tabesh' ); ?></span>
				</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="header-btn">
					🚪 <span><?php esc_html_e( 'خروج', 'tabesh' ); ?></span>
				</a>
			</div>
		</header>

		<!-- Statistics Cards -->
		<section class="stats-container">
			<div class="stat-card" data-filter="total">
				<div class="stat-icon total">📊</div>
				<div class="stat-content">
					<div class="stat-label"><?php esc_html_e( 'کل سفارشات فعال', 'tabesh' ); ?></div>
					<div class="stat-value"><?php echo number_format( $stats['total_orders'] ); ?></div>
				</div>
			</div>
			<div class="stat-card" data-filter="pending">
				<div class="stat-icon pending">⏳</div>
				<div class="stat-content">
					<div class="stat-label"><?php esc_html_e( 'در انتظار بررسی', 'tabesh' ); ?></div>
					<div class="stat-value"><?php echo number_format( $stats['pending_orders'] ); ?></div>
				</div>
			</div>
			<div class="stat-card" data-filter="processing">
				<div class="stat-icon processing">🖨️</div>
				<div class="stat-content">
					<div class="stat-label"><?php esc_html_e( 'در حال پردازش', 'tabesh' ); ?></div>
					<div class="stat-value"><?php echo number_format( $stats['processing_orders'] ); ?></div>
				</div>
			</div>
			<div class="stat-card" data-filter="completed">
				<div class="stat-icon completed">✅</div>
				<div class="stat-content">
					<div class="stat-label"><?php esc_html_e( 'تکمیل شده', 'tabesh' ); ?></div>
					<div class="stat-value"><?php echo number_format( $stats['completed_orders'] ); ?></div>
				</div>
			</div>
			<div class="stat-card">
				<div class="stat-icon revenue">💰</div>
				<div class="stat-content">
					<div class="stat-label"><?php esc_html_e( 'درآمد کل', 'tabesh' ); ?></div>
					<div class="stat-value"><?php echo number_format( $stats['total_revenue'] ); ?> <small><?php esc_html_e( 'تومان', 'tabesh' ); ?></small></div>
				</div>
			</div>
		</section>

		<!-- Global Search Bar -->
		<section class="search-section">
			<div class="search-bar-wrapper">
				<input type="text" 
						class="global-search-input" 
						placeholder="<?php esc_attr_e( 'جستجو: عنوان کتاب، شماره سفارش، نام مشتری، موبایل، استان، User ID...', 'tabesh' ); ?>"
						aria-label="<?php esc_attr_e( 'جستجوی سراسری', 'tabesh' ); ?>">
				<span class="search-icon">🔍</span>
				<button class="search-btn"><?php esc_html_e( 'جستجو', 'tabesh' ); ?></button>
			</div>
			<div class="search-results-info">
				<span class="results-count"></span>
			</div>
		</section>

		<!-- Filters Section -->
		<section class="filters-section">
			<div class="filters-wrapper">
				<div class="filter-group">
					<label class="filter-label"><?php esc_html_e( 'وضعیت', 'tabesh' ); ?></label>
					<select id="filter-status" class="filter-select">
						<option value=""><?php esc_html_e( 'همه وضعیت‌ها', 'tabesh' ); ?></option>
						<?php foreach ( $status_labels as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="filter-group">
					<label class="filter-label"><?php esc_html_e( 'مرتب‌سازی', 'tabesh' ); ?></label>
					<select id="filter-sort" class="filter-select">
						<option value="newest"><?php esc_html_e( 'جدیدترین', 'tabesh' ); ?></option>
						<option value="oldest"><?php esc_html_e( 'قدیمی‌ترین', 'tabesh' ); ?></option>
						<option value="quantity_high"><?php esc_html_e( 'بیشترین تیراژ', 'tabesh' ); ?></option>
						<option value="quantity_low"><?php esc_html_e( 'کمترین تیراژ', 'tabesh' ); ?></option>
						<option value="price_high"><?php esc_html_e( 'بالاترین قیمت', 'tabesh' ); ?></option>
						<option value="price_low"><?php esc_html_e( 'پایین‌ترین قیمت', 'tabesh' ); ?></option>
					</select>
				</div>
				<button class="filter-reset-btn"><?php esc_html_e( 'پاک کردن فیلترها', 'tabesh' ); ?></button>
			</div>
		</section>

		<!-- Orders Table -->
		<section class="orders-section">
			<?php if ( empty( $all_orders ) ) : ?>
				<div class="no-orders-state">
					<div class="no-orders-icon">📦</div>
					<p class="no-orders-text"><?php esc_html_e( 'هیچ سفارشی ثبت نشده است.', 'tabesh' ); ?></p>
				</div>
			<?php else : ?>
				<div class="orders-table-wrapper">
					<table class="orders-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ردیف', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'یوزر', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'مشتری', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'استان', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'کتاب', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'قطع', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'صفحه', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'تیراژ', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'مبلغ یک جلد', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'وضعیت', 'tabesh' ); ?></th>
								<th><?php esc_html_e( 'پیشرفت', 'tabesh' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$row_number = 0;
							foreach ( $all_orders as $order ) :
								++$row_number;
								$user          = get_userdata( $order->user_id );
								$customer_name = $user ? $user->display_name : __( 'نامشخص', 'tabesh' );

								// Get user billing info for province
								$province = '';
								$phone    = '';
								if ( $user ) {
									$province = get_user_meta( $order->user_id, 'billing_state', true );
									$phone    = get_user_meta( $order->user_id, 'billing_phone', true );
									if ( empty( $province ) ) {
										$province = get_user_meta( $order->user_id, 'billing_city', true );
									}
								}

								// Calculate unit price
								$unit_price = $order->quantity > 0 ? $order->total_price / $order->quantity : 0;

								// Get progress
								$progress = $status_progress[ $order->status ] ?? 0;

								// Get print substeps progress if in processing status
								if ( $order->status === 'processing' && isset( Tabesh()->print_substeps ) && method_exists( Tabesh()->print_substeps, 'calculate_print_progress' ) ) {
									$substep_progress = Tabesh()->print_substeps->calculate_print_progress( $order->id );
									// Blend the two progress values
									$progress = 25 + ( $substep_progress * 0.55 ); // Scale substeps to 25-80 range
								}
								?>
								<tr class="order-row" 
									data-order-id="<?php echo esc_attr( $order->id ); ?>"
									data-order-number="<?php echo esc_attr( $order->order_number ); ?>"
									data-book-title="<?php echo esc_attr( $order->book_title ); ?>"
									data-book-size="<?php echo esc_attr( $order->book_size ); ?>"
									data-customer-name="<?php echo esc_attr( $customer_name ); ?>"
									data-customer-phone="<?php echo esc_attr( $phone ); ?>"
									data-province="<?php echo esc_attr( $province ); ?>"
									data-user-id="<?php echo esc_attr( $order->user_id ); ?>"
									data-status="<?php echo esc_attr( $order->status ); ?>">
									<td class="row-number"><?php echo esc_html( $row_number ); ?></td>
									<td><span class="user-id"><?php echo esc_html( sprintf( '%02d', $order->user_id ) ); ?></span></td>
									<td class="customer-name"><?php echo esc_html( $customer_name ); ?></td>
									<td class="province-cell"><?php echo esc_html( $province ?: '—' ); ?></td>
									<td class="book-title-cell"><?php echo esc_html( $order->book_title ?: '—' ); ?></td>
									<td class="book-size-cell"><?php echo esc_html( $order->book_size ); ?></td>
									<td class="page-count-cell"><?php echo number_format( $order->page_count_total ); ?></td>
									<td class="quantity-cell"><?php echo number_format( $order->quantity ); ?></td>
									<td class="unit-price-cell"><?php echo number_format( $unit_price ); ?></td>
									<td>
										<span class="status-badge status-<?php echo esc_attr( $order->status ); ?>">
											<?php echo esc_html( $status_labels[ $order->status ] ?? $order->status ); ?>
										</span>
									</td>
									<td>
										<div class="progress-bar-container">
											<div class="progress-bar-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
										</div>
										<div class="progress-text"><?php echo esc_html( round( $progress ) ); ?>%</div>
									</td>
								</tr>
								<!-- Order Details Row (Hidden by default) -->
								<tr class="order-details-row" data-order-id="<?php echo esc_attr( $order->id ); ?>">
									<td colspan="11" class="order-details-cell">
										<div class="order-details-content">
											<?php
											// Include order details template
											$order_id = $order->id;
											include TABESH_PLUGIN_DIR . 'templates/admin/partials/order-details-tabs.php';
											?>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				
				<!-- Pagination -->
				<div class="pagination-container">
					<!-- Pagination will be generated by JavaScript -->
				</div>
			<?php endif; ?>
		</section>

		<!-- Full Screen Modal (for future use) -->
		<div class="fullscreen-modal">
			<div class="modal-header">
				<h3 class="modal-title"></h3>
				<button class="modal-close-btn" aria-label="<?php esc_attr_e( 'بستن', 'tabesh' ); ?>">✕</button>
			</div>
			<div class="modal-body"></div>
		</div>

		<?php
		// Render admin order creator modal
		Tabesh()->admin_order_creator->render_order_modal();
		?>
	</div>

	<?php
} else {
	// Customer view: Show their orders
	$user = Tabesh()->user;
	echo $user->render_user_orders( array() );
}
?>
