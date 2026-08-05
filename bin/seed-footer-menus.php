<?php
/**
 * bin/seed-footer-menus.php
 * Seed language-specific footer navigation menus (idempotent).
 */

global $wpdb;

$menus_to_seed = [
    [
        'slug' => 'footer-english-menu',
        'title' => 'Footer English Menu',
        'content' => '<!-- wp:navigation-link {"label":"Establishment","url":"/en/establishment/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Services","url":"/en/services/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Activities","url":"/en/activities/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"News","url":"/en/news/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Contact","url":"/en/contact/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Privacy Policy","url":"/en/privacy-policy/","isTopLevelLink":true} /-->'
    ],
    [
        'slug' => 'footer-arabic-menu',
        'title' => 'Footer Arabic Menu',
        'content' => '<!-- wp:navigation-link {"label":"التأسيس","url":"/ar/establishment/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"الخدمات","url":"/ar/services/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"الأنشطة","url":"/ar/activities/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"الأخبار","url":"/ar/news/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"اتصل بنا","url":"/ar/contact/","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"سياسة الخصوصية","url":"/ar/privacy-policy/","isTopLevelLink":true} /-->'
    ]
];

foreach ($menus_to_seed as $menu) {
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'wp_navigation' AND post_status = 'publish'",
        $menu['slug']
    ));

    if ($existing_id) {
        echo "Skipping existing menu '{$menu['title']}' (ID: $existing_id)\n";
        continue;
    }

    $post_id = wp_insert_post([
        'post_title'   => $menu['title'],
        'post_name'    => $menu['slug'],
        'post_content' => $menu['content'],
        'post_status'  => 'publish',
        'post_type'    => 'wp_navigation',
    ]);

    if (is_wp_error($post_id)) {
        echo "Failed to seed menu '{$menu['title']}': " . $post_id->get_error_message() . "\n";
    } else {
        echo "Successfully seeded menu '{$menu['title']}' (ID: $post_id)\n";
    }
}
