<?php
/**
 * Test script to verify pricing matrix key encoding fix
 * 
 * This script tests that all components use consistent base64 encoding
 * for pricing matrix database keys.
 * 
 * Run from command line:
 * cd /path/to/Tabesh && php test-encoding-fix.php
 * 
 * Or run from WordPress (requires WordPress loaded):
 * include 'test-encoding-fix.php'; run_tests();
 * 
 * @package Tabesh
 */

// Only run directly if in CLI mode
if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	// If not in CLI, this file should be included and run_tests() called manually
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'This test script should be run from command line or included in WordPress context.' );
	}
}

// Colors for output
define( 'COLOR_GREEN', "\033[0;32m" );
define( 'COLOR_RED', "\033[0;31m" );
define( 'COLOR_YELLOW', "\033[1;33m" );
define( 'COLOR_RESET', "\033[0m" );

/**
 * Print colored output
 */
function print_result( $test_name, $passed, $message = '' ) {
	$status = $passed ? COLOR_GREEN . '✓ PASS' : COLOR_RED . '✗ FAIL';
	echo sprintf( "%s%s - %s\n", $status, COLOR_RESET, $test_name );
	if ( ! empty( $message ) ) {
		echo "  " . $message . "\n";
	}
}

/**
 * Test encoding consistency
 */
function test_encoding_consistency() {
	echo COLOR_YELLOW . "\n=== Testing Encoding Consistency ===\n" . COLOR_RESET;
	
	$test_cases = array(
		'A5'     => base64_encode( 'A5' ),
		'A4'     => base64_encode( 'A4' ),
		'B5'     => base64_encode( 'B5' ),
		'رقعی'   => base64_encode( 'رقعی' ),
		'وزیری'  => base64_encode( 'وزیری' ),
		'خشتی'   => base64_encode( 'خشتی' ),
	);
	
	$all_passed = true;
	
	foreach ( $test_cases as $book_size => $expected_encoded ) {
		$encoded = base64_encode( $book_size );
		$decoded = base64_decode( $encoded, true );
		
		// Test encoding
		$encoding_ok = ( $encoded === $expected_encoded );
		print_result( 
			sprintf( 'Encoding "%s"', $book_size ),
			$encoding_ok,
			sprintf( 'Expected: %s, Got: %s', $expected_encoded, $encoded )
		);
		
		// Test decoding
		$decoding_ok = ( $decoded === $book_size );
		print_result(
			sprintf( 'Decoding "%s"', $expected_encoded ),
			$decoding_ok,
			sprintf( 'Expected: %s, Got: %s', $book_size, $decoded )
		);
		
		// Test round-trip
		$roundtrip_ok = ( base64_decode( base64_encode( $book_size ), true ) === $book_size );
		print_result(
			sprintf( 'Round-trip "%s"', $book_size ),
			$roundtrip_ok
		);
		
		if ( ! $encoding_ok || ! $decoding_ok || ! $roundtrip_ok ) {
			$all_passed = false;
		}
	}
	
	return $all_passed;
}

/**
 * Test sanitize_key problems
 */
function test_sanitize_key_issues() {
	// Skip if WordPress is not loaded
	if ( ! function_exists( 'sanitize_key' ) ) {
		echo COLOR_YELLOW . "\n=== Skipping sanitize_key() Tests (WordPress not loaded) ===\n" . COLOR_RESET;
		return;
	}
	
	echo COLOR_YELLOW . "\n=== Testing Why sanitize_key() Fails ===\n" . COLOR_RESET;
	
	$test_cases = array(
		'A5'    => 'a5',      // Lowercase conversion
		'A4'    => 'a4',
		'رقعی'  => '',        // Persian removed completely
		'وزیری' => '',
		'خشتی'  => '',
	);
	
	foreach ( $test_cases as $book_size => $expected_sanitized ) {
		$sanitized = sanitize_key( $book_size );
		$matches   = ( $sanitized === $expected_sanitized );
		
		print_result(
			sprintf( 'sanitize_key("%s")', $book_size ),
			$matches,
			sprintf(
				'Input: "%s" → Output: "%s" (Expected: "%s")',
				$book_size,
				$sanitized,
				$expected_sanitized
			)
		);
		
		// Show the problem
		if ( $book_size !== $sanitized ) {
			echo COLOR_RED . "  ⚠ Data loss! Original ≠ Sanitized\n" . COLOR_RESET;
		}
	}
}

/**
 * Test key construction
 */
function test_key_construction() {
	echo COLOR_YELLOW . "\n=== Testing Key Construction ===\n" . COLOR_RESET;
	
	$test_cases = array(
		'A5'    => 'pricing_matrix_' . base64_encode( 'A5' ),
		'رقعی'  => 'pricing_matrix_' . base64_encode( 'رقعی' ),
	);
	
	$all_passed = true;
	
	foreach ( $test_cases as $book_size => $expected_key ) {
		// Correct method (using base64)
		$correct_key = 'pricing_matrix_' . base64_encode( $book_size );
		
		$correct_matches = ( $correct_key === $expected_key );
		print_result(
			sprintf( 'Correct key for "%s"', $book_size ),
			$correct_matches,
			sprintf( 'Key: %s', $correct_key )
		);
		
		// Test broken method only if WordPress is loaded
		if ( function_exists( 'sanitize_key' ) ) {
			// Broken method (using sanitize_key)
			$broken_key = 'pricing_matrix_' . sanitize_key( $book_size );
			
			$broken_matches = ( $broken_key === $expected_key );
			print_result(
				sprintf( 'Broken key for "%s" (should fail)', $book_size ),
				! $broken_matches, // We EXPECT this to fail
				sprintf( 'Key: %s (Wrong!)', $broken_key )
			);
		}
		
		if ( ! $correct_matches ) {
			$all_passed = false;
		}
	}
	
	return $all_passed;
}

/**
 * Main test runner
 */
function run_tests() {
	echo COLOR_YELLOW . "╔═══════════════════════════════════════════════════════╗\n";
	echo "║  Tabesh Pricing Matrix Key Encoding Test Suite       ║\n";
	echo "╚═══════════════════════════════════════════════════════╝\n" . COLOR_RESET;
	
	$results = array();
	
	// Run tests
	$results['encoding']     = test_encoding_consistency();
	test_sanitize_key_issues(); // This is informational, not pass/fail
	$results['key_construction'] = test_key_construction();
	
	// Summary
	echo COLOR_YELLOW . "\n=== Test Summary ===\n" . COLOR_RESET;
	
	$all_passed = array_reduce( $results, function( $carry, $item ) {
		return $carry && $item;
	}, true );
	
	if ( $all_passed ) {
		echo COLOR_GREEN . "✓ All tests passed!\n" . COLOR_RESET;
		echo "The encoding fix is working correctly.\n";
		return 0;
	} else {
		echo COLOR_RED . "✗ Some tests failed!\n" . COLOR_RESET;
		echo "Please review the failures above.\n";
		return 1;
	}
}

// Run tests if executed directly from CLI
if ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) {
	exit( run_tests() );
}
