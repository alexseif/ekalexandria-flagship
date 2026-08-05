#!/bin/bash
# bin/03-migrate-content.sh
# Content Transformation & Navigation Assignment Script (Script 3)
# Targets: /var/www/backstage.ekalexandria.org (DB: backstage_eka)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/03-migrate-content.log"
ENGINE_LOG="$LOG_DIR/content-engine.log"
MENU_LOG="$LOG_DIR/menu-assignments.log"

mkdir -p "$LOG_DIR"
> "$MAIN_LOG"
> "$ENGINE_LOG"
> "$MENU_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

echo "=========================================="
echo "Starting Content Transformation & Navigation Assignment: $(date)"
echo "Target DB: backstage_eka"
echo "=========================================="

# 1. Execute Content Transformation Engine (Steps 3A - 3F)
echo "Executing Content Transformation Engine (bin/migration-content-engine.php)..."
if [ -f "$THEME_DIR/bin/migration-content-engine.php" ]; then
    php7.4 $(which wp) eval-file "$THEME_DIR/bin/migration-content-engine.php" --path="$WP_DIR" --allow-root || { echo "ERROR: Content engine execution failed."; exit 1; }
else
    echo "ERROR: bin/migration-content-engine.php not found!"
    exit 1
fi

# 2. Transient Clean-Up (Step 3G)
echo "Flushing transient cache..."
cd "$WP_DIR" || exit 1
php7.4 $(which wp) transient delete --all --path="$WP_DIR" --allow-root

# 3. Page Template Assignments (Step 3H)
echo "Assigning FSE Page Templates (front-page-el/en/ar and page-parent-sidebar)..."
php7.4 $(which wp) eval-file --path="$WP_DIR" --allow-root - <<'PHP'
<?php
global $wpdb;

// Homepage template assignments
$greek_homepage_id = 13236;
if (get_post($greek_homepage_id)) {
    update_post_meta($greek_homepage_id, '_wp_page_template', 'front-page-el');
    echo "Assigned template 'front-page-el' to Greek Homepage (ID: $greek_homepage_id)\n";
}

// Find English & Arabic homepages
$en_home_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE (post_name = 'home-en' OR post_name = 'en' OR post_title LIKE '%Home%') AND post_type = 'page' AND post_status = 'publish' LIMIT 1");
if ($en_home_id) {
    update_post_meta($en_home_id, '_wp_page_template', 'front-page-en');
    echo "Assigned template 'front-page-en' to English Homepage (ID: $en_home_id)\n";
}

$ar_home_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE (post_name = 'home-ar' OR post_name = 'ar' OR post_title LIKE '%الرئيسية%') AND post_type = 'page' AND post_status = 'publish' LIMIT 1");
if ($ar_home_id) {
    update_post_meta($ar_home_id, '_wp_page_template', 'front-page-ar');
    echo "Assigned template 'front-page-ar' to Arabic Homepage (ID: $ar_home_id)\n";
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
PHP

# 4. Navigation Menu Assignments, Footer Seeding & Sidebar Injection (Final Task)
echo "Assigning navigation menu locations..."
php7.4 $(which wp) eka assign-menus --path="$WP_DIR" --allow-root >> "$MENU_LOG" 2>&1

echo "Seeding footer navigation posts..."
php7.4 $(which wp) eka seed-footer-menus --path="$WP_DIR" --allow-root >> "$MENU_LOG" 2>&1

echo "Executing sidebar navigation menu injection..."
# TODO: Sidebar menu assignment for parent/sub-pages is specified here, but implementation logic is pending in the next phase.
php7.4 $(which wp) eval-file --path="$WP_DIR" --allow-root - <<'PHP'
<?php
// Sidebar navigation menu injection for specified parent/sub-pages
$sidebar_menus = [
    70 => [12],
    3377 => [16912],
    3378 => [16909],
    71 => [16],
    3944 => [17102],
    3945 => [17105],
    117 => [14],
    3707 => [16936],
    3716 => [16933],
];

foreach ($sidebar_menus as $menu_id => $page_ids) {
    foreach ($page_ids as $page_id) {
        $post = get_post($page_id);
        if ($post && strpos($post->post_content, 'wp:navigation') === false) {
            $nav_block = '<!-- wp:navigation {"ref":' . $menu_id . ',"layout":{"type":"flex","orientation":"vertical"}} /-->';
            $new_content = '<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"width":"33%"} -->
<div class="wp-block-column" style="flex-basis:33%">' . $nav_block . '</div>
<!-- /wp:column -->
<!-- wp:column {"width":"66%"} -->
<div class="wp-block-column" style="flex-basis:66%">' . $post->post_content . '</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->';
            wp_update_post(['ID' => $page_id, 'post_content' => $new_content]);
            echo "Injected sidebar navigation menu (ref: $menu_id) into page ID $page_id\n";
        }
    }
}
PHP

echo "Content transformation & navigation assignment pipeline complete successfully at $(date)!"
exit 0
