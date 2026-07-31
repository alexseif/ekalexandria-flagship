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

    register_post_meta('board_member', '_eka_legacy_id', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'integer',
        'auth_callback' => function() { return current_user_can('edit_posts'); }
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

// Auto-set featured image from PDF on save via ImageMagick
add_action('save_post_alx_tachydromos', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $pdf_id = get_post_meta($post_id, '_eka_pdf_attachment_id', true);
    if ($pdf_id && !has_post_thumbnail($post_id)) {
        $pdf_path = get_attached_file($pdf_id);
        if ($pdf_path && file_exists($pdf_path)) {
            $upload_dir = wp_upload_dir();
            $thumb_filename = 'tachydromos-thumb-' . $post_id . '-' . time() . '.jpg';
            $thumb_path = $upload_dir['path'] . '/' . $thumb_filename;

            $cmd = sprintf("convert -density 150 %s[0] -quality 90 %s", escapeshellarg($pdf_path), escapeshellarg($thumb_path));
            exec($cmd, $output, $return_var);

            if ($return_var === 0 && file_exists($thumb_path)) {
                $filetype = wp_check_filetype($thumb_filename, null);
                $attachment = [
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => sanitize_file_name($thumb_filename),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ];
                $attach_id = wp_insert_attachment($attachment, $thumb_path, $post_id);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $thumb_path);
                wp_update_attachment_metadata($attach_id, $attach_data);
                set_post_thumbnail($post_id, $attach_id);
            }
        }
    }
}, 20);

// Register Block Style for Gallery (Legacy Slider)
add_action('init', function() {
    register_block_style('core/gallery', [
        'name'         => 'legacy-slider',
        'label'        => __('Legacy Slider', 'ekalexandria-flagship'),
        'is_default'   => false,
    ]);
});

// Re-engineered Mailchimp Newsletter Shortcode
add_shortcode('eka_mailchimp_form', function($atts) {
    ob_start(); ?>
    <div class="eka-mailchimp-block">
        <h3><?php _e('Subscribe to Our Newsletter', 'ekalexandria-flagship'); ?></h3>
        <p><?php _e('Get the latest updates and announcements from the Greek Community of Alexandria.', 'ekalexandria-flagship'); ?></p>
        <form class="eka-mailchimp-form" action="" method="post">
            <?php wp_nonce_field('eka_mailchimp_subscribe', 'eka_mc_nonce'); ?>
            <input type="email" name="eka_subscriber_email" placeholder="<?php esc_attr_e('Your email address...', 'ekalexandria-flagship'); ?>" required />
            <button type="submit" name="eka_mc_submit"><?php _e('Subscribe', 'ekalexandria-flagship'); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
});

