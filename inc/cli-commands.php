<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class EKA_CLI {
    /**
     * Migrate Alexandrinos Tachydromos from legacy DB
     *
     * @subcommand migrate-tachydromos
     */
    public function migrate_tachydromos() {
        $json_file = get_template_directory() . '/ai-work/scopings/tachydromos-scoping.json';
        if (!file_exists($json_file)) {
            WP_CLI::error("Scoping file not found at: $json_file");
        }

        $items = json_decode(file_get_contents($json_file), true);
        if (!$items || !is_array($items)) {
            WP_CLI::error("Invalid JSON scoping data.");
        }

        WP_CLI::line("Loaded " . count($items) . " items from scoping JSON.");

        $months = [
            'Ιανουάριος' => '01', 'Ιανουαρίου' => '01',
            'Φεβρουάριος' => '02', 'Φεβρουαρίου' => '02',
            'Μάρτιος' => '03', 'Μαρτίου' => '03',
            'Απρίλιος' => '04', 'Απριλίου' => '04', 'ΑΠΡΛΙΟΣ' => '04',
            'Μάιος' => '05', 'Μαΐου' => '05',
            'Ιούνιος' => '06', 'Ιουνίου' => '06',
            'Ιούλιος' => '07', 'Ιουλίου' => '07',
            'Αύγουστος' => '08', 'Αυγούστου' => '08',
            'Σεπτέμβριος' => '09', 'Σεπτεμβρίου' => '09',
            'Οκτώβριος' => '10', 'Οκτωβρίου' => '10',
            'Νοέμβριος' => '11', 'Νοεμβρίου' => '11',
            'Δεκέμβριος' => '12', 'Δεκεμβρίου' => '12',
        ];

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        global $wpdb;

        foreach ($items as $item) {
            $pdf_url = $item['pdf_url'] ?? null;
            $img_url = $item['img_url'] ?? null;
            $raw_title = $item['extracted_title'] ?? null;

            if (!$pdf_url && !$raw_title) {
                continue; // Skip banner header
            }

            // Clean up title
            $title = $raw_title ? trim(strip_tags(html_entity_decode($raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) : '';
            $title = preg_replace('/\s+/', ' ', $title);

            if (!$title && $pdf_url) {
                $title = wp_basename($pdf_url, '.pdf');
            }

            $pdf_filename = $pdf_url ? wp_basename($pdf_url) : ($img_url ? wp_basename($img_url) : 'item-' . time());

            // Check idempotency
            $existing = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_eka_pdf_filename' AND meta_value=%s", $pdf_filename));
            if ($existing) {
                WP_CLI::line("Skipping existing: $pdf_filename");
                continue;
            }

            // Date mapping
            $year = date('Y');
            if (preg_match('/(20\d{2})/', $title . ' ' . $pdf_url, $m)) {
                $year = $m[1];
            }
            $month_num = '01';
            foreach ($months as $m_name => $m_num) {
                if (mb_stripos($title, $m_name) !== false) {
                    $month_num = $m_num;
                    break;
                }
            }
            $post_date = sprintf('%04d-%02d-01 00:00:00', $year, $month_num);

            $pdf_attachment_id = 0;
            $pdf_attachment_url = $pdf_url;

            if ($pdf_url && strtolower(pathinfo($pdf_url, PATHINFO_EXTENSION)) === 'pdf') {
                // Check if attachment already exists in DB
                $existing_att = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s", '%' . $pdf_filename));
                if ($existing_att) {
                    $pdf_attachment_id = $existing_att;
                    $pdf_attachment_url = wp_get_attachment_url($pdf_attachment_id);
                } else {
                    WP_CLI::line("Downloading PDF: $pdf_url");
                    $tmp_pdf = download_url($pdf_url);
                    if (!is_wp_error($tmp_pdf)) {
                        $pdf_file_array = ['name' => $pdf_filename, 'tmp_name' => $tmp_pdf];
                        $pdf_attachment_id = media_handle_sideload($pdf_file_array, 0);
                        if (!is_wp_error($pdf_attachment_id)) {
                            $pdf_attachment_url = wp_get_attachment_url($pdf_attachment_id);
                        } else {
                            @unlink($tmp_pdf);
                            $pdf_attachment_id = 0;
                        }
                    }
                }
            }

            // Build core/file block or image block
            if ($pdf_attachment_id && $pdf_attachment_url) {
                $block_content = sprintf(
                    '<!-- wp:file {"id":%d,"href":"%s","displayPreview":true} -->
<div class="wp-block-file"><object class="wp-block-file__embed" data="%s" type="application/pdf" style="width:100%%;height:600px" aria-label="Embed of %s"></object><a href="%s">%s</a><a href="%s" class="wp-block-file__button wp-element-button" download aria-label="Λήψη %s">Λήψη</a></div>
<!-- /wp:file -->',
                    $pdf_attachment_id, esc_url($pdf_attachment_url), esc_url($pdf_attachment_url),
                    esc_attr($title), esc_url($pdf_attachment_url), esc_html($title),
                    esc_url($pdf_attachment_url), esc_attr($title)
                );
            } else {
                $block_content = sprintf(
                    '<!-- wp:paragraph --><p><a href="%s" target="_blank">%s</a></p><!-- /wp:paragraph -->',
                    esc_url($pdf_url ?: $img_url), esc_html($title)
                );
            }

            // AST parse verification
            $parsed_blocks = parse_blocks($block_content);
            if (empty($parsed_blocks)) {
                WP_CLI::warning("AST serialization failed for $title");
            }

            $post_id = wp_insert_post([
                'post_title' => $title ?: 'Tachydromos',
                'post_content' => $block_content,
                'post_status' => 'publish',
                'post_type' => 'alx_tachydromos',
                'post_date' => $post_date,
            ]);

            if (is_wp_error($post_id)) {
                WP_CLI::warning("Failed to insert post: $title");
                continue;
            }

            update_post_meta($post_id, '_eka_pdf_filename', $pdf_filename);
            if ($pdf_attachment_id) {
                update_post_meta($post_id, '_eka_pdf_attachment_id', $pdf_attachment_id);
            }

            // Reassign or download featured image
            if ($img_url) {
                $img_filename = wp_basename($img_url);
                $img_att_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s", '%' . $img_filename));
                if ($img_att_id) {
                    set_post_thumbnail($post_id, $img_att_id);
                } else {
                    $tmp_img = download_url($img_url);
                    if (!is_wp_error($tmp_img)) {
                        $img_file_array = ['name' => $img_filename, 'tmp_name' => $tmp_img];
                        $sideload_img_id = media_handle_sideload($img_file_array, $post_id);
                        if (!is_wp_error($sideload_img_id)) {
                            set_post_thumbnail($post_id, $sideload_img_id);
                        } else {
                            @unlink($tmp_img);
                        }
                    }
                }
            }

            WP_CLI::success("Migrated Tachydromos: $title ($post_date)");
        }

        WP_CLI::success('Tachydromos migration complete.');
    }

    /**
     * Migrate Board Members from legacy DB
     *
     * @subcommand migrate-board
     */
    public function migrate_board() {
        WP_CLI::line('Connecting to legacy DB...');
        $legacy_db = new wpdb('root', '0024', 'db207080_eka', 'localhost');
        if ($legacy_db->error) {
            WP_CLI::error('Could not connect to db207080_eka.');
        }

        // Fetch testimonials
        $testimonials = $legacy_db->get_results("SELECT ID, post_title, post_content, menu_order FROM wp_posts WHERE post_type='testimonial' AND post_status='publish'");

        // Fetch post languages
        $languages = $legacy_db->get_results("
            SELECT tr.object_id, t.slug as language
            FROM wp_term_relationships tr
            JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN wp_terms t ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'language'
        ");
        $post_languages = [];
        foreach ($languages as $l) {
            $post_languages[$l->object_id] = $l->language;
        }

        // Fetch translation groups
        $translations = $legacy_db->get_results("
            SELECT t.description as serialized_group
            FROM wp_term_taxonomy tt
            JOIN wp_terms t ON tt.term_id = t.term_id
            WHERE tt.taxonomy = 'post_translations'
        ");
        
        $groups = [];
        foreach ($translations as $t) {
            $group = unserialize($t->serialized_group);
            if (is_array($group)) {
                $groups[] = $group;
            }
        }

        // Fetch legacy thumbnails
        $thumbnails = $legacy_db->get_results("
            SELECT post_id, meta_value as thumbnail_id
            FROM wp_postmeta
            WHERE meta_key='_thumbnail_id'
        ");
        $legacy_thumbs = [];
        foreach ($thumbnails as $t) {
            $legacy_thumbs[$t->post_id] = $t->thumbnail_id;
        }

        global $wpdb;

        $migrated_map = []; // legacy ID => new ID
        $thumbnail_map = []; // filename => new attachment ID

        foreach (['el', 'en', 'ar'] as $lang) {
            foreach ($testimonials as $t) {
                $post_lang = isset($post_languages[$t->ID]) ? $post_languages[$t->ID] : 'el'; // fallback to el
                if ($post_lang !== $lang) continue;

                // Check idempotency
                $existing = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_legacy_testimonial_id' AND meta_value=%d", $t->ID));
                if ($existing) {
                    $migrated_map[$t->ID] = $existing;
                    WP_CLI::line("Skipping existing: {$t->post_title} ($lang)");
                    continue;
                }

                // Strip WPBakery shortcodes
                $clean_content = preg_replace('/\[\/?vc_[^\]]+\]/', '', $t->post_content);
                // Also parse standard Gutenberg blocks for raw text if we want, but legacy was WPBakery
                $clean_content = trim($clean_content);

                // Insert post
                $post_id = wp_insert_post([
                    'post_title' => $t->post_title,
                    'post_content' => $clean_content, // Keeping raw text + basic HTML
                    'post_status' => 'publish',
                    'post_type' => 'board_member',
                    'menu_order' => $t->menu_order,
                ]);

                if (is_wp_error($post_id)) {
                    WP_CLI::warning("Failed to insert: {$t->post_title}");
                    continue;
                }

                $migrated_map[$t->ID] = $post_id;
                update_post_meta($post_id, '_legacy_testimonial_id', $t->ID);

                // Handle thumbnail
                $thumbnail_id = 0;
                if (isset($legacy_thumbs[$t->ID])) {
                    $legacy_thumb_id = $legacy_thumbs[$t->ID];
                    $legacy_attachment = $legacy_db->get_row($legacy_db->prepare("SELECT meta_value FROM wp_postmeta WHERE post_id=%d AND meta_key='_wp_attached_file'", $legacy_thumb_id));
                    
                    if ($legacy_attachment) {
                        $filename = wp_basename($legacy_attachment->meta_value);
                        
                        if (isset($thumbnail_map[$filename])) {
                            $thumbnail_id = $thumbnail_map[$filename];
                        } else {
                            $existing_attachment = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_eka_original_filename' AND meta_value=%s", $filename));
                            
                            if ($existing_attachment) {
                                $thumbnail_id = $existing_attachment;
                                $thumbnail_map[$filename] = $thumbnail_id;
                            } else {
                                // Download and process
                                $legacy_url = "https://ekalexandria.org/wp-content/uploads/" . $legacy_attachment->meta_value;
                                $upload_dir = wp_upload_dir();
                                $local_filename = 'board-' . md5($legacy_url) . '-' . $filename;
                                $local_path = $upload_dir['path'] . '/' . $local_filename;
                                
                                WP_CLI::line("Downloading board image: $legacy_url");
                                $img_content = @file_get_contents($legacy_url);
                                
                                if ($img_content) {
                                    file_put_contents($local_path, $img_content);
                                    
                                    // ImageMagick resize to 800x800 square
                                    $command = sprintf("convert %s -resize 800x800^ -gravity center -extent 800x800 %s", escapeshellarg($local_path), escapeshellarg($local_path));
                                    exec($command);
                                    
                                    $filetype = wp_check_filetype($local_filename, null);
                                    $attachment = array(
                                        'post_mime_type' => $filetype['type'],
                                        'post_title'     => sanitize_file_name($filename),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit'
                                    );
                                    
                                    $thumbnail_id = wp_insert_attachment($attachment, $local_path, $post_id);
                                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                                    $attach_data = wp_generate_attachment_metadata($thumbnail_id, $local_path);
                                    wp_update_attachment_metadata($thumbnail_id, $attach_data);
                                    update_post_meta($thumbnail_id, '_eka_original_filename', $filename);
                                    
                                    $thumbnail_map[$filename] = $thumbnail_id;
                                } else {
                                    WP_CLI::warning("Failed to download: $legacy_url");
                                }
                            }
                        }
                    }
                }
                
                if ($thumbnail_id) {
                    set_post_thumbnail($post_id, $thumbnail_id);
                }

                // Set Polylang language
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                }

                WP_CLI::success("Migrated ($lang): {$t->post_title}");
            }
        }

        // Link translations
        if (function_exists('pll_save_post_translations')) {
            foreach ($groups as $group) {
                // $group is ['el' => legacy_id, 'en' => legacy_id, 'ar' => legacy_id]
                $new_group = [];
                foreach ($group as $lang => $legacy_id) {
                    if (isset($migrated_map[$legacy_id])) {
                        $new_group[$lang] = $migrated_map[$legacy_id];
                    }
                }
                if (count($new_group) > 1) {
                    pll_save_post_translations($new_group);
                    WP_CLI::line("Linked translations: " . implode(', ', $new_group));
                }
            }
        }

        WP_CLI::success('Board Members migration complete.');
    }
}

WP_CLI::add_command( 'eka', 'EKA_CLI' );
