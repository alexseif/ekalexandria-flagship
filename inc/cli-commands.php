<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class EKA_CLI {

    private function get_logger($log_filename) {
        $log_dir = get_template_directory() . '/ai-work/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/' . $log_filename;
        return function($message, $type = 'INFO', $reasoning = '') use ($log_file) {
            $timestamp = date('Y-m-d H:i:s');
            $log_entry = sprintf("[%s] [%s] %s %s\n", $timestamp, $type, $message, $reasoning ? "Reasoning: $reasoning" : '');
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            if ($type === 'ERROR') {
                WP_CLI::error($message);
            } elseif ($type === 'WARNING') {
                WP_CLI::warning($message);
            } else {
                WP_CLI::line($message);
            }
        };
    }

    /**
     * Migrate Alexandrinos Tachydromos from legacy DB
     *
     * @subcommand migrate-tachydromos
     */
    public function migrate_tachydromos() {
        $log = $this->get_logger('tachydromos-migration.log');
        $log("Starting Alexandrinos Tachydromos migration...", "INFO", "Initializing scoping data and database connectivity.");

        $json_file = get_template_directory() . '/ai-work/scopings/tachydromos-scoping.json';
        if (!file_exists($json_file)) {
            $log("Scoping file not found at: $json_file", "ERROR", "Migration cannot proceed without valid scoping dataset.");
        }

        $items = json_decode(file_get_contents($json_file), true);
        if (!$items || !is_array($items)) {
            $log("Invalid JSON scoping data.", "ERROR", "JSON parsing failed for tachydromos-scoping.json.");
        }

        $log("Loaded " . count($items) . " items from scoping JSON.");

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

        $month_title_casing = [
            '01' => 'Ιανουάριος',
            '02' => 'Φεβρουάριος',
            '03' => 'Μάρτιος',
            '04' => 'Απρίλιος',
            '05' => 'Μάιος',
            '06' => 'Ιούνιος',
            '07' => 'Ιούλιος',
            '08' => 'Αύγουστος',
            '09' => 'Σεπτέμβριος',
            '10' => 'Οκτώβριος',
            '11' => 'Νοέμβριος',
            '12' => 'Δεκέμβριος',
        ];

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        global $wpdb;

        foreach ($items as $item) {
            $pdf_url = $item['pdf_url'] ?? null;
            $img_url = $item['img_url'] ?? null;
            $unscaled_img_url = $item['unscaled_img_url'] ?? null;
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
                $log("Skipping existing item: $pdf_filename", "INFO", "Idempotency check confirmed post_id $existing already exists.");
                continue;
            }

            // Date mapping & normalized Greek Month title casing
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
            
            // Normalize title casing if it matches a month
            if (isset($month_title_casing[$month_num]) && preg_match('/^[A-Z\x{0370}-\x{03FF}\s\d]+$/u', $title)) {
                $title = $month_title_casing[$month_num] . ' ' . $year;
            }

            $pdf_attachment_id = 0;
            $pdf_attachment_url = $pdf_url;

            if ($pdf_url && strtolower(pathinfo($pdf_url, PATHINFO_EXTENSION)) === 'pdf') {
                $existing_att = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s", '%' . $pdf_filename));
                if ($existing_att) {
                    $pdf_attachment_id = $existing_att;
                    $pdf_attachment_url = wp_get_attachment_url($pdf_attachment_id);
                    $log("Reassigned existing PDF attachment ID $pdf_attachment_id for $pdf_filename", "INFO", "Reusing media library asset without re-downloading.");
                } else {
                    $log("Downloading PDF: $pdf_url", "INFO", "File not found in local media library.");
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

            // Build core/file block or fallback link
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

            $parsed_blocks = parse_blocks($block_content);
            if (empty($parsed_blocks)) {
                $log("AST serialization warning for $title", "WARNING", "Block structure failed default parser validation.");
            }

            $post_id = wp_insert_post([
                'post_title' => $title ?: 'Tachydromos',
                'post_content' => $block_content,
                'post_status' => 'publish',
                'post_type' => 'alx_tachydromos',
                'post_date' => $post_date,
            ]);

            if (is_wp_error($post_id)) {
                $log("Failed to insert post: $title", "WARNING", $post_id->get_error_message());
                continue;
            }

            update_post_meta($post_id, '_eka_pdf_filename', $pdf_filename);
            if ($pdf_attachment_id) {
                update_post_meta($post_id, '_eka_pdf_attachment_id', $pdf_attachment_id);
            }

            // Reassign unscaled featured image without re-uploading or scaling
            $target_img = $unscaled_img_url ?: $img_url;
            if ($target_img) {
                $img_filename = wp_basename($target_img);
                $clean_filename = preg_replace('/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $img_filename);
                $img_att_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s", '%' . $clean_filename));
                if ($img_att_id) {
                    set_post_thumbnail($post_id, $img_att_id);
                    $log("Assigned existing unscaled attachment ID $img_att_id to post $post_id", "INFO", "Matched unscaled filename $clean_filename.");
                } else {
                    $tmp_img = download_url($target_img);
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

            $log("Successfully migrated Tachydromos: $title ($post_date)", "INFO", "Post ID: $post_id");
        }

        $log("Alexandrinos Tachydromos migration finished successfully.", "INFO");
    }

    /**
     * Migrate Board Members from legacy DB
     *
     * @subcommand migrate-board
     */
    public function migrate_board() {
        $log = $this->get_logger('board-migration.log');
        $log("Starting Board Members migration...", "INFO", "Connecting to legacy DB and scoping translation maps.");

        $scoping_file = get_template_directory() . '/ai-work/scopings/board-scoping.json';
        $scoping_data = file_exists($scoping_file) ? json_decode(file_get_contents($scoping_file), true) : null;

        WP_CLI::line('Connecting to legacy DB...');
        $legacy_db = new wpdb('root', '0024', 'db207080_eka', 'localhost');
        if ($legacy_db->error) {
            $log("Could not connect to legacy DB db207080_eka.", "ERROR", "Database credentials or MySQL service unavailable.");
        }

        $testimonials = $legacy_db->get_results("SELECT ID, post_title, post_content, menu_order FROM wp_posts WHERE post_type='testimonial' AND post_status='publish'");

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

        $groups = $scoping_data['translation_groups'] ?? [];
        if (empty($groups)) {
            $translations = $legacy_db->get_results("
                SELECT t.description as serialized_group
                FROM wp_term_taxonomy tt
                JOIN wp_terms t ON tt.term_id = t.term_id
                WHERE tt.taxonomy = 'post_translations'
            ");
            foreach ($translations as $t) {
                $group = unserialize($t->serialized_group);
                if (is_array($group)) {
                    $groups[] = $group;
                }
            }
        }

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
        $migrated_map = [];
        $thumbnail_map = [];

        foreach (['el', 'en', 'ar'] as $lang) {
            foreach ($testimonials as $t) {
                $post_lang = isset($post_languages[$t->ID]) ? $post_languages[$t->ID] : 'el';
                if ($post_lang !== $lang) continue;

                // Check idempotency
                $existing = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_legacy_testimonial_id' AND meta_value=%d", $t->ID));
                if ($existing) {
                    $migrated_map[$t->ID] = $existing;
                    $log("Skipping existing Board Member: {$t->post_title} ($lang)", "INFO", "Found legacy testimonial ID {$t->ID} mapped to post_id $existing.");
                    continue;
                }

                // Strip <img> tags and WPBakery shortcodes completely from post_content
                $clean_content = preg_replace('/<img[^>]*>/i', '', $t->post_content);
                $clean_content = preg_replace('/\[\/?vc_[^\]]+\]/', '', $clean_content);
                $clean_content = trim($clean_content);

                $post_id = wp_insert_post([
                    'post_title' => $t->post_title,
                    'post_content' => $clean_content,
                    'post_status' => 'publish',
                    'post_type' => 'board_member',
                    'menu_order' => $t->menu_order,
                ]);

                if (is_wp_error($post_id)) {
                    $log("Failed to insert board member: {$t->post_title}", "WARNING", $post_id->get_error_message());
                    continue;
                }

                $migrated_map[$t->ID] = $post_id;
                update_post_meta($post_id, '_legacy_testimonial_id', $t->ID);

                // Handle thumbnail: reassign unscaled existing attachment ID without re-uploading/cropping
                $thumbnail_id = 0;
                if (isset($legacy_thumbs[$t->ID])) {
                    $legacy_thumb_id = $legacy_thumbs[$t->ID];
                    $legacy_attachment = $legacy_db->get_row($legacy_db->prepare("SELECT meta_value FROM wp_postmeta WHERE post_id=%d AND meta_key='_wp_attached_file'", $legacy_thumb_id));
                    
                    if ($legacy_attachment) {
                        $filename = wp_basename($legacy_attachment->meta_value);
                        $unscaled_filename = preg_replace('/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $filename);
                        
                        $existing_attachment = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s", '%' . $unscaled_filename));
                        
                        if ($existing_attachment) {
                            $thumbnail_id = $existing_attachment;
                            $log("Reassigned unscaled attachment ID $thumbnail_id for board member {$t->post_title}", "INFO", "Matched attachment filename $unscaled_filename.");
                        } else {
                            $legacy_url = "https://ekalexandria.org/wp-content/uploads/" . $legacy_attachment->meta_value;
                            $upload_dir = wp_upload_dir();
                            $local_filename = 'board-' . md5($legacy_url) . '-' . $filename;
                            $local_path = $upload_dir['path'] . '/' . $local_filename;
                            
                            $img_content = @file_get_contents($legacy_url);
                            if ($img_content) {
                                file_put_contents($local_path, $img_content);
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
                            }
                        }
                    }
                }
                
                if ($thumbnail_id) {
                    set_post_thumbnail($post_id, $thumbnail_id);
                }

                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                }

                $log("Migrated Board Member ($lang): {$t->post_title}", "INFO", "Post ID: $post_id");
            }
        }

        // Link translations via Polylang pll_save_post_translations
        if (function_exists('pll_save_post_translations')) {
            foreach ($groups as $group) {
                $new_group = [];
                foreach ($group as $lang => $legacy_id) {
                    if (isset($migrated_map[$legacy_id])) {
                        $new_group[$lang] = $migrated_map[$legacy_id];
                    }
                }
                if (count($new_group) > 1) {
                    pll_save_post_translations($new_group);
                    $log("Linked Polylang post translations: " . json_encode($new_group), "INFO", "Linked " . count($new_group) . " translations.");
                }
            }
        }

        $log("Board Members migration finished successfully.", "INFO");
    }

    /**
     * Replace Legacy Sliders with Native Blocks
     *
     * @subcommand replace-sliders
     */
    public function replace_sliders() {
        $log = $this->get_logger('sliders-migration.log');
        $log("Starting Slider Replacement...", "INFO", "Scanning pages for [rev_slider] and [layerslider] shortcodes.");

        global $wpdb;

        $dynamic_pages = [13236, 17194, 17215, 17219, 8934, 16920, 16923];
        $query_loop_block = '<!-- wp:query {"queryId":1,"query":{"perPage":5,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query">
<!-- wp:post-template -->
<!-- wp:post-title {"isLink":true} /-->
<!-- wp:post-excerpt {"moreText":"Read more"} /-->
<!-- wp:post-date /-->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->';
        
        foreach ($dynamic_pages as $page_id) {
            $post = get_post($page_id);
            if ($post) {
                if (strpos($post->post_content, 'wp:query') === false) {
                    $new_content = preg_replace('/\[rev_slider[^\]]*\]/i', $query_loop_block, $post->post_content);
                    $new_content = preg_replace('/\[layerslider[^\]]*\]/i', $query_loop_block, $new_content);
                    if ($new_content !== $post->post_content) {
                        wp_update_post(['ID' => $page_id, 'post_content' => $new_content]);
                        $log("Replaced dynamic slider in page ID $page_id with core Query Loop.", "INFO");
                    }
                }
            }
        }

        $static_galleries = [
            7820 => [7821, 7822, 7823],
            17129 => [7821, 7822, 7823],
            17133 => [7821, 7822, 7823],
            7811 => [7813, 7814, 7815],
            17137 => [7813, 7814, 7815],
            17139 => [7813, 7814, 7815],
            3442 => [10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673],
            17023 => [10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673],
            17155 => [10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673],
            7756 => [7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942],
            17150 => [7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942],
            7390 => [10328],
            17018 => [10328],
            17020 => [10328]
        ];

        foreach ($static_galleries as $page_id => $media_ids) {
            $post = get_post($page_id);
            if ($post) {
                if (strpos($post->post_content, 'wp:gallery') === false) {
                    $gallery_block = '<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">';
                    foreach ($media_ids as $media_id) {
                        $img_url = wp_get_attachment_url($media_id) ?: '';
                        if ($img_url) {
                            $gallery_block .= sprintf(
                                '<!-- wp:image {"id":%d,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="%s" alt="" class="wp-image-%d"/></figure>
<!-- /wp:image -->',
                                $media_id, esc_url($img_url), $media_id
                            );
                        }
                    }
                    $gallery_block .= '</figure>
<!-- /wp:gallery -->';

                    $new_content = preg_replace('/\[rev_slider[^\]]*\]/i', $gallery_block, $post->post_content);
                    $new_content = preg_replace('/\[layerslider[^\]]*\]/i', $gallery_block, $new_content);
                    
                    if ($new_content === $post->post_content) {
                        $new_content .= "\n\n" . $gallery_block;
                    }

                    if ($new_content !== $post->post_content) {
                        wp_update_post(['ID' => $page_id, 'post_content' => $new_content]);
                        $log("Replaced static slider in page ID $page_id with core Gallery block.", "INFO");
                    }
                }
            }
        }
        
        $log("Slider replacement finished successfully.", "INFO");
    }

    /**
     * Remediate Shortcodes and Sub-navigation
     *
     * @subcommand remediate-shortcodes
     */
    public function remediate_shortcodes() {
        $log = $this->get_logger('remediate-shortcodes.log');
        $log("Starting Shortcode & Sub-navigation Remediation...", "INFO");

        global $wpdb;

        $posts = get_posts(['post_type' => 'page', 'posts_per_page' => -1]);
        
        foreach ($posts as $post) {
            $content = $post->post_content;

            if (strpos($content, '[testimonials') !== false) {
                $board_query = '<!-- wp:query {"queryId":2,"query":{"perPage":50,"pages":0,"offset":0,"postType":"board_member","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query">
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":false} /-->
<!-- wp:post-title {"level":3} /-->
<!-- wp:post-content /-->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->';
                $content = preg_replace('/<!-- wp:shortcode -->\s*\[testimonials[^\]]*\]\s*<!-- \/wp:shortcode -->/is', $board_query, $content);
                $content = preg_replace('/\[testimonials[^\]]*\]/is', $board_query, $content);
            }

            if (strpos($content, '[vc_posts_grid') !== false) {
                if (preg_match('/by_id:([0-9,]+)/', $content, $matches)) {
                    $ids = array_map('intval', explode(',', $matches[1]));
                    $include_json = json_encode($ids);
                    
                    $subnav_query = '<!-- wp:query {"queryId":3,"query":{"perPage":50,"pages":0,"offset":0,"postType":"page","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false,"include":' . $include_json . '}} -->
<div class="wp-block-query">
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true} /-->
<!-- wp:post-title {"isLink":true,"level":3} /-->
<!-- wp:post-excerpt /-->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->';
                    
                    $content = preg_replace('/\[vc_row\]\[vc_column[^\]]*\]\[vc_posts_grid[^\]]*\]\[\/vc_column\]\[\/vc_row\]/is', $subnav_query, $content);
                    $content = preg_replace('/\[vc_posts_grid[^\]]*\]/is', $subnav_query, $content);
                }
            }
            
            $content = preg_replace('/\[\/?vc_[^\]]*\]/', '', $content);
            $content = preg_replace('/\[\/?mfn_[^\]]*\]/', '', $content);

            if ($content !== $post->post_content) {
                wp_update_post(['ID' => $post->ID, 'post_content' => trim($content)]);
                $log("Remediated shortcodes on page ID {$post->ID}", "INFO");
            }
        }

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
                    $log("Injected Navigation Sidebar for page ID $page_id with menu $menu_id", "INFO");
                }
            }
        }
        
        $log("Shortcode & sub-navigation remediation finished successfully.", "INFO");
    }

    /**
     * Standalone Production Cutover Execution
     *
     * @subcommand production-cutover
     */
    public function production_cutover() {
        $log = $this->get_logger('cutover.log');
        $log("Starting Production Cutover...", "INFO", "Initializing autonomous cutover workflow.");

        // 1. Activate Flagship Theme
        $log("Activating ekalexandria-flagship theme...", "INFO");
        switch_theme('ekalexandria-flagship');

        // 2. Run Cleanup Plugins Script if available
        $cleanup_script = get_template_directory() . '/bin/cleanup-plugins.sh';
        if (file_exists($cleanup_script)) {
            $log("Executing legacy plugin cleanup script...", "INFO");
            exec("bash " . escapeshellarg($cleanup_script) . " >> " . escapeshellarg(get_template_directory() . '/ai-work/logs/cutover.log') . " 2>&1");
        }

        // 3. Execute Migration Workflows
        $log("Running Alexandrinos Tachydromos migration...", "INFO");
        $this->migrate_tachydromos();

        $log("Running Board Members migration...", "INFO");
        $this->migrate_board();

        $log("Running Slider replacements...", "INFO");
        $this->replace_sliders();

        $log("Running Shortcode remediation...", "INFO");
        $this->remediate_shortcodes();

        // 4. Flush Rewrite Rules
        $log("Flushing permalinks and rewrite rules...", "INFO");
        flush_rewrite_rules();

        $log("Production cutover completed successfully!", "INFO", "All data, templates, and plugins finalized.");
    }
}

WP_CLI::add_command( 'eka', 'EKA_CLI' );

