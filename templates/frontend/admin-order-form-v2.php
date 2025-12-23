<?php
/**
 * Admin Order Form V2 Template - Matrix-Based Pricing with Customer Selection
 *
 * Modern admin order form with V2 pricing engine integration
 * Includes customer search/creation and optional SMS sending
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get constraint manager to fetch available book sizes.
try {
	$constraint_manager = new Tabesh_Constraint_Manager();
	$available_sizes    = $constraint_manager->get_available_book_sizes();

	// Log for debugging if WP_DEBUG is enabled.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Tabesh Admin Order Form V2: Available book sizes count: ' . count( $available_sizes ) );
		if ( empty( $available_sizes ) ) {
			error_log( 'Tabesh Admin Order Form V2: WARNING - No book sizes configured in pricing matrix' );
		}
	}
} catch ( Exception $e ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Tabesh Admin Order Form V2 Error: ' . $e->getMessage() );
	}
	$available_sizes = array();
}

// Scalar settings.
$min_quantity  = Tabesh()->get_setting( 'min_quantity', 10 );
$max_quantity  = Tabesh()->get_setting( 'max_quantity', 10000 );
$quantity_step = Tabesh()->get_setting( 'quantity_step', 10 );

// Get title from attributes.
$form_title = isset( $atts['title'] ) ? $atts['title'] : __( 'ثبت سفارش جدید (ویژه مدیر)', 'tabesh' );
?>

<div class="tabesh-admin-wizard-container" dir="rtl">
	<?php if ( empty( $available_sizes ) ) : ?>
		<div class="tabesh-wizard-error">
			<div class="error-icon">⚠️</div>
			<h3><?php echo esc_html__( 'خطا در بارگذاری فرم', 'tabesh' ); ?></h3>
			<p><?php echo esc_html__( 'هیچ قطع کتابی با قیمت‌گذاری فعال در سیستم یافت نشد.', 'tabesh' ); ?></p>
			
			<?php if ( current_user_can( 'manage_woocommerce' ) ) : ?>
				<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
					<h4 style="margin: 0 0 10px 0;"><?php echo esc_html__( 'راهنمای مدیر سیستم:', 'tabesh' ); ?></h4>
					<p>
						<?php echo esc_html__( 'لطفاً ابتدا به', 'tabesh' ); ?> 
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=tabesh-product-pricing' ) ); ?>" class="error-link" style="font-weight: bold;">
							<?php echo esc_html__( 'مدیریت قیمت‌گذاری محصولات', 'tabesh' ); ?>
						</a> 
						<?php echo esc_html__( 'بروید و ماتریس قیمت را برای قطع‌های کتاب تنظیم کنید.', 'tabesh' ); ?>
					</p>
				</div>
			<?php else : ?>
				<p><?php echo esc_html__( 'لطفاً با مدیر سیستم تماس بگیرید.', 'tabesh' ); ?></p>
			<?php endif; ?>
		</div>
	<?php else : ?>

	<!-- Header -->
	<div class="admin-wizard-header">
		<h2 class="admin-wizard-title">
			<span class="dashicons dashicons-cart"></span>
			<?php echo esc_html( $form_title ); ?>
		</h2>
		<p class="admin-wizard-subtitle">
			<?php echo esc_html__( 'ثبت سفارش به نام مشتری با موتور قیمت‌گذاری V2', 'tabesh' ); ?>
		</p>
	</div>

	<!-- Progress Bar -->
	<div class="wizard-progress">
		<div class="progress-bar">
			<div class="progress-fill" id="adminProgressBar"></div>
		</div>
		<div class="progress-steps">
			<div class="progress-step active" data-step="1">
				<div class="step-circle">1</div>
				<span class="step-label"><?php echo esc_html__( 'مشتری', 'tabesh' ); ?></span>
			</div>
			<div class="progress-step" data-step="2">
				<div class="step-circle">2</div>
				<span class="step-label"><?php echo esc_html__( 'عنوان و قطع', 'tabesh' ); ?></span>
			</div>
			<div class="progress-step" data-step="3">
				<div class="step-circle">3</div>
				<span class="step-label"><?php echo esc_html__( 'کاغذ و چاپ', 'tabesh' ); ?></span>
			</div>
			<div class="progress-step" data-step="4">
				<div class="step-circle">4</div>
				<span class="step-label"><?php echo esc_html__( 'صحافی', 'tabesh' ); ?></span>
			</div>
			<div class="progress-step" data-step="5">
				<div class="step-circle">5</div>
				<span class="step-label"><?php echo esc_html__( 'تکمیل', 'tabesh' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Wizard Form -->
	<div class="wizard-form-wrapper">
		<form id="tabesh-admin-wizard-form" class="wizard-form">

			<!-- Step 1: Customer Selection -->
			<div class="wizard-step active" data-step="1">
				<div class="step-header">
					<h2 class="step-title">
						<span class="step-icon">👤</span>
						<?php echo esc_html__( 'انتخاب مشتری', 'tabesh' ); ?>
					</h2>
					<p class="step-description">
						<?php echo esc_html__( 'ابتدا مشتری را جستجو کنید یا کاربر جدید ایجاد کنید', 'tabesh' ); ?>
					</p>
				</div>
				<div class="step-content">
					<!-- Customer Type Selection -->
					<div class="form-group">
						<label class="form-label">
							<?php echo esc_html__( 'نوع مشتری', 'tabesh' ); ?>
							<span class="required">*</span>
						</label>
						<div class="customer-type-toggle">
							<label class="toggle-option">
								<input type="radio" name="customer_type" value="existing" checked>
								<span><?php echo esc_html__( 'مشتری موجود', 'tabesh' ); ?></span>
							</label>
							<label class="toggle-option">
								<input type="radio" name="customer_type" value="new">
								<span><?php echo esc_html__( 'مشتری جدید', 'tabesh' ); ?></span>
							</label>
						</div>
					</div>

					<!-- Existing Customer Search -->
					<div id="existing-customer-section" class="customer-section">
						<div class="form-group">
							<label for="customer_search" class="form-label">
								<?php echo esc_html__( 'جستجوی مشتری', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<input 
								type="text" 
								id="customer_search" 
								class="form-control"
								placeholder="<?php echo esc_attr__( 'نام، موبایل یا ایمیل مشتری را وارد کنید...', 'tabesh' ); ?>"
							>
							<div id="customer_search_results" class="search-results"></div>
							<input type="hidden" id="selected_customer_id" name="customer_id">
						</div>
						<div id="selected_customer_display" class="selected-customer"></div>
					</div>

					<!-- New Customer Form -->
					<div id="new-customer-section" class="customer-section" style="display: none;">
						<div class="form-row">
							<div class="form-group">
								<label for="new_customer_name" class="form-label">
									<?php echo esc_html__( 'نام و نام خانوادگی', 'tabesh' ); ?>
									<span class="required">*</span>
								</label>
								<input 
									type="text" 
									id="new_customer_name" 
									name="new_customer_name"
									class="form-control"
									placeholder="<?php echo esc_attr__( 'نام کامل مشتری', 'tabesh' ); ?>"
								>
							</div>
							<div class="form-group">
								<label for="new_customer_mobile" class="form-label">
									<?php echo esc_html__( 'موبایل', 'tabesh' ); ?>
									<span class="required">*</span>
								</label>
								<input 
									type="tel" 
									id="new_customer_mobile" 
									name="new_customer_mobile"
									class="form-control"
									placeholder="<?php echo esc_attr__( '09123456789', 'tabesh' ); ?>"
									pattern="09[0-9]{9}"
								>
							</div>
						</div>
						<div class="form-group">
							<label for="new_customer_email" class="form-label">
								<?php echo esc_html__( 'ایمیل', 'tabesh' ); ?>
							</label>
							<input 
								type="email" 
								id="new_customer_email" 
								name="new_customer_email"
								class="form-control"
								placeholder="<?php echo esc_attr__( 'example@email.com', 'tabesh' ); ?>"
							>
						</div>
						<button type="button" id="create_customer_btn" class="button button-secondary">
							<?php echo esc_html__( 'ایجاد مشتری', 'tabesh' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Step 2: Book Title & Size -->
			<div class="wizard-step" data-step="2">
				<div class="step-header">
					<h2 class="step-title">
						<span class="step-icon">📖</span>
						<?php echo esc_html__( 'اطلاعات اولیه کتاب', 'tabesh' ); ?>
					</h2>
					<p class="step-description">
						<?php echo esc_html__( 'عنوان کتاب و قطع مورد نظر را انتخاب کنید', 'tabesh' ); ?>
					</p>
				</div>
				<div class="step-content">
					<div class="form-group">
						<label for="book_title_admin" class="form-label">
							<?php echo esc_html__( 'عنوان کتاب', 'tabesh' ); ?>
							<span class="required">*</span>
						</label>
						<input 
							type="text" 
							id="book_title_admin" 
							name="book_title" 
							class="form-control"
							placeholder="<?php echo esc_attr__( 'نام کتابی که می‌خواهید چاپ کنید...', 'tabesh' ); ?>"
							required
						>
					</div>

					<div class="form-group">
						<label class="form-label">
							<?php echo esc_html__( 'قطع کتاب', 'tabesh' ); ?>
							<span class="required">*</span>
						</label>
						<div class="book-size-grid">
							<?php foreach ( $available_sizes as $size_info ) : ?>
								<?php if ( $size_info['enabled'] ) : ?>
								<label class="size-option">
									<input 
										type="radio" 
										name="book_size" 
										value="<?php echo esc_attr( $size_info['size'] ); ?>"
										data-size="<?php echo esc_attr( $size_info['size'] ); ?>"
										required
									>
									<span class="size-card">
										<span class="size-name"><?php echo esc_html( $size_info['size'] ); ?></span>
										<span class="size-info">
											<?php echo esc_html( $size_info['paper_count'] ); ?> نوع کاغذ
										</span>
									</span>
								</label>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 3: Paper & Print Specifications -->
			<div class="wizard-step" data-step="3">
				<div class="step-header">
					<h2 class="step-title">
						<span class="step-icon">📄</span>
						<?php echo esc_html__( 'مشخصات کاغذ و چاپ', 'tabesh' ); ?>
					</h2>
					<p class="step-description">
						<?php echo esc_html__( 'نوع کاغذ، گرماژ و نوع چاپ را انتخاب کنید', 'tabesh' ); ?>
					</p>
				</div>
				<div class="step-content">
					<div class="form-row">
						<div class="form-group">
							<label for="paper_type_admin" class="form-label">
								<?php echo esc_html__( 'نوع کاغذ', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<select id="paper_type_admin" name="paper_type" class="form-control" required>
								<option value=""><?php echo esc_html__( 'انتخاب کنید...', 'tabesh' ); ?></option>
							</select>
						</div>

						<div class="form-group">
							<label for="paper_weight_admin" class="form-label">
								<?php echo esc_html__( 'گرماژ کاغذ', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<select id="paper_weight_admin" name="paper_weight" class="form-control" required disabled>
								<option value=""><?php echo esc_html__( 'ابتدا نوع کاغذ را انتخاب کنید', 'tabesh' ); ?></option>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label class="form-label">
							<?php echo esc_html__( 'نوع چاپ', 'tabesh' ); ?>
							<span class="required">*</span>
						</label>
						<div class="print-type-grid" id="print_type_container_admin">
							<p class="form-hint"><?php echo esc_html__( 'ابتدا نوع و گرماژ کاغذ را انتخاب کنید', 'tabesh' ); ?></p>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group">
							<label for="page_count_admin" class="form-label">
								<?php echo esc_html__( 'تعداد صفحات', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<input 
								type="number" 
								id="page_count_admin" 
								name="page_count" 
								class="form-control"
								min="4"
								max="5000"
								step="4"
								value="100"
								required
							>
							<span class="form-hint"><?php echo esc_html__( 'باید مضرب ۴ باشد', 'tabesh' ); ?></span>
						</div>

						<div class="form-group">
							<label for="quantity_admin" class="form-label">
								<?php echo esc_html__( 'تیراژ (تعداد)', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<input 
								type="number" 
								id="quantity_admin" 
								name="quantity" 
								class="form-control"
								min="<?php echo esc_attr( $min_quantity ); ?>"
								max="<?php echo esc_attr( $max_quantity ); ?>"
								step="<?php echo esc_attr( $quantity_step ); ?>"
								value="<?php echo esc_attr( $min_quantity ); ?>"
								required
							>
							<span class="form-hint">
								<?php
								/* translators: 1: minimum quantity, 2: maximum quantity */
								echo esc_html( sprintf( __( 'حداقل %1$d، حداکثر %2$d', 'tabesh' ), $min_quantity, $max_quantity ) );
								?>
							</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 4: Binding & Cover -->
			<div class="wizard-step" data-step="4">
				<div class="step-header">
					<h2 class="step-title">
						<span class="step-icon">📘</span>
						<?php echo esc_html__( 'صحافی و جلد', 'tabesh' ); ?>
					</h2>
					<p class="step-description">
						<?php echo esc_html__( 'نوع صحافی، گرماژ جلد و خدمات اضافی را انتخاب کنید', 'tabesh' ); ?>
					</p>
				</div>
				<div class="step-content">
					<div class="form-row">
						<div class="form-group">
							<label for="binding_type_admin" class="form-label">
								<?php echo esc_html__( 'نوع صحافی', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<select id="binding_type_admin" name="binding_type" class="form-control" required>
								<option value=""><?php echo esc_html__( 'انتخاب کنید...', 'tabesh' ); ?></option>
							</select>
						</div>

						<div class="form-group">
							<label for="cover_weight_admin" class="form-label">
								<?php echo esc_html__( 'گرماژ جلد', 'tabesh' ); ?>
								<span class="required">*</span>
							</label>
							<select id="cover_weight_admin" name="cover_weight" class="form-control" required disabled>
								<option value=""><?php echo esc_html__( 'ابتدا نوع صحافی را انتخاب کنید', 'tabesh' ); ?></option>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label class="form-label">
							<?php echo esc_html__( 'خدمات اضافی', 'tabesh' ); ?>
						</label>
						<div id="extras_container_admin" class="extras-grid">
							<p class="form-hint"><?php echo esc_html__( 'در حال بارگذاری...', 'tabesh' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 5: Review & Submit -->
			<div class="wizard-step" data-step="5">
				<div class="step-header">
					<h2 class="step-title">
						<span class="step-icon">✅</span>
						<?php echo esc_html__( 'بررسی و تکمیل سفارش', 'tabesh' ); ?>
					</h2>
					<p class="step-description">
						<?php echo esc_html__( 'مشخصات سفارش را بررسی کرده و سفارش را ثبت نهایی کنید', 'tabesh' ); ?>
					</p>
				</div>
				<div class="step-content">
					<!-- Order Review -->
					<div class="order-review-section">
						<h3><?php echo esc_html__( 'خلاصه سفارش', 'tabesh' ); ?></h3>
						<div id="order_review_content" class="review-content">
							<!-- Will be populated by JS -->
						</div>
					</div>

					<!-- Price Display -->
					<div class="price-section">
						<button type="button" id="calculate_price_admin_btn" class="button button-secondary">
							<?php echo esc_html__( 'محاسبه قیمت', 'tabesh' ); ?>
						</button>
						<div id="price_display_admin" class="price-display">
							<!-- Will be populated after calculation -->
						</div>
					</div>

					<!-- Notes -->
					<div class="form-group">
						<label for="notes_admin" class="form-label">
							<?php echo esc_html__( 'توضیحات سفارش', 'tabesh' ); ?>
						</label>
						<textarea 
							id="notes_admin" 
							name="notes" 
							class="form-control"
							rows="4"
							placeholder="<?php echo esc_attr__( 'توضیحات اضافی در مورد سفارش...', 'tabesh' ); ?>"
						></textarea>
					</div>

					<!-- SMS Option -->
					<div class="form-group">
						<label class="checkbox-label">
							<input type="checkbox" id="send_sms_admin" name="send_sms" value="1">
							<span><?php echo esc_html__( 'ارسال پیامک به مشتری', 'tabesh' ); ?></span>
						</label>
						<p class="form-hint">
							<?php echo esc_html__( 'در صورت فعال بودن، پیامک تأیید سفارش برای مشتری ارسال می‌شود', 'tabesh' ); ?>
						</p>
					</div>
				</div>
			</div>

		</form>
	</div>

	<!-- Navigation Buttons -->
	<div class="wizard-navigation">
		<button type="button" id="adminPrevBtn" class="button button-secondary" style="display: none;">
			<?php echo esc_html__( 'مرحله قبل', 'tabesh' ); ?>
		</button>
		<button type="button" id="adminNextBtn" class="button button-primary">
			<?php echo esc_html__( 'مرحله بعد', 'tabesh' ); ?>
		</button>
		<button type="button" id="adminSubmitBtn" class="button button-primary" style="display: none;">
			<?php echo esc_html__( 'ثبت سفارش', 'tabesh' ); ?>
		</button>
	</div>

	<!-- Success Message (Hidden by default) -->
	<div id="success_message_admin" class="success-message" style="display: none;">
		<div class="success-icon">✓</div>
		<h3><?php echo esc_html__( 'سفارش با موفقیت ثبت شد!', 'tabesh' ); ?></h3>
		<p id="success_order_link"></p>
		<button type="button" id="create_another_order_btn" class="button button-primary">
			<?php echo esc_html__( 'ثبت سفارش جدید', 'tabesh' ); ?>
		</button>
	</div>

	<?php endif; ?>
</div>
