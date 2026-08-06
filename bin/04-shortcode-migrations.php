<?php

/**
 * bin/04-shortcode-migrations.php
 * Stage 04: Shortcode Remediation Engine
 * Target: backstage_eka DB via WP-CLI / MySQL
 * Transforms WPBakery structural shortcodes (vc_row, vc_column, vc_single_image),
 * [caption] shortcodes, and residual shortcodes into native Gutenberg block structures.
 */

require_once __DIR__ . '/migration-helpers.php';

$log_file = dirname(__DIR__) . '/ai-work/logs/04-shortcode-migrations.log';
eka_init_log_file($log_file);

function eka_shortcode_log($msg, $level = 'INFO')
{
    static $log_file = null;
    if ($log_file === null) {
        $log_file = dirname(__DIR__) . '/ai-work/logs/04-shortcode-migrations.log';
    }
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[{$timestamp}] [{$level}] {$msg}\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
    if (class_exists('WP_CLI')) {
        if ($level === 'ERROR') {
            WP_CLI::warning("ERROR: " . $msg);
        } elseif ($level === 'WARNING') {
            WP_CLI::warning($msg);
        } else {
            WP_CLI::line($msg);
        }
    } else {
        echo $formatted;
    }
}

/**
 * Loads slider scoping JSON file into an in-memory map keyed by page_id.
 *
 * @param string|null $scoping_file_path Path to rev-sliders-scoping.json
 * @return array Map of page_id => scoping_data_array
 */
function eka_load_slider_scoping($scoping_file_path = null)
{
    if ($scoping_file_path === null) {
        $scoping_file_path = dirname(__DIR__) . '/ai-work/scopings/rev-sliders-scoping.json';
    }

    if (!file_exists($scoping_file_path)) {
        eka_shortcode_log("Scoping file not found: {$scoping_file_path}", "WARNING");
        return [];
    }

    $json = file_get_contents($scoping_file_path);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        eka_shortcode_log("Failed to parse scoping JSON: {$scoping_file_path}", "WARNING");
        return [];
    }

    $map = [];
    foreach ($data as $item) {
        if (isset($item['page_id'])) {
            $map[(int)$item['page_id']] = $item;
        }
    }

    return $map;
}

/**
 * Resolves media images (IDs and URLs) for a given post ID using scoping data and MySQL fallback.
 *
 * @param int $post_id
 * @param array $scoping_map
 * @param mysqli|null $mysqli
 * @return array Array of ['id' => int, 'url' => string]
 */
