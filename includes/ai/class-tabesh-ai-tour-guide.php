<?php
/**
 * AI Tour Guide
 *
 * Provides interactive tour guides to help users navigate the site.
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tabesh_AI_Tour_Guide
 *
 * Handles interactive tour guides
 */
class Tabesh_AI_Tour_Guide {

	/**
	 * Get tour steps for a specific target
	 *
	 * @param string $target Target identifier.
	 * @return array Tour steps.
	 */
	public function get_tour_steps( $target ) {
		$tours = array(
			'order-form' => array(
				array(
					'selector' => '#book_title',
					'message'  => __( 'ابتدا عنوان کتاب خود را وارد کنید', 'tabesh' ),
					'arrow'    => 'top',
					'pulse'    => true,
				),
				array(
					'selector' => '#book_size',
					'message'  => __( 'سایز کتاب خود را انتخاب کنید', 'tabesh' ),
					'arrow'    => 'left',
					'pulse'    => true,
				),
				array(
					'selector' => '#paper_type',
					'message'  => __( 'نوع کاغذ مورد نظر را انتخاب کنید', 'tabesh' ),
					'arrow'    => 'left',
					'pulse'    => true,
				),
				array(
					'selector' => '#page_count',
					'message'  => __( 'تعداد صفحات کتاب را وارد کنید', 'tabesh' ),
					'arrow'    => 'top',
					'pulse'    => true,
				),
				array(
					'selector' => '#quantity',
					'message'  => __( 'تیراژ مورد نظر خود را مشخص کنید', 'tabesh' ),
					'arrow'    => 'top',
					'pulse'    => true,
				),
				array(
					'selector' => 'button[type="submit"]',
					'message'  => __( 'و در نهایت فرم را ارسال کنید', 'tabesh' ),
					'arrow'    => 'bottom',
					'pulse'    => true,
				),
			),
			'cart'       => array(
				array(
					'selector' => '.tabesh-cart-button',
					'message'  => __( 'سبد خرید شما در اینجاست', 'tabesh' ),
					'arrow'    => 'left',
					'pulse'    => true,
				),
			),
			'dashboard'  => array(
				array(
					'selector' => '.tabesh-user-dashboard',
					'message'  => __( 'از اینجا می‌توانید سفارشات خود را مشاهده کنید', 'tabesh' ),
					'arrow'    => 'top',
					'pulse'    => true,
				),
			),
		);

		// Get custom tours from settings.
		$custom_tours = get_option( 'tabesh_ai_custom_tours', array() );
		if ( is_array( $custom_tours ) ) {
			$tours = array_merge( $tours, $custom_tours );
		}

		return isset( $tours[ $target ] ) ? $tours[ $target ] : array();
	}

	/**
	 * Add custom tour
	 *
	 * @param string $target_id Unique tour identifier.
	 * @param array  $steps Tour steps.
	 * @return bool True on success, false on failure.
	 */
	public function add_custom_tour( $target_id, $steps ) {
		$custom_tours = get_option( 'tabesh_ai_custom_tours', array() );

		$custom_tours[ sanitize_key( $target_id ) ] = $steps;

		return update_option( 'tabesh_ai_custom_tours', $custom_tours );
	}

	/**
	 * Remove custom tour
	 *
	 * @param string $target_id Tour identifier.
	 * @return bool True on success, false on failure.
	 */
	public function remove_custom_tour( $target_id ) {
		$custom_tours = get_option( 'tabesh_ai_custom_tours', array() );

		if ( isset( $custom_tours[ $target_id ] ) ) {
			unset( $custom_tours[ $target_id ] );
			return update_option( 'tabesh_ai_custom_tours', $custom_tours );
		}

		return false;
	}
}
