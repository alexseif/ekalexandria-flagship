<?php

/**
 * bin/assign-page-templates.php
 * Assigns FSE Page Templates (front-page-el/en/ar and page-parent-sidebar) to target pages.
 */

global $wpdb;

// Homepage template assignments
$greek_homepage_id = 13236;
if (get_post($greek_homepage_id)) {
    update_post_meta($greek_homepage_id, '_wp_page_template', 'front-page-el');
    echo "Assigned template 'front-page-el' to Greek Homepage (ID: $greek_homepage_id)\n";
}

// Find English & Arabic homepages
$en_home_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE (post_name = 'front-en' OR post_name = 'home-en' OR post_name = 'en' OR post_title LIKE '%Home%') AND post_type = 'page' AND post_status = 'publish' LIMIT 1");
if ($en_home_id) {
    update_post_meta($en_home_id, '_wp_page_template', 'front-page-en');
    echo "Assigned template 'front-page-en' to English Homepage (ID: $en_home_id)\n";
}

$ar_home_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE (post_name = 'front-ar' OR post_name = 'home-ar' OR post_name = 'ar' OR post_title LIKE '%الرئيسية%') AND post_type = 'page' AND post_status = 'publish' LIMIT 1");
if ($ar_home_id) {
    update_post_meta($ar_home_id, '_wp_page_template', 'front-page-ar');
    echo "Assigned template 'front-page-ar' to Arabic Homepage (ID: $ar_home_id)\n";
}

// News/posts page template assignments
$posts_page_id = (int) get_option('page_for_posts');
if ($posts_page_id) {
    $template_slug = 'index-el';

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

    if (function_exists('pll_get_post_translations')) {
        $translations = pll_get_post_translations($posts_page_id);
        foreach ($translations as $language => $translated_id) {
            $translated_template = 'index-el';
            if ('en' === $language) {
                $translated_template = 'index-en';
            } elseif ('ar' === $language) {
                $translated_template = 'index-ar';
            }

            if ($translated_id && (int) $translated_id !== $posts_page_id) {
                update_post_meta((int) $translated_id, '_wp_page_template', $translated_template);
                echo "Assigned template '$translated_template' to translated posts page (ID: $translated_id)\n";
            }
        }
    }
}

// Parent pages: Ίδρυση, Υπηρεσίες, Δραστηριότητες and children
$parent_titles = ['Ίδρυση', 'Υπηρεσίες', 'Δραστηριότητες', 'Establishment', 'Services', 'Activities'];
foreach ($parent_titles as $title) {
    $parent_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s AND post_type = 'page' AND post_status = 'publish'", '%' . $title . '%'));
    foreach ($parent_ids as $pid) {
        update_post_meta($pid, '_wp_page_template', 'page-parent-sidebar');
        echo "Assigned 'page-parent-sidebar' to parent page ID $pid ($title)\n";

        // Child pages
        $child_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'page' AND post_status = 'publish'", $pid));
        foreach ($child_ids as $cid) {
            update_post_meta($cid, '_wp_page_template', 'page-parent-sidebar');
            echo "Assigned 'page-parent-sidebar' to child page ID $cid\n";
        }
    }
}