function eka_resolve_post_images($post_id, $scoping_map = [], $mysqli = null)
{
    $post_id = (int)$post_id;
    $images = [];
    $seen_ids = [];

    // 1. Check scoping map for post_id
    if (isset($scoping_map[$post_id])) {
        $scoped = $scoping_map[$post_id];

        // 1a. attached_media
        if (!empty($scoped['attached_media']) && is_array($scoped['attached_media'])) {
            foreach ($scoped['attached_media'] as $media) {
                $id = isset($media['attachment_id']) ? (int)$media['attachment_id'] : 0;
                $url = isset($media['url']) ? $media['url'] : '';
                if ($id > 0 && !isset($seen_ids[$id])) {
                    $seen_ids[$id] = true;
                    $images[] = ['id' => $id, 'url' => $url];
                }
            }
        }

        // 1b. embedded_images
        if (!empty($scoped['embedded_images']) && is_array($scoped['embedded_images'])) {
            foreach ($scoped['embedded_images'] as $media) {
                $id = isset($media['db_attachment_id']) ? (int)$media['db_attachment_id'] : 0;
                $url = isset($media['src_url']) ? $media['src_url'] : '';
                if ($id > 0 && !isset($seen_ids[$id])) {
                    $seen_ids[$id] = true;
                    $images[] = ['id' => $id, 'url' => $url];
                }
            }
        }

        // 1c. gallery_image_ids
        if (!empty($scoped['gallery_image_ids']) && is_array($scoped['gallery_image_ids'])) {
            foreach ($scoped['gallery_image_ids'] as $gid) {
                $id = (int)$gid;
                if ($id > 0 && !isset($seen_ids[$id])) {
                    $seen_ids[$id] = true;
                    $images[] = ['id' => $id, 'url' => ''];
                }
            }
        }
    }

    // 2. MySQL fallback query if no images found in scoping
    if (empty($images) && $mysqli instanceof mysqli) {
        $stmt = $mysqli->prepare("SELECT ID, guid FROM wp_posts WHERE post_parent = ? AND post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
        if ($stmt) {
            $stmt->bind_param("i", $post_id);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $id = (int)$row['ID'];
                    $url = $row['guid'];
                    if ($id > 0 && !isset($seen_ids[$id])) {
                        $seen_ids[$id] = true;
                        $images[] = ['id' => $id, 'url' => $url];
                    }
                }
            }
            $stmt->close();
        }
    }

    // 3. Resolve missing URLs or IDs via MySQL if needed
    if (!empty($images) && $mysqli instanceof mysqli) {
        foreach ($images as &$img) {
            if ($img['id'] > 0 && empty($img['url'])) {
                $stmt = $mysqli->prepare("SELECT guid FROM wp_posts WHERE ID = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $img['id']);
                    if ($stmt->execute()) {
                        $res = $stmt->get_result();
                        if ($row = $res->fetch_assoc()) {
                            $img['url'] = $row['guid'];
                        }
                    }
                    $stmt->close();
                }
            }
        }
        unset($img);
    }

    return $images;
}

/**
 * Resolves media images by slider alias across translated pages in scoping index.
 *
 * @param string $alias
 * @param int $post_id
 * @param array $scoping_map
 * @param mysqli|null $mysqli
 * @return array Array of ['id' => int, 'url' => string]
 */
function eka_resolve_slider_images_by_alias($alias, $post_id, $scoping_map = [], $mysqli = null)
{
    $images = eka_resolve_post_images($post_id, $scoping_map, $mysqli);
    if (!empty($images)) {
        return $images;
    }

    if (!empty($alias) && is_array($scoping_map)) {
        foreach ($scoping_map as $pid => $scoped) {
            if (!empty($scoped['rev_sliders']) && is_array($scoped['rev_sliders'])) {
                foreach ($scoped['rev_sliders'] as $rs) {
                    if (isset($rs['alias']) && strcasecmp($rs['alias'], $alias) === 0) {
                        $found = eka_resolve_post_images($pid, $scoping_map, $mysqli);
                        if (!empty($found)) {
                            return $found;
                        }
                    }
                }
            }
        }
    }

    return [];
}

$is_direct_execution = !defined('EKA_TEST_MODE');

if ($is_direct_execution) {
    eka_shortcode_log("==========================================");
    eka_shortcode_log("Starting Stage 04: Shortcode Migrations - " . date('Y-m-d H:i:s'));
    eka_shortcode_log("==========================================");

    $db_config = eka_get_db_config();
    $mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
    if ($mysqli->connect_error) {
        eka_shortcode_log("Database connection failed: " . $mysqli->connect_error, "ERROR");
        die("Connection failed: " . $mysqli->connect_error . "\n");
    }
    $mysqli->set_charset("utf8mb4");
}


// ----------------------------------------------------------------------
// Shortcode Transformation Functions
// ----------------------------------------------------------------------

function parse_fraction_width($width_str)
{
    $width_str = trim($width_str);
    if (empty($width_str)) {
        return '100%';
    }
    if (strpos($width_str, '/') !== false) {
        $parts = explode('/', $width_str);
        $num = (float)$parts[0];
        $den = (float)$parts[1];
        if ($den > 0) {
            $pct = round(($num / $den) * 100, 2);
            return $pct . '%';
        }
    }
    if (is_numeric(rtrim($width_str, '%'))) {
        return rtrim($width_str, '%') . '%';
    }
    return '100%';
}

