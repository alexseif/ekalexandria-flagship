<?php
/**
 * EKA Alexandria Flagship Theme functions and definitions
 */

// Theme setup and block support
add_action( 'after_setup_theme', function() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
} );

// Enqueue styles
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'ekalexandria-flagship-style', get_template_directory_uri() . '/style.css', array(), '1.0.0' );
	if ( is_rtl() ) {
		wp_enqueue_style( 'ekalexandria-flagship-rtl', get_template_directory_uri() . '/rtl.css', array( 'ekalexandria-flagship-style' ), '1.0.0' );
	}
} );

// Load custom features and CPT registrations
if ( file_exists( __DIR__ . '/inc/custom-features.php' ) ) {
	require_once __DIR__ . '/inc/custom-features.php';
}

// Load custom WP-CLI commands when WP-CLI is running
if ( defined( 'WP_CLI' ) && WP_CLI && file_exists( __DIR__ . '/inc/cli-commands.php' ) ) {
	require_once __DIR__ . '/inc/cli-commands.php';
}

