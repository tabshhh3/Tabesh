<?php
/**
 * Diagnostic Script for Pricing Cycle Issue
 *
 * This script helps diagnose the broken cycle between:
 * 1. Pricing Form (tabesh_product_pricing)
 * 2. Pricing Engine
 * 3. Constraint Manager  
 * 4. Order Form V2 (tabesh_order_form_v2)
 *
 * Run this by accessing: /wp-content/plugins/Tabesh/diagnostic-pricing-cycle.php
 * (Only for development/debugging)
 *
 * @package Tabesh
 */

// Load WordPress
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Security check - only for admins
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Admins only.' );
}

// Helper function to format output
function diagnostic_output( $title, $data, $is_good = null ) {
	echo '<div style="margin: 20px 0; padding: 15px; border: 2px solid ' . ( $is_good === true ? 'green' : ( $is_good === false ? 'red' : '#ccc' ) ) . '; background: #f9f9f9;">';
	echo '<h3 style="margin: 0 0 10px 0; color: ' . ( $is_good === true ? 'green' : ( $is_good === false ? 'red' : '#333' ) ) . ';">' . esc_html( $title ) . '</h3>';
	echo '<pre style="background: white; padding: 10px; overflow: auto; max-height: 400px;">';
	echo esc_html( print_r( $data, true ) );
	echo '</pre>';
	echo '</div>';
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
	<meta charset="UTF-8">
	<title>Tabesh - Pricing Cycle Diagnostic</title>
	<style>
		body {
			font-family: Tahoma, Arial, sans-serif;
			padding: 20px;
			background: #f0f0f0;
		}
		.container {
			max-width: 1200px;
			margin: 0 auto;
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		h1 {
			color: #0073aa;
			border-bottom: 3px solid #0073aa;
			padding-bottom: 10px;
		}
		h2 {
			color: #333;
			margin-top: 30px;
			border-bottom: 2px solid #ddd;
			padding-bottom: 5px;
		}
		.status {
			padding: 10px 15px;
			margin: 15px 0;
			border-radius: 5px;
			font-weight: bold;
		}
		.status.error {
			background: #ffebee;
			border: 2px solid #f44336;
			color: #c62828;
		}
		.status.warning {
			background: #fff3e0;
			border: 2px solid #ff9800;
			color: #e65100;
		}
		.status.success {
			background: #e8f5e9;
			border: 2px solid #4caf50;
			color: #2e7d32;
		}
		.recommendation {
			background: #e3f2fd;
			border: 2px solid #2196f3;
			padding: 15px;
			margin: 20px 0;
			border-radius: 5px;
		}
		.recommendation h3 {
			margin: 0 0 10px 0;
			color: #1976d2;
		}
	</style>
</head>
<body>
<div class="container">
	<h1>🔍 تشخیص مشکل چرخه قیمت‌گذاری</h1>
	<p>این صفحه وضعیت چرخه کامل از ذخیره قیمت تا نمایش در فرم سفارش را بررسی می‌کند.</p>

	<?php
	// Step 1: Check Product Parameters (Source of Truth)
	echo '<h2>📋 مرحله ۱: پارامترهای محصول (منبع اصلی)</h2>';
	
	global $wpdb;
	$table_settings = $wpdb->prefix . 'tabesh_settings';
	
	$book_sizes_json = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT setting_value FROM {$table_settings} WHERE setting_key = %s",
			'book_sizes'
		)
	);
	
	$product_book_sizes = array();
	if ( $book_sizes_json ) {
		$decoded = json_decode( $book_sizes_json, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			$product_book_sizes = $decoded;
		}
	}
	
	if ( empty( $product_book_sizes ) ) {
		echo '<div class="status error">⚠️ هیچ قطع کتابی در پارامترهای محصول تعریف نشده است!</div>';
		diagnostic_output( 'قطع‌های کتاب در تنظیمات محصول (book_sizes)', 'EMPTY - این منبع اصلی است و باید حتماً پر باشد', false );
	} else {
		echo '<div class="status success">✓ قطع‌های کتاب در پارامترهای محصول یافت شد (' . count( $product_book_sizes ) . ' عدد)</div>';
		diagnostic_output( 'قطع‌های کتاب در تنظیمات محصول (book_sizes)', $product_book_sizes, true );
	}
	
	// Step 2: Check Pricing Matrices
	echo '<h2>💰 مرحله ۲: ماتریس‌های قیمت‌گذاری ذخیره شده</h2>';
	
	$pricing_matrices = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT setting_key, setting_value FROM {$table_settings} WHERE setting_key LIKE %s",
			'pricing_matrix_%'
		),
		ARRAY_A
	);
	
	if ( empty( $pricing_matrices ) ) {
		echo '<div class="status error">⚠️ هیچ ماتریس قیمت‌گذاری در دیتابیس یافت نشد!</div>';
		diagnostic_output( 'ماتریس‌های قیمت‌گذاری', 'EMPTY - هیچ قیمتی تنظیم نشده', false );
	} else {
		echo '<div class="status success">✓ تعداد ' . count( $pricing_matrices ) . ' ماتریس قیمت‌گذاری یافت شد</div>';
		
		// Decode each matrix to show book sizes
		$pricing_engine   = new Tabesh_Pricing_Engine();
		$configured_sizes = array();
		$corrupted_keys   = array();
		
		foreach ( $pricing_matrices as $row ) {
			$key       = $row['setting_key'];
			$safe_key  = str_replace( 'pricing_matrix_', '', $key );
			
			// Try to decode using pricing engine's method (via reflection)
			$reflection      = new ReflectionClass( $pricing_engine );
			$decode_method   = $reflection->getMethod( 'decode_book_size_key' );
			$decode_method->setAccessible( true );
			$book_size = $decode_method->invoke( $pricing_engine, $safe_key );
			
			if ( ! empty( $book_size ) ) {
				$configured_sizes[] = array(
					'book_size' => $book_size,
					'safe_key'  => $safe_key,
					'full_key'  => $key,
				);
			} else {
				$corrupted_keys[] = $key;
			}
		}
		
		diagnostic_output( 'ماتریس‌های قیمت قابل خواندن', $configured_sizes, count( $configured_sizes ) > 0 );
		
		if ( ! empty( $corrupted_keys ) ) {
			diagnostic_output( 'کلیدهای خراب (نمی‌توان رمزگشایی کرد)', $corrupted_keys, false );
		}
	}
	
	// Step 3: Check Pricing Engine
	echo '<h2>⚙️ مرحله ۳: موتور قیمت‌گذاری</h2>';
	
	$pricing_engine       = new Tabesh_Pricing_Engine();
	$is_v2_enabled        = $pricing_engine->is_enabled();
	$engine_book_sizes    = $pricing_engine->get_configured_book_sizes();
	
	if ( ! $is_v2_enabled ) {
		echo '<div class="status error">⚠️ موتور قیمت‌گذاری V2 فعال نیست!</div>';
	} else {
		echo '<div class="status success">✓ موتور قیمت‌گذاری V2 فعال است</div>';
	}
	
	diagnostic_output( 
		'قطع‌های کتاب خوانده شده توسط Pricing Engine', 
		$engine_book_sizes,
		! empty( $engine_book_sizes )
	);
	
	// Step 4: Check Constraint Manager
	echo '<h2>🔗 مرحله ۴: مدیر محدودیت‌ها (Constraint Manager)</h2>';
	
	try {
		$constraint_manager = new Tabesh_Constraint_Manager();
		$available_sizes    = $constraint_manager->get_available_book_sizes();
		
		if ( empty( $available_sizes ) ) {
			echo '<div class="status error">⚠️ Constraint Manager هیچ قطع کتابی برنمی‌گرداند!</div>';
			diagnostic_output( 'قطع‌های در دسترس از Constraint Manager', 'EMPTY', false );
		} else {
			echo '<div class="status success">✓ Constraint Manager تعداد ' . count( $available_sizes ) . ' قطع کتاب برمی‌گرداند</div>';
			diagnostic_output( 'قطع‌های در دسترس از Constraint Manager', $available_sizes, true );
		}
	} catch ( Exception $e ) {
		echo '<div class="status error">⚠️ خطا در Constraint Manager: ' . esc_html( $e->getMessage() ) . '</div>';
		diagnostic_output( 'خطای Constraint Manager', $e->getMessage(), false );
		$available_sizes = array();
	}
	
	// Step 5: Analysis and Recommendations
	echo '<h2>📊 تحلیل و توصیه‌ها</h2>';
	
	// Check alignment between product params and pricing matrices
	$sizes_in_both    = array();
	$sizes_only_param = array();
	$sizes_only_price = array();
	
	foreach ( $product_book_sizes as $size ) {
		if ( in_array( $size, $engine_book_sizes, true ) ) {
			$sizes_in_both[] = $size;
		} else {
			$sizes_only_param[] = $size;
		}
	}
	
	foreach ( $engine_book_sizes as $size ) {
		if ( ! in_array( $size, $product_book_sizes, true ) ) {
			$sizes_only_price[] = $size;
		}
	}
	
	echo '<div class="recommendation">';
	echo '<h3>🎯 وضعیت همسویی داده‌ها</h3>';
	
	if ( ! empty( $sizes_in_both ) ) {
		echo '<p><strong style="color: green;">✓ قطع‌های صحیح (' . count( $sizes_in_both ) . ' عدد):</strong><br>';
		echo 'این قطع‌ها هم در تنظیمات محصول هستند و هم قیمت‌گذاری دارند:<br>';
		echo '<code>' . esc_html( implode( '، ', $sizes_in_both ) ) . '</code></p>';
	}
	
	if ( ! empty( $sizes_only_param ) ) {
		echo '<p><strong style="color: orange;">⚠ قطع‌های بدون قیمت (' . count( $sizes_only_param ) . ' عدد):</strong><br>';
		echo 'این قطع‌ها در تنظیمات محصول هستند اما قیمت‌گذاری ندارند:<br>';
		echo '<code>' . esc_html( implode( '، ', $sizes_only_param ) ) . '</code><br>';
		echo '<em>راه حل: از فرم [tabesh_product_pricing] قیمت این قطع‌ها را تنظیم کنید.</em></p>';
	}
	
	if ( ! empty( $sizes_only_price ) ) {
		echo '<p><strong style="color: red;">❌ قطع‌های خراب/غیرمجاز (' . count( $sizes_only_price ) . ' عدد):</strong><br>';
		echo 'این قطع‌ها قیمت‌گذاری دارند اما در تنظیمات محصول نیستند:<br>';
		echo '<code>' . esc_html( implode( '، ', $sizes_only_price ) ) . '</code><br>';
		echo '<em>این یک باگ است! این قطع‌ها نباید ذخیره می‌شدند. باید حذف شوند.</em></p>';
	}
	
	echo '</div>';
	
	// Final verdict
	echo '<h2>✅ نتیجه‌گیری نهایی</h2>';
	
	$issues = array();
	
	if ( empty( $product_book_sizes ) ) {
		$issues[] = 'تنظیمات محصول (book_sizes) خالی است - این منبع اصلی است';
	}
	
	if ( empty( $engine_book_sizes ) ) {
		$issues[] = 'هیچ ماتریس قیمت‌گذاری وجود ندارد';
	}
	
	if ( ! $is_v2_enabled ) {
		$issues[] = 'موتور قیمت‌گذاری V2 غیرفعال است';
	}
	
	if ( empty( $available_sizes ) ) {
		$issues[] = 'Constraint Manager هیچ قطع در دسترسی برنمی‌گرداند (فرم سفارش خراب است)';
	}
	
	if ( ! empty( $sizes_only_price ) ) {
		$issues[] = 'قطع‌های غیرمجاز در دیتابیس ذخیره شده‌اند (داده خراب)';
	}
	
	if ( empty( $issues ) ) {
		echo '<div class="status success">';
		echo '<h3>🎉 همه چیز درست کار می‌کند!</h3>';
		echo '<p>چرخه قیمت‌گذاری به درستی تنظیم شده و فرم سفارش باید کار کند.</p>';
		echo '<p><strong>تعداد قطع‌های فعال:</strong> ' . count( $sizes_in_both ) . '</p>';
		echo '</div>';
	} else {
		echo '<div class="status error">';
		echo '<h3>❌ مشکلات یافت شده:</h3>';
		echo '<ul>';
		foreach ( $issues as $issue ) {
			echo '<li>' . esc_html( $issue ) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
		
		echo '<div class="recommendation">';
		echo '<h3>💡 توصیه‌های رفع مشکل:</h3>';
		echo '<ol>';
		
		if ( empty( $product_book_sizes ) ) {
			echo '<li>ابتدا به <strong>تنظیمات محصول</strong> بروید و قطع‌های کتاب را تعریف کنید</li>';
		}
		
		if ( ! $is_v2_enabled ) {
			echo '<li>از فرم [tabesh_product_pricing] موتور قیمت‌گذاری V2 را فعال کنید</li>';
		}
		
		if ( empty( $engine_book_sizes ) && ! empty( $product_book_sizes ) ) {
			echo '<li>از فرم [tabesh_product_pricing] برای هر قطع کتاب قیمت‌گذاری تنظیم کنید</li>';
		}
		
		if ( ! empty( $sizes_only_price ) ) {
			echo '<li>داده‌های خراب را پاک کنید (ماتریس‌های قیمت برای قطع‌های غیرمجاز)</li>';
		}
		
		echo '</ol>';
		echo '</div>';
	}
	?>
	
	<hr style="margin: 40px 0;">
	<p style="text-align: center; color: #666;">
		<small>برای اطلاعات بیشتر، لاگ‌های WP_DEBUG را بررسی کنید.</small>
	</p>
</div>
</body>
</html>