/**
 * Helper to construct Gutenberg wp:gallery block with nested wp:image blocks.
 */
function eka_build_gutenberg_gallery_block($images, $extra_class = 'rev-slider-replaced', $fallback_title = 'Slider')
{
    $valid_images = [];
    foreach ($images as $img) {
        if (!empty($img['id']) || !empty($img['url'])) {
            $valid_images[] = $img;
        }
    }

    if (empty($valid_images)) {
        return '<!-- wp:gallery {"className":"' . $extra_class . '"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped ' . $extra_class . '"><!-- wp:paragraph --><p>' . htmlspecialchars($fallback_title, ENT_QUOTES, 'UTF-8') . '</p><!-- /wp:paragraph --></figure><!-- /wp:gallery -->';
    }

    $image_ids = [];
    $inner_blocks_html = '';

    foreach ($valid_images as $img) {
        $id = (int)$img['id'];
        $url = htmlspecialchars($img['url'], ENT_QUOTES, 'UTF-8');
        if ($id > 0) {
            $image_ids[] = $id;
        }

        $id_attr_json = $id > 0 ? '"id":' . $id . ',' : '';
        $id_class = $id > 0 ? ' wp-image-' . $id : '';

        $inner_blocks_html .= '<!-- wp:image {' . $id_attr_json . '"sizeSlug":"full","linkDestination":"none"} -->';
        $inner_blocks_html .= '<figure class="wp-block-image size-full"><img src="' . $url . '" alt="" class="' . trim($id_class) . '"/></figure>';
        $inner_blocks_html .= '<!-- /wp:image -->';
    }

    $gallery_attrs = [
        'columns' => 1,
        'ids' => $image_ids,
        'linkTo' => 'none',
        'sizeSlug' => 'full',
        'className' => $extra_class,
    ];
    $attrs_json = json_encode($gallery_attrs, JSON_UNESCAPED_SLASHES);

    $html = '<!-- wp:gallery ' . $attrs_json . ' -->';
    $html .= '<figure class="wp-block-gallery has-nested-images columns-1 is-cropped ' . $extra_class . '">';
    $html .= $inner_blocks_html;
    $html .= '</figure>';
    $html .= '<!-- /wp:gallery -->';

    return $html;
}

/**
 * Structural WPBakery (vc_row, vc_column, vc_single_image), caption, and slider transformations.
 */
