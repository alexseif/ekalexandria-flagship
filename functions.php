<?php
/**
 * EKA Alexandria Flagship Theme Functions
 */

if ( file_exists( __DIR__ . '/inc/custom-features.php' ) ) {
	require_once __DIR__ . '/inc/custom-features.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI && file_exists( __DIR__ . '/inc/cli-commands.php' ) ) {
	require_once __DIR__ . '/inc/cli-commands.php';
}
