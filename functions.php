<?php
/**
 * EKA Alexandria Flagship Theme functions and definitions
 */

// Load custom features and CPT registrations
if ( file_exists( __DIR__ . '/inc/custom-features.php' ) ) {
	require_once __DIR__ . '/inc/custom-features.php';
}

// Load custom WP-CLI commands when WP-CLI is running
if ( defined( 'WP_CLI' ) && WP_CLI && file_exists( __DIR__ . '/inc/cli-commands.php' ) ) {
	require_once __DIR__ . '/inc/cli-commands.php';
}