function step_4a_transform_wpbakery_and_caption($content, $post_id = 0, $scoping_map = [], $mysqli = null)
{
    // Clean up surrounding <p> tags around slider shortcodes or converted slider gallery blocks before replacing
    $content = preg_replace_callback(
        '/<p[^>]*>\s*(\[(?:rev_slider|rev_slider_vc|layerslider)[^\]]*\]|<!-- wp:gallery \{(?:"className":"(?:rev-slider-replaced|layerslider-replaced)"|.*?"className":"(?:rev-slider-replaced|layerslider-replaced)".*?)\} -->.*?<!-- \/wp:gallery -->)\s*<\/p>/is',
        function ($m) {
            return $m[1];
        },
        $content
    );

    // Upgrade previously converted slider galleries to 1-column full resolution
    $content = preg_replace_callback(
        '/<!-- wp:gallery \{(?:"className":"(?:rev-slider-replaced|layerslider-replaced)"|.*?"className":"(?:rev-slider-replaced|layerslider-replaced)".*?)\} -->\s*<figure class="wp-block-gallery has-nested-images columns-[^\s"]+ is-cropped (rev-slider-replaced|layerslider-replaced)">(?:<!-- wp:paragraph --><p>(?:Slider|LayerSlider ID):\s*([^<]+)<\/p><!-- \/wp:paragraph -->)?.*?<\/figure><!-- \/wp:gallery -->/is',
        function ($matches) use ($post_id, $scoping_map, $mysqli) {
            $class = $matches[1];
            $alias = isset($matches[2]) ? trim($matches[2]) : '';
            $images = eka_resolve_slider_images_by_alias($alias, $post_id, $scoping_map, $mysqli);
            return eka_build_gutenberg_gallery_block($images, $class, $alias ? 'Slider: ' . $alias : 'Slider');
        },
        $content
    );

    // 1. vc_row
    $content = preg_replace('/\[vc_row[^\]]*\]/i', '<!-- wp:columns --><div class="wp-block-columns">', $content);
    $content = preg_replace('/\[\/vc_row\]/i', '</div><!-- /wp:columns -->', $content);

    // 2. vc_column
    $content = preg_replace_callback(
        '/\[vc_column(?:\s+width=["\']([^"\']+)["\'])?[^\]]*\]/i',
        function ($matches) {
            $width = isset($matches[1]) ? parse_fraction_width($matches[1]) : '100%';
            return '<!-- wp:column {"width":"' . $width . '"} --><div class="wp-block-column" style="flex-basis: ' . $width . ';">';
        },
        $content
    );
    $content = preg_replace('/\[\/vc_column\]/i', '</div><!-- /wp:column -->', $content);
    $content = preg_replace('/\[\/?vc_column_text[^\]]*\]/i', '', $content);

    // 3. vc_single_image
    $content = preg_replace_callback(
        '/\[vc_single_image(?:\s+[^\]]*?image=["\'](\d+)["\'])?[^\]]*\]/i',
        function ($matches) {
            $img_id = isset($matches[1]) ? (int)$matches[1] : 0;
            return '<!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image"><img src="" alt="" class="wp-image-' . $img_id . '"/></figure><!-- /wp:image -->';
        },
        $content
    );

    // 4. [caption]
    $content = preg_replace_callback(
        '/\[caption(?:\s+id=["\']([^"\']+)["\'])?(?:\s+align=["\']([^"\']+)["\'])?(?:\s+width=["\']([^"\']+)["\'])?[^\]]*\](.*?)\[\/caption\]/is',
        function ($matches) {
            $id_attr = isset($matches[1]) ? $matches[1] : '';
            $img_id = (int)preg_replace('/\D/', '', $id_attr);
            $inner = trim($matches[4]);

            if (preg_match('/(<img[^>]+>)(.*)/is', $inner, $img_matches)) {
                $img_tag = $img_matches[1];
                $caption_text = trim(strip_tags($img_matches[2]));
                return '<!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image">' . $img_tag . '<figcaption>' . htmlspecialchars($caption_text, ENT_QUOTES, 'UTF-8') . '</figcaption></figure><!-- /wp:image -->';
            }

            return '<!-- wp:image --><figure class="wp-block-image">' . $inner . '</figure><!-- /wp:image -->';
        },
        $content
    );

    // 5. rev_slider and layerslider fallback
    $content = step_3a_transform_sliders($content, $post_id, $mysqli);

    return $content;
}

/**
 * Testimonials ([testimonials]) into Board Member Query Loop.
 */
function step_4b_transform_testimonials($content)
{
    if (strpos($content, '[testimonials') === false) {
        return $content;
    }

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

    return $content;
}

/**
 * Transform [vc_posts_grid] sub-navigation cards.
 */
/**
 * Transform [vc_posts_grid] sub-navigation cards into FSE query loop grid.
 *
 * @param string $content
 * @param int $post_id
 * @return string
 */
