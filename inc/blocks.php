<?php
/**
 * Dynamic Gutenberg Block Registrations & Block Styles
 * Path: inc/blocks.php
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

add_action('init', function () {
	// 1. Register homepage-services-grid dynamic block from block.json
	$block_json_path = get_template_directory() . '/blocks/homepage-services-grid/block.json';
	if (file_exists($block_json_path)) {
		register_block_type($block_json_path, [
			'render_callback' => 'eka_render_homepage_services_grid',
		]);
	} else {
		register_block_type('eka/homepage-services-grid', [
			'render_callback' => 'eka_render_homepage_services_grid',
		]);
	}

	// 2. Register child-pages-grid dynamic block
	register_block_type('eka/child-pages-grid', [
		'render_callback' => 'eka_render_child_pages_grid',
		'attributes' => [
			'columns' => [
				'type' => 'number',
				'default' => 4,
			],
		],
	]);

	// 3. Register child-pages-sidebar dynamic block
	register_block_type('eka/child-pages-sidebar', [
		'render_callback' => 'eka_render_child_pages_sidebar',
	]);

	// 4. Register news-carousel dynamic block
	register_block_type('eka/news-carousel', [
		'render_callback' => 'eka_render_news_carousel',
		'attributes' => [
			'postsPerPage' => [
				'type' => 'number',
				'default' => 6,
			],
		],
	]);

	// 5. Register core/query block style 'is-style-news-carousel'
	register_block_style('core/query', [
		'name' => 'news-carousel',
		'label' => __('News Carousel Slider', 'ekalexandria-flagship'),
		'is_default' => false,
	]);

	// 6. Register core/gallery block style 'legacy-slider'
	register_block_style('core/gallery', [
		'name' => 'legacy-slider',
		'label' => __('Legacy Slider', 'ekalexandria-flagship'),
		'is_default' => false,
	]);
});

/**
 * Enqueue Block Editor Assets
 */
add_action('enqueue_block_editor_assets', function () {
	$editor_js_file = get_template_directory() . '/assets/js/blocks-editor.js';
	if (file_exists($editor_js_file)) {
		wp_enqueue_script(
			'eka-blocks-editor',
			get_template_directory_uri() . '/assets/js/blocks-editor.js',
			['wp-blocks', 'wp-element', 'wp-server-side-render'],
			filemtime($editor_js_file),
			true
		);
	}
});

/**
 * Render Homepage Services Grid Block
 */
function eka_render_homepage_services_grid($attributes)
{
	wp_enqueue_style('wp-block-columns');

	$args = [
		'post_type' => 'page',
		'post__in' => [7837, 8088, 28, 14],
		'orderby' => 'post__in',
		'posts_per_page' => 4,
	];
	$query = new WP_Query($args);

	ob_start();

	if ($query->have_posts()) {
		echo '<div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-7387b849 wp-block-columns-is-layout-flex homepage-pages-grid">';
		while ($query->have_posts()) {
			$query->the_post();
			echo '<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow post-' . get_the_ID() . ' page type-page status-publish has-post-thumbnail hentry">';
			echo '<h2 class="wp-block-post-title"><a href="' . esc_url(get_permalink()) . '" target="_self">' . esc_html(get_the_title()) . '</a></h2>';
			if (has_post_thumbnail()) {
				echo '<figure style="aspect-ratio:16/9" class="wp-block-post-featured-image"><a href="' . esc_url(get_permalink()) . '" target="_self"><img width="237" height="300" src="' . get_the_post_thumbnail_url(get_the_ID(), 'medium') . '" class="attachment-medium size-medium wp-post-image" alt="Ίδρυση" style="width:100%;height:100%;object-fit:cover;" decoding="async" loading="lazy"></a></figure>';
			}
			echo '<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">' . wp_kses_post(get_the_excerpt()) . '</p></div>';
			echo '</div>';
		}
		echo '</div>';
		wp_reset_postdata();
	}
	return ob_get_clean();
}

/**
 * Render Child Pages Grid Block
 */
function eka_render_child_pages_grid($attributes)
{
	global $post;
	$parent_id = isset($post->ID) ? $post->ID : 0;
	if (!$parent_id) {
		return '';
	}

	$children = get_children([
		'post_parent' => $parent_id,
		'post_type' => 'page',
		'post_status' => 'publish',
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);

	if (empty($children)) {
		return '';
	}

	ob_start();
	echo '<div class="wp-block-eka-child-pages-grid eka-child-grid">';
	foreach ($children as $child) {
		echo '<div class="eka-child-card">';
		if (has_post_thumbnail($child->ID)) {
			echo '<div class="eka-child-thumbnail"><a href="' . esc_url(get_permalink($child->ID)) . '">' . get_the_post_thumbnail($child->ID, 'medium') . '</a></div>';
		}
		echo '<h4 class="eka-child-title"><a href="' . esc_url(get_permalink($child->ID)) . '">' . esc_html(get_the_title($child->ID)) . '</a></h4>';
		echo '</div>';
	}
	echo '</div>';
	return ob_get_clean();
}

/**
 * Render Child Pages Sidebar Block
 */
function eka_render_child_pages_sidebar($attributes)
{
	global $post;
	if (!isset($post->ID)) {
		return '';
	}

	$ancestors = get_post_ancestors($post);
	$root_id = !empty($ancestors) ? end($ancestors) : $post->ID;
	$children = get_children([
		'post_parent' => $root_id,
		'post_type' => 'page',
		'post_status' => 'publish',
		'orderby' => 'menu_order title',
		'order' => 'ASC',
	]);

	ob_start();
	echo '<aside class="wp-block-eka-child-pages-sidebar eka-sidebar-menu">';
	echo '<h3 class="eka-sidebar-title">' . esc_html(get_the_title($root_id)) . '</h3>';
	if (!empty($children)) {
		echo '<ul class="eka-sidebar-links">';
		foreach ($children as $child) {
			$active_class = ($child->ID === $post->ID) ? ' class="active"' : '';
			echo '<li' . $active_class . '><a href="' . esc_url(get_permalink($child->ID)) . '">' . esc_html(get_the_title($child->ID)) . '</a></li>';
		}
		echo '</ul>';
	}
	echo '</aside>';
	return ob_get_clean();
}

/**
 * Render News Carousel Block
 */
function eka_render_news_carousel($attributes)
{
	$posts_per_page = isset($attributes['postsPerPage']) ? intval($attributes['postsPerPage']) : 6;
	$query = new WP_Query([
		'post_type' => 'post',
		'posts_per_page' => $posts_per_page,
		'post_status' => 'publish',
	]);

	ob_start();
	if ($query->have_posts()) {
		echo '<div class="wp-block-eka-news-carousel eka-news-carousel-container">';
		echo '<div class="eka-carousel-wrapper">';
		while ($query->have_posts()) {
			$query->the_post();
			echo '<div class="eka-carousel-slide">';
			if (has_post_thumbnail()) {
				echo '<div class="eka-slide-image"><a href="' . esc_url(get_permalink()) . '">' . get_the_post_thumbnail(get_the_ID(), 'large') . '</a></div>';
			}
			echo '<div class="eka-slide-content">';
			echo '<h3 class="eka-slide-title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
			echo '<p class="eka-slide-date">' . esc_html(get_the_date()) . '</p>';
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
		wp_reset_postdata();
	}
	return ob_get_clean();
}
