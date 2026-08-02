<?php
/**
 * Custom Post Type Rules & Sorting / Redirect Logic
 * Path: inc/cpt-rules.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Redirect single board_member requests to archive view (LIST VIEW ONLY)
 */
add_action( 'template_redirect', function() {
	if ( is_singular( 'board_member' ) ) {
		$archive_link = get_post_type_archive_link( 'board_member' );
		if ( ! $archive_link ) {
			$archive_link = home_url( '/board-members/' );
		}
		wp_safe_redirect( $archive_link, 301 );
		exit;
	}
} );

/**
 * Order board_member queries by menu_order ASC
 */
add_action( 'pre_get_posts', function( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'board_member' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', -1 );
	}
} );