function step_4c_transform_vc_posts_grid($content, $post_id = 0)
{
    if (strpos($content, '[vc_posts_grid') === false) {
        return $content;
    }

    $build_block_markup = function ($shortcode_str) use ($post_id) {
        $include_ids = [];

        if (preg_match('/by_id:([0-9,]+)/', $shortcode_str, $id_matches)) {
            $include_ids = array_values(array_filter(array_map('intval', explode(',', $id_matches[1]))));
        }

        if (empty($include_ids)) {
            return '<!-- wp:html -->' . $shortcode_str . '<!-- /wp:html -->';
        }

        $query_data = [
            'perPage' => 50,
            'pages' => 0,
            'offset' => 0,
            'postType' => 'page',
            'order' => 'asc',
            'orderBy' => 'menu_order',
            'author' => '',
            'search' => '',
            'exclude' => [],
            'sticky' => '',
            'inherit' => false,
            'include' => $include_ids,
        ];

        $post_id = (int)$post_id;
        if ($post_id > 0) {
            $query_data['parents'] = [$post_id];
        }

        $query_attr = [
            'queryId' => 3,
            'query' => $query_data,
            'layout' => [
                'type' => 'constrained',
            ],
        ];

        $query_json = json_encode($query_attr, JSON_UNESCAPED_SLASHES);

        $block_html = '<!-- wp:group {"layout":{"type":"constrained"}} -->' . "\n" .
            '<div class="wp-block-group"><!-- wp:query ' . $query_json . ' -->' . "\n" .
            '<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":2}} -->' . "\n" .
            '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px","bottomLeft":"12px","bottomRight":"12px"}}}} /-->' . "\n\n" .
            '<!-- wp:post-title {"level":3,"isLink":true} /-->' . "\n" .
            '<!-- /wp:post-template --></div>' . "\n" .
            '<!-- /wp:query --></div>' . "\n" .
            '<!-- /wp:group -->';

        return $block_html;
    };

    // 1. If wrapped by transformed wp:columns / wp:column from step_4a
    $content = preg_replace_callback(
        '/<!-- wp:columns -->\s*<div class="wp-block-columns">\s*<!-- wp:column {"width":"100%"} -->\s*<div class="wp-block-column" style="flex-basis: 100%;">\s*(\[vc_posts_grid[^\]]*\])\s*<\/div>\s*<!-- \/wp:column -->\s*<\/div>\s*<!-- \/wp:columns -->/is',
        function ($m) use ($build_block_markup) {
            return $build_block_markup($m[1]);
        },
        $content
    );

    // 2. If wrapped by raw vc_row / vc_column
    $content = preg_replace_callback(
        '/\[vc_row\]\s*\[vc_column[^\]]*\]\s*(\[vc_posts_grid[^\]]*\])\s*\[\/vc_column\]\s*\[\/vc_row\]/is',
        function ($m) use ($build_block_markup) {
            return $build_block_markup($m[1]);
        },
        $content
    );

    // 3. Fallback: match any bare [vc_posts_grid ...]
    $content = preg_replace_callback(
        '/\[vc_posts_grid[^\]]*\]/is',
        function ($m) use ($build_block_markup) {
            return $build_block_markup($m[0]);
        },
        $content
    );

    return $content;
}

/**
 * Residual Shortcode Logging for Analysis
 */
function step_4d_transform_residual_shortcodes($content)
{
    // Log warning if any untransformed slider shortcodes remain
    if (preg_match_all('/\[(?:rev_slider|rev_slider_vc|layerslider)[^\]]*\]/i', $content, $slider_matches)) {
        foreach ($slider_matches[0] as $raw_slider) {
            eka_shortcode_log("WARNING: Untransformed slider shortcode detected: {$raw_slider}", "WARNING");
        }
    }

    $content = preg_replace('/\[\/?vc_[^\]]*\]/', '', $content);
    $content = preg_replace('/\[\/?mfn_[^\]]*\]/', '', $content);

    $ignored_tags = ['wp', 'caption', 'vc_row', 'vc_column', 'vc_column_text', 'vc_single_image', 'vc_raw_html', 'our_team', 'rev_slider', 'rev_slider_vc', 'layerslider', 'testimonials', 'vc_posts_grid'];
    $residual_tags = [];

    $content = preg_replace_callback(
        '/\[([a-zA-Z0-9_]+)([^\]]*)\](?:(.*?)\[\/\1\])?/s',
        function ($matches) use ($ignored_tags, &$residual_tags) {
            $tag = strtolower($matches[1]);
            if (in_array($tag, $ignored_tags, true)) {
                return $matches[0];
            }
            if (in_array($tag, ['endif', 'if', 'the', 'general', 'this', 'list', 'in', 'it', 'on', 'was', 'who', 'f'], true)) {
                return $matches[0];
            }
            if (!in_array($tag, $residual_tags, true)) {
                $residual_tags[] = $tag;
            }
            return $matches[0];
        },
        $content
    );

    if (!empty($residual_tags)) {
        eka_shortcode_log('Residual shortcodes detected: ' . implode(', ', $residual_tags), 'WARNING');
    }

    return $content;
}

