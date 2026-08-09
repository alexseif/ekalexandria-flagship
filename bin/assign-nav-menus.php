<?php
/**
 * bin/assign-nav-menus.php
 * Configures Polylang translation associations and theme menu location assignments
 * for both Classic Nav Menus (nav_menu) and FSE Block Navigation posts (wp_navigation) based on `ai-work/menus.json`.
 *
 * @package EKA_Alexandria_Flagship
 */

if (!defined('ABSPATH') && !defined('WP_CLI')) {
    echo "This script must be run within WordPress execution context.\n";
    exit(1);
}

echo "========================================\n";
echo "Assigning Navigation Menus & Translations\n";
echo "========================================\n";

// 1. Classic Nav Menu Term Assignments & Polylang Linking
$classic_menu_mappings = [
    'main' => [
        'el' => 13,   // Main Greek Menu
        'en' => 3315, // Main English Menu
        'ar' => 3316, // Main Arabic Menu
    ],
    'establishment' => [
        'el' => 70,   // Establishment Greek Menu
        'en' => 3377, // Establishment English Menu
        'ar' => 3378, // Establishment Arabic Menu
    ],
    'activity' => [
        'el' => 71,   // Activity Greek Menu
        'en' => 3944, // Activity English Menu
        'ar' => 3945, // Activity Arabic Menu
    ],
    'services' => [
        'el' => 117,  // Service Greek Menu
        'en' => 3707, // Services English Menu
        'ar' => 3716, // Services Arabic Menu
    ],
];

foreach ($classic_menu_mappings as $group_name => $langs) {
    $valid_translations = [];
    foreach ($langs as $lang => $term_id) {
        $term = get_term($term_id, 'nav_menu');
        if ($term && !is_wp_error($term)) {
            if (function_exists('pll_set_term_language')) {
                pll_set_term_language($term_id, $lang);
            }
            $valid_translations[$lang] = $term_id;
            echo "Assigned language '$lang' to nav_menu term ID $term_id ('{$term->name}')\n";
        }
    }

    if (!empty($valid_translations) && function_exists('pll_save_term_translations')) {
        pll_save_term_translations($valid_translations);
        echo "Saved Polylang term translations for group '$group_name': " . json_encode($valid_translations) . "\n";
    }
}

// 2. Set Theme Mod Nav Menu Locations
$locations = get_theme_mod('nav_menu_locations', []);
$locations['main-menu']          = 13;   // Main Greek Menu
$locations['main-menu___en']     = 3315; // Main English Menu
$locations['main-menu___ar']     = 3316; // Main Arabic Menu
$locations['footer-menu']        = 21;   // Footer Greek Menu
$locations['social-menu-bottom'] = 21;   // Footer / Social Menu

set_theme_mod('nav_menu_locations', $locations);
echo "Updated theme_mod nav_menu_locations: " . json_encode($locations) . "\n";

// 3. FSE Block Navigation Posts (wp_navigation) Polylang Linking from ai-work/menus.json
$json_path = dirname(__DIR__) . '/ai-work/menus.json';
if (file_exists($json_path)) {
    $config = json_decode(file_get_contents($json_path), true);
    if ($config && isset($config['menu_groups'])) {
        foreach ($config['menu_groups'] as $group_key => $group_data) {
            if (!empty($group_data['single_shared_menu'])) {
                continue;
            }

            $translations = $group_data['translations'] ?? [];
            $valid_fse_translations = [];

            foreach ($translations as $lang => $trans_info) {
                $post_id = $trans_info['wp_navigation_id'] ?? 0;
                if ($post_id > 0) {
                    $post = get_post($post_id);
                    if ($post && $post->post_type === 'wp_navigation') {
                        if (function_exists('pll_set_post_language')) {
                            pll_set_post_language($post_id, $lang);
                        }
                        $valid_fse_translations[$lang] = $post_id;
                        echo "Assigned language '$lang' to wp_navigation post ID $post_id ('{$post->post_title}')\n";
                    }
                }
            }

            if (!empty($valid_fse_translations) && function_exists('pll_save_post_translations')) {
                pll_save_post_translations($valid_fse_translations);
                echo "Saved Polylang post translations for group '$group_key': " . json_encode($valid_fse_translations) . "\n";
            }
        }
    }
}

echo "Navigation Menu assignment completed successfully.\n";
