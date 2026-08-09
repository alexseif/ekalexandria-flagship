<?php
/**
 * bin/migrate-classic-menus-to-fse.php
 * Converts Classic Nav Menus into Gutenberg FSE `wp_navigation` posts based on `ai-work/menus.json`.
 * Configures Polylang translations, menu areas, and appends the Polylang Language Switcher to Top Bar menus.
 *
 * @package EKA_Alexandria_Flagship
 */

if (!defined('ABSPATH') && !defined('WP_CLI')) {
    echo "This script must be run within WordPress execution context.\n";
    exit(1);
}

echo "========================================\n";
echo "Migrating Classic Menus to FSE wp_navigation\n";
echo "========================================\n";

$json_path = dirname(__DIR__) . '/ai-work/menus.json';
if (!file_exists($json_path)) {
    echo "ERROR: menus.json not found at $json_path\n";
    exit(1);
}

$config = json_decode(file_get_contents($json_path), true);
if (!$config || !isset($config['menu_groups'])) {
    echo "ERROR: Invalid JSON structure in menus.json\n";
    exit(1);
}

/**
 * Build block markup recursively from hierarchical menu items, automatically resolving Polylang translations.
 */
function eka_build_nav_blocks_markup(array $items, int $parent_id = 0, string $lang = 'el'): string
{
    $markup = '';
    foreach ($items as $item) {
        if ((int)$item->menu_item_parent !== $parent_id) {
            continue;
        }

        $label = esc_html($item->title);
        $url   = esc_url($item->url);
        $kind  = ($item->type === 'custom') ? 'custom' : 'post-type';
        $type  = esc_attr($item->object);
        $id    = (int)$item->object_id;

        // Resolve translation for post-type objects if $lang is different
        if ($kind === 'post-type' && $id > 0 && function_exists('pll_get_post')) {
            $trans_id = pll_get_post($id, $lang);
            if ($trans_id > 0 && $trans_id !== $id) {
                $trans_post = get_post($trans_id);
                if ($trans_post) {
                    $id    = $trans_id;
                    $label = esc_html($trans_post->post_title);
                    $url   = esc_url(get_permalink($trans_id));
                }
            }
        }

        // Check if item has children
        $has_children = false;
        foreach ($items as $child) {
            if ((int)$child->menu_item_parent === (int)$item->db_id) {
                $has_children = true;
                break;
            }
        }

        if ($has_children) {
            $attrs = json_encode([
                'label' => $label,
                'url'   => $url,
                'kind'  => $kind,
                'type'  => $type,
                'id'    => $id,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $markup .= "<!-- wp:navigation-submenu {$attrs} -->\n";
            $markup .= eka_build_nav_blocks_markup($items, (int)$item->db_id, $lang);
            $markup .= "<!-- /wp:navigation-submenu -->\n\n";
        } else {
            $attrs = json_encode([
                'label' => $label,
                'url'   => $url,
                'kind'  => $kind,
                'type'  => $type,
                'id'    => $id,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $markup .= "<!-- wp:navigation-link {$attrs} /-->\n\n";
        }
    }
    return $markup;
}

foreach ($config['menu_groups'] as $group_key => $group_data) {
    $area                       = $group_data['area'] ?? '';
    $includes_language_switcher = !empty($group_data['includes_language_switcher']);
    $single_shared_menu         = !empty($group_data['single_shared_menu']);
    $translations               = $group_data['translations'] ?? [];

    $group_fse_posts = [];
    $fallback_classic_id = 0;

    // Find first valid classic menu ID in group for fallback
    foreach ($translations as $t_info) {
        if (!empty($t_info['classic_menu_id'])) {
            $fallback_classic_id = (int)$t_info['classic_menu_id'];
            break;
        }
    }

    foreach ($translations as $lang => $trans_info) {
        $classic_id    = $trans_info['classic_menu_id'] ?? $fallback_classic_id;
        $target_fse_id = $trans_info['wp_navigation_id'] ?? 0;
        $title         = $trans_info['title'] ?? "Navigation Menu ({$lang})";
        $block_content = '';

        if ($classic_id > 0) {
            $items = wp_get_nav_menu_items($classic_id);
            if ($items && !is_wp_error($items)) {
                $block_content = eka_build_nav_blocks_markup($items, 0, $lang);
            }
        }

        if ($includes_language_switcher) {
            $block_content .= "<!-- wp:polylang/navigation-language-switcher /-->\n";
        }

        // Create or update wp_navigation post
        $post_data = [
            'post_title'   => $title,
            'post_content' => trim($block_content),
            'post_status'  => 'publish',
            'post_type'    => 'wp_navigation',
        ];

        $post_exists = $target_fse_id > 0 ? get_post($target_fse_id) : null;
        if ($post_exists && $post_exists->post_type === 'wp_navigation') {
            $post_data['ID'] = $target_fse_id;
            wp_update_post($post_data);
            $fse_id = $target_fse_id;
            echo "Updated existing wp_navigation post ID $fse_id ('$title') for lang '$lang'\n";
        } else {
            $fse_id = wp_insert_post($post_data);
            echo "Created new wp_navigation post ID $fse_id ('$title') for lang '$lang'\n";
        }

        if ($fse_id && !is_wp_error($fse_id)) {
            if (function_exists('pll_set_post_language') && !$single_shared_menu) {
                pll_set_post_language($fse_id, $lang);
            }
            $group_fse_posts[$lang] = $fse_id;
        }

        if ($single_shared_menu) {
            break; // Single shared menu only needs one post created/updated
        }
    }

    // Save Polylang post translations for this group if not single shared
    if (!$single_shared_menu && !empty($group_fse_posts) && function_exists('pll_save_post_translations')) {
        pll_save_post_translations($group_fse_posts);
        echo "Saved Polylang post translations for group '$group_key': " . json_encode($group_fse_posts) . "\n";
    }
}

echo "Classic to FSE Menu migration completed successfully!\n";