if ($is_direct_execution) {
    // ----------------------------------------------------------------------
    // Main Stage 04 Execution
    // ----------------------------------------------------------------------

    $scoping_map = eka_load_slider_scoping();

    $sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE post_type IN ('page', 'post', 'testimonial', 'board_member', 'alx_tachydromos') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future')";
    $res = $mysqli->query($sql);

    if (!$res) {
        eka_shortcode_log("Query failed: " . $mysqli->error, "ERROR");
        exit(1);
    }

    $total_scanned = $res->num_rows;
    eka_shortcode_log("Scanning {$total_scanned} posts for Stage 04 shortcode transformations...");

    $converted_count = 0;
    $skipped_count = 0;
    $failed_ast_count = 0;
    $failed_post_ids = [];

    while ($row = $res->fetch_assoc()) {
        $id = (int)$row['ID'];
        $original_content = $row['post_content'];

        // Apply shortcode transformations in a staged sequence.
        $content = step_4a_transform_wpbakery_and_caption($original_content, $id, $scoping_map, $mysqli);
        $content = step_4b_transform_testimonials($content);
        $content = step_4c_transform_vc_posts_grid($content, $id);
        $content = step_4d_transform_residual_shortcodes($content);

        if ($content === $original_content) {
            $skipped_count++;
            continue;
        }

        // AST Validation
        if (!eka_validate_blocks_ast($content)) {
            eka_shortcode_log("AST Validation failed for post ID {$id} ('{$row['post_title']}'). Skipping update.", "WARNING");
            $failed_ast_count++;
            $failed_post_ids[] = $id;
            continue;
        }

        $stmt = $mysqli->prepare("UPDATE wp_posts SET post_content = ? WHERE ID = ?");
        if ($stmt) {
            $stmt->bind_param("si", $content, $id);
            if ($stmt->execute()) {
                $converted_count++;
            } else {
                eka_shortcode_log("Failed to update Post ID {$id}: " . $stmt->error, "ERROR");
                $failed_ast_count++;
                $failed_post_ids[] = $id;
            }
            $stmt->close();
        }
    }

    // ----------------------------------------------------------------------
    // Metrics Summary
    // ----------------------------------------------------------------------
    eka_shortcode_log("==========================================");
    eka_shortcode_log(" STAGE 04 SUMMARY: Shortcode Migrations");
    eka_shortcode_log("==========================================");
    eka_shortcode_log(" Total Posts Scanned   : {$total_scanned}");
    eka_shortcode_log(" Successfully Converted: {$converted_count}");
    eka_shortcode_log(" Skipped / Unchanged   : {$skipped_count}");
    eka_shortcode_log(" Failed AST Validation : {$failed_ast_count}");
    if (!empty($failed_post_ids)) {
        eka_shortcode_log(" Failed Post IDs       : [" . implode(', ', $failed_post_ids) . "]");
    } else {
        eka_shortcode_log(" Failed Post IDs       : []");
    }
    eka_shortcode_log("==========================================");

    $mysqli->close();
    eka_shortcode_log("Stage 04 shortcode migration pipeline completed successfully.");
}

