<?php
/**
 * Order Form Slider Integration Class
 *
 * Provides a modern, multi-step animated order form with Revolution Slider integration support.
 * This form emits JavaScript events for real-time slider preview updates.
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_Order_Form_Slider
 *
 * Handles rendering of the slider-integrated order form and event emission.
 */
class Tabesh_Order_Form_Slider {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialization.
	}

	/**
	 * Render the slider-integrated order form
	 *
	 * Shortcode: [tabesh_order_form_slider]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts = array() ) {
		// Check if V2 pricing engine is enabled.
		$pricing_engine = new Tabesh_Pricing_Engine();
		if ( ! $pricing_engine->is_enabled() ) {
			return '<div class="tabesh-message error" dir="rtl"><p><strong>' .
				esc_html__( 'خطا:', 'tabesh' ) .
				'</strong> ' .
				esc_html__( 'موتور قیمت‌گذاری نسخه ۲ فعال نیست. لطفاً ابتدا از پنل تنظیمات، موتور قیمت‌گذاری جدید را فعال کنید.', 'tabesh' ) .
				'</p></div>';
		}

		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'show_title'      => 'yes',
				'redirect_url'    => home_url( '/user-orders/' ),
				'theme'           => 'light', // light or dark.
				'animation_speed' => 'normal', // slow, normal, fast.
			),
			$atts,
			'tabesh_order_form_slider'
		);

		// Enqueue specific assets for this form.
		$this->enqueue_assets();

		// Start output buffering.
		ob_start();

		// Pass attributes to template.
		$show_title      = ( $atts['show_title'] === 'yes' );
		$redirect_url    = esc_url( $atts['redirect_url'] );
		$theme           = sanitize_text_field( $atts['theme'] );
		$animation_speed = sanitize_text_field( $atts['animation_speed'] );

		// Include template.
		include TABESH_PLUGIN_DIR . 'templates/frontend/order-form-slider.php';

		return ob_get_clean();
	}

	/**
	 * Enqueue form-specific assets
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		// Helper function to safely get file modification time.
		$get_file_version = function ( $file_path ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $file_path ) ) {
				$mtime = @filemtime( $file_path );
				return $mtime !== false ? $mtime : TABESH_VERSION;
			}
			return TABESH_VERSION;
		};

		// Enqueue CSS.
		wp_enqueue_style(
			'tabesh-order-form-slider',
			TABESH_PLUGIN_URL . 'assets/css/order-form-slider.css',
			array(),
			$get_file_version( TABESH_PLUGIN_DIR . 'assets/css/order-form-slider.css' )
		);

		// Enqueue JS.
		wp_enqueue_script(
			'tabesh-order-form-slider',
			TABESH_PLUGIN_URL . 'assets/js/order-form-slider.js',
			array( 'jquery' ),
			$get_file_version( TABESH_PLUGIN_DIR . 'assets/js/order-form-slider.js' ),
			true
		);

		// Localize script with configuration.
		wp_localize_script(
			'tabesh-order-form-slider',
			'tabeshSliderForm',
			array(
				'apiUrl'        => rest_url( TABESH_REST_NAMESPACE ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'userOrdersUrl' => home_url( '/user-orders/' ),
				'i18n'          => array(
					'loading'             => __( 'در حال بارگذاری...', 'tabesh' ),
					'calculating'         => __( 'در حال محاسبه قیمت...', 'tabesh' ),
					'submitting'          => __( 'در حال ثبت سفارش...', 'tabesh' ),
					'error'               => __( 'خطا در پردازش درخواست', 'tabesh' ),
					'success'             => __( 'عملیات با موفقیت انجام شد', 'tabesh' ),
					'noOptions'           => __( 'هیچ گزینه‌ای در دسترس نیست', 'tabesh' ),
					'selectFirst'         => __( 'ابتدا گزینه قبلی را انتخاب کنید', 'tabesh' ),
					'invalidField'        => __( 'لطفاً این فیلد را پر کنید', 'tabesh' ),
					'priceCalculated'     => __( 'قیمت محاسبه شد', 'tabesh' ),
					'orderSubmitted'      => __( 'سفارش با موفقیت ثبت شد', 'tabesh' ),
					'pleaseFillAllFields' => __( 'لطفاً تمام فیلدهای الزامی را پر کنید', 'tabesh' ),
					'calculateFirst'      => __( 'لطفاً ابتدا قیمت را محاسبه کنید', 'tabesh' ),
				),
			)
		);
	}
}
