<?php
/**
 * Polylang + FSE Integration Bridge
 *
 * Provides multilingual template part dynamic routing and enables translation
 * management for FSE Navigation Menus (wp_navigation post type) in Polylang Free.
 *
 * @package EKA_Alexandria_Flagship
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Register wp_navigation post type for Polylang translation.
 */
add_filter('pll_get_post_types', function ($post_types, $is_settings) {
    if (!$is_settings) {
        $post_types['wp_navigation'] = 'wp_navigation';
    }
    return $post_types;
}, 10, 2);

/**
 * 2. Add an Admin Submenu under Appearance to manage FSE Navigation Menus directly.
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'themes.php',
        __('FSE Navigation Menus', 'ekalexandria-flagship'),
        __('FSE Nav Menus', 'ekalexandria-flagship'),
        'edit_theme_options',
        'edit.php?post_type=wp_navigation'
    );
}, 100);

/**
 * 3. Dynamically route FSE template parts and navigation blocks based on Polylang language context.
 */
add_filter('render_block_data', function ($parsed_block) {
    // Never run in admin canvas or REST request context to prevent editor corruption
    if (is_admin() || (function_exists('wp_is_json_request') && wp_is_json_request())) {
        return $parsed_block;
    }

    if (!function_exists('pll_current_language')) {
        return $parsed_block;
    }

    $lang = pll_current_language();
    if (!$lang) {
        return $parsed_block;
    }

    // A. Dynamic Template Part Routing (core/template-part)
    if (isset($parsed_block['blockName']) && 'core/template-part' === $parsed_block['blockName']) {
        $slug = $parsed_block['attrs']['slug'] ?? '';
        if ($slug) {
            // Determine base slug (strip existing lang suffix if any)
            $base_slug = preg_replace('/-(en|ar|el|gr)$/', '', $slug);
            $theme_dir = get_stylesheet_directory();

            // Language candidate slugs in priority order
            $candidate_slugs = [];
            if ($lang === 'el' || $lang === 'gr') {
                $candidate_slugs = [$base_slug . '-el', $base_slug . '-gr', $base_slug];
            } else {
                $candidate_slugs = [$base_slug . '-' . $lang, $base_slug];
            }

            foreach ($candidate_slugs as $candidate) {
                if (file_exists($theme_dir . '/parts/' . $candidate . '.html')) {
                    $parsed_block['attrs']['slug'] = $candidate;
                    break;
                }
            }
        }
    }

    // B. Dynamic FSE Navigation Block Translation (core/navigation)
    if (isset($parsed_block['blockName']) && 'core/navigation' === $parsed_block['blockName']) {
        if (isset($parsed_block['attrs']['ref'])) {
            $nav_post_id = intval($parsed_block['attrs']['ref']);
            if ($nav_post_id > 0 && function_exists('pll_get_post')) {
                $translated_nav_id = pll_get_post($nav_post_id, $lang);
                if ($translated_nav_id && get_post_status($translated_nav_id) === 'publish') {
                    $parsed_block['attrs']['ref'] = $translated_nav_id;
                }
            }
        }
    }

    return $parsed_block;
}, 10, 1);
