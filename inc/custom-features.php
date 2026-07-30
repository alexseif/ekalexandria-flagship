<?php
/**
 * Custom Post Types and Admin Features
 */

// Admin login panel branded
function ekalexandria_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/assets/images/logo.png);
            width: 100%;
            background-size: contain;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
        body.login {
            background-color: #f5f5f5;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'ekalexandria_login_logo' );

// Greek admin dashboard labels for default posts
function ekalexandria_change_post_menu_label() {
    global $menu;
    global $submenu;
    if(isset($menu[5])) {
        $menu[5][0] = 'Νέα';
    }
    if(isset($submenu['edit.php'])) {
        $submenu['edit.php'][5][0] = 'Όλα τα Νέα';
        $submenu['edit.php'][10][0] = 'Προσθήκη Νέου';
    }
}
add_action( 'admin_menu', 'ekalexandria_change_post_menu_label' );

// Register Polylang Switcher Shortcode for FSE Header
function ekalexandria_polylang_shortcode() {
    if ( function_exists('pll_the_languages') ) {
        return '<ul class="polylang-switcher" style="display:flex; list-style:none; gap:10px; margin:0; padding:0; align-items:center;">' . pll_the_languages( array( 'echo' => 0, 'hide_current' => 0 ) ) . '</ul>';
    }
    return '';
}
add_shortcode( 'polylang_langswitcher', 'ekalexandria_polylang_shortcode' );

// Register Alexandrinos Tachydromos CPT
add_action('init', function() {
    register_post_type('alx_tachydromos', [
        'labels' => [
            'name' => 'Alexandrinos Tachydromos',
            'singular_name' => 'Tachydromos',
            'menu_name' => 'Tachydromos',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Tachydromos',
            'edit_item' => 'Edit',
            'new_item' => 'New',
            'view_item' => 'View',
            'search_items' => 'Search',
            'not_found' => 'No items found',
        ],
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'rewrite' => ['slug' => 'αλεξανδρινός-ταχυδρόμος', 'with_front' => false],
        'menu_icon' => 'dashicons-media-document',
    ]);
});

// Register Gutenberg Meta Fields for Tachydromos PDF
add_action('init', function() {
    register_post_meta('alx_tachydromos', '_eka_pdf_attachment_id', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'integer',
        'auth_callback' => function() { return current_user_can('edit_posts'); }
    ]);
    register_post_meta('alx_tachydromos', '_eka_pdf_filename', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'auth_callback' => function() { return current_user_can('edit_posts'); }
    ]);
});

// Register Board Member CPT
add_action('init', function() {
    register_post_type('board_member', [
        'labels' => [
            'name' => 'Board Members',
            'singular_name' => 'Board Member',
            'menu_name' => 'Board Members',
        ],
        'public' => true,
        'publicly_queryable' => false,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
        'menu_icon' => 'dashicons-groups',
    ]);
});

// Exclude Tachydromos and include Board Member for Polylang
add_filter('pll_get_post_types', function($post_types, $is_settings) {
    if (isset($post_types['alx_tachydromos'])) {
        unset($post_types['alx_tachydromos']);
    }
    $post_types['board_member'] = 'board_member';
    return $post_types;
}, 10, 2);

// Auto-set featured image from PDF on save
add_action('save_post_alx_tachydromos', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $pdf_id = get_post_meta($post_id, '_eka_pdf_attachment_id', true);
    if ($pdf_id && !has_post_thumbnail($post_id)) {
        // Migration script or future editor will generate thumbnail and set it
        // If image generation is needed here, it would use ImageMagick on the PDF
    }
}, 20);
