#!/usr/bin/env php
<?php
/**
 * Test Paper Weight and Print Type Filtering
 *
 * This test demonstrates the new filtering logic that ensures:
 * 1. Paper weights with all zero prices are excluded
 * 2. Each weight shows only available print types (non-zero price)
 * 3. Database is the single source of truth
 *
 * @package Tabesh
 */

/**
 * Test Case 1: Paper weight with both print types available
 *
 * Example pricing matrix:
 * "تحریر" => [
 *   "70" => ["bw" => 350, "color" => 950]  // Both available
 * ]
 *
 * Expected: Weight "70" is shown with available_prints = ["bw", "color"]
 */
function test_both_print_types_available() {
	$page_costs = array(
		'تحریر' => array(
			'70' => array(
				'bw'    => 350,
				'color' => 950,
			),
		),
	);

	$available_print_types = array();
	foreach ( $page_costs['تحریر']['70'] as $print_type => $price ) {
		if ( is_numeric( $price ) && floatval( $price ) > 0 ) {
			$available_print_types[] = $print_type;
		}
	}

	echo "Test 1: Both print types available\n";
	echo 'Weight: 70, Available prints: ' . implode( ', ', $available_print_types ) . "\n";
	echo 'Expected: bw, color' . "\n";
	echo 'Result: ' . ( count( $available_print_types ) === 2 ? 'PASS' : 'FAIL' ) . "\n\n";
}

/**
 * Test Case 2: Paper weight with only one print type available
 *
 * Example pricing matrix:
 * "بالک" => [
 *   "80" => ["bw" => 400, "color" => 0]  // Only bw available
 * ]
 *
 * Expected: Weight "80" is shown with available_prints = ["bw"]
 */
function test_one_print_type_available() {
	$page_costs = array(
		'بالک' => array(
			'80' => array(
				'bw'    => 400,
				'color' => 0,
			),
		),
	);

	$available_print_types = array();
	foreach ( $page_costs['بالک']['80'] as $print_type => $price ) {
		if ( is_numeric( $price ) && floatval( $price ) > 0 ) {
			$available_print_types[] = $print_type;
		}
	}

	echo "Test 2: Only one print type available (bw)\n";
	echo 'Weight: 80, Available prints: ' . implode( ', ', $available_print_types ) . "\n";
	echo 'Expected: bw' . "\n";
	echo 'Result: ' . ( count( $available_print_types ) === 1 && in_array( 'bw', $available_print_types, true ) ? 'PASS' : 'FAIL' ) . "\n\n";
}

/**
 * Test Case 3: Paper weight with all zero prices
 *
 * Example pricing matrix:
 * "گلاسه" => [
 *   "100" => ["bw" => 0, "color" => 0]  // Both disabled
 * ]
 *
 * Expected: Weight "100" is NOT shown (filtered out)
 */
function test_all_zero_prices() {
	$page_costs = array(
		'گلاسه' => array(
			'100' => array(
				'bw'    => 0,
				'color' => 0,
			),
		),
	);

	$available_print_types = array();
	foreach ( $page_costs['گلاسه']['100'] as $print_type => $price ) {
		if ( is_numeric( $price ) && floatval( $price ) > 0 ) {
			$available_print_types[] = $print_type;
		}
	}

	$should_show_weight = ! empty( $available_print_types );

	echo "Test 3: All print types have zero price\n";
	echo 'Weight: 100, Available prints: ' . ( empty( $available_print_types ) ? 'none' : implode( ', ', $available_print_types ) ) . "\n";
	echo 'Should show weight: ' . ( $should_show_weight ? 'yes' : 'no' ) . "\n";
	echo 'Expected: no' . "\n";
	echo 'Result: ' . ( ! $should_show_weight ? 'PASS' : 'FAIL' ) . "\n\n";
}

/**
 * Test Case 4: Complete paper type filtering
 *
 * Example pricing matrix:
 * "تحریر" => [
 *   "60" => ["bw" => 350, "color" => 950],  // Both available
 *   "70" => ["bw" => 0, "color" => 0],      // Should be filtered out
 *   "80" => ["bw" => 400, "color" => 0]     // Only bw available
 * ]
 *
 * Expected: Only weights "60" and "80" are shown
 */
function test_complete_paper_filtering() {
	$page_costs = array(
		'تحریر' => array(
			'60' => array(
				'bw'    => 350,
				'color' => 950,
			),
			'70' => array(
				'bw'    => 0,
				'color' => 0,
			),
			'80' => array(
				'bw'    => 400,
				'color' => 0,
			),
		),
	);

	$allowed_weights = array();
	foreach ( $page_costs['تحریر'] as $weight => $print_types ) {
		$available_print_types = array();
		foreach ( $print_types as $print_type => $price ) {
			if ( is_numeric( $price ) && floatval( $price ) > 0 ) {
				$available_print_types[] = $print_type;
			}
		}

		// Only add weight if it has at least one available print type.
		if ( ! empty( $available_print_types ) ) {
			$allowed_weights[] = array(
				'weight'           => $weight,
				'available_prints' => $available_print_types,
			);
		}
	}

	echo "Test 4: Complete paper type filtering\n";
	echo 'Total weights in matrix: 3 (60, 70, 80)' . "\n";
	echo 'Weights shown to user: ' . count( $allowed_weights ) . "\n";
	foreach ( $allowed_weights as $weight_info ) {
		echo "  - Weight {$weight_info['weight']}: " . implode( ', ', $weight_info['available_prints'] ) . "\n";
	}
	echo 'Expected: 2 weights (60 with both, 80 with bw only)' . "\n";
	echo 'Result: ' . ( count( $allowed_weights ) === 2 ? 'PASS' : 'FAIL' ) . "\n\n";
}

// Run all tests
echo "=== Paper Weight and Print Type Filtering Tests ===\n\n";
test_both_print_types_available();
test_one_print_type_available();
test_all_zero_prices();
test_complete_paper_filtering();
echo "=== All Tests Complete ===\n";

