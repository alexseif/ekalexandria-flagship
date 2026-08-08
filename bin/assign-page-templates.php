<?php

/**
 * bin/assign-page-templates.php
 * Assigns FSE Page Templates (front-page-*, index-*, news-*, page-*) to target home, news, and translated pages.
 * Performs transient and object cache invalidations to ensure clean FSE rendering.
 */

global $wpdb;

function eka_flush_all_caches()
{
    $stylesheet = get_stylesheet();
    delete_transient('wp_theme_files_' . $stylesheet);
    delete_transient('wp_theme_files_ekalexandria-flagship');
    delete_transient('pll_languages');

    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    if (function_exists('PLL') && isset(PLL()->model) && method_exists(PLL()->model, 'clean_language_cache')) {
        PLL()->model->clean_language_cache();
    }

    echo "Cleared theme transients and flushed object cache.\n";
}

// 1. Initial Cache Flush
eka_flush_all_caches();

// 2. Homepage publishing & template assignments
$en_home_id = 16894;
$ar_home_id = 16892;

// Publish English and Arabic homepage pages before assignment
foreach ([$en_home_id, $ar_home_id] as $home_id) {
    $post_obj = get_post($home_id);
    if ($post_obj && $post_obj->post_status !== 'publish') {
        wp_update_post([
            'ID'          => $home_id,
            'post_status' => 'publish',
        ]);
        echo "Published homepage (ID: $home_id, Title: '{$post_obj->post_title}')\n";
    }
}

$greek_homepage_id = 13236;
if (get_post($greek_homepage_id)) {
    update_post_meta($greek_homepage_id, '_wp_page_template', 'front-page');
    echo "Assigned template 'front-page' to Greek Homepage (ID: $greek_homepage_id)\n";
}

if (!$en_home_id || !get_post($en_home_id)) {
    $en_home_id = (int)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE (post_name = 'front-en' OR post_name = 'home-en' OR post_name = 'en' OR post_title LIKE '%Home%' OR post_title LIKE '%Welcome%') AND post_type = 'page' AND post_status = 'publish' LIMIT 1");
}
if ($en_home_id) {
    update_post_meta($en_home_id, '_wp_page_template', 'front-page-en');
    echo "Assigned template 'front-page-en' to English Homepage (ID: $en_home_id)\n";
}

if (!$ar_home_id || !get_post($ar_home_id)) {
    $ar_home_id = (int)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE (post_name = 'front-ar' OR post_name = 'home-ar' OR post_name = 'ar' OR post_title LIKE '%الرئيسية%' OR post_title LIKE '%مرحباً%') AND post_type = 'page' AND post_status = 'publish' LIMIT 1");
}
if ($ar_home_id) {
    update_post_meta($ar_home_id, '_wp_page_template', 'front-page-ar');
    echo "Assigned template 'front-page-ar' to Arabic Homepage (ID: $ar_home_id)\n";
}

$home_ids = array_filter([$greek_homepage_id, (int)$en_home_id, (int)$ar_home_id]);

// 3. News/posts page template assignments (Explicit IDs: 18 -> index, 16920 -> index-en, 16923 -> index-ar)
$index_mappings = [
    18    => 'index',
    16920 => 'index-en',
    16923 => 'index-ar'
];

foreach ($index_mappings as $idx_id => $tmpl_slug) {
    if (get_post($idx_id)) {
        update_post_meta($idx_id, '_wp_page_template', $tmpl_slug);
        echo "Assigned template '$tmpl_slug' to Posts Index page (ID: $idx_id)\n";
    }
}

$posts_page_id = (int) get_option('page_for_posts');
$posts_page_ids = array_keys($index_mappings);
if ($posts_page_id && !in_array($posts_page_id, $posts_page_ids, true)) {
    $posts_page_ids[] = $posts_page_id;
    $template_slug = 'index';

    if (function_exists('pll_get_post_language')) {
        $language = pll_get_post_language($posts_page_id, 'slug');
        if ('en' === $language) {
            $template_slug = 'index-en';
        } elseif ('ar' === $language) {
            $template_slug = 'index-ar';
        }
    }

    update_post_meta($posts_page_id, '_wp_page_template', $template_slug);
    echo "Assigned template '$template_slug' to posts page (ID: $posts_page_id)\n";
}

// 4. Multilingual template assignments for standard pages
if (function_exists('pll_get_post_language')) {
    $all_pages = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish'");
    foreach ($all_pages as $page) {
        $pid = (int)$page->ID;
        if (in_array($pid, $home_ids, true) || in_array($pid, $posts_page_ids, true)) {
            continue;
        }

        $lang = pll_get_post_language($pid, 'slug');
        $existing_tmpl = get_post_meta($pid, '_wp_page_template', true);

        // Don't overwrite custom template if set to tachydromos, board-members, news-ar, news-en, etc.
        if (!empty($existing_tmpl) && in_array($existing_tmpl, ['tachydromos', 'board-members', 'news-ar', 'news-en', 'news'], true)) {
            continue;
        }

        $target_tmpl = 'page';
        if ($lang === 'en') {
            $target_tmpl = 'page-en';
        } elseif ($lang === 'ar') {
            $target_tmpl = 'page-ar';
        }

        if ($existing_tmpl !== $target_tmpl) {
            update_post_meta($pid, '_wp_page_template', $target_tmpl);
            echo "Assigned template '$target_tmpl' to page ID: $pid ('{$page->post_title}')\n";
        }
    }
}

// 5. Final Cache Flush & Invalidation
eka_flush_all_caches();
