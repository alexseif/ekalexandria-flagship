<?php
/**
 * bin/inject-sidebar-menus.php
 * Executes sidebar navigation menu injection for specified parent/sub-pages.
 * TODO: Sidebar menu assignment for parent/sub-pages is specified here, but implementation logic is pending in the next phase.
 */

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
