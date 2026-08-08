<?php
/**
 * bin/migration-content-engine.php
 * Modular 6-Step Gutenberg Content Migration Engine
 * Targets: backstage_eka DB via WP-CLI / MySQL
 */

require_once __DIR__ . '/migration-helpers.php';

$GLOBALS['eka_log_file'] = dirname(__DIR__) . '/ai-work/logs/content-engine.log';
eka_init_log_file($GLOBALS['eka_log_file']);

function eka_engine_log($msg, $level = 'INFO') {
    $log_file = isset($GLOBALS['eka_log_file']) ? $GLOBALS['eka_log_file'] : dirname(__DIR__) . '/ai-work/logs/content-engine.log';
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

eka_engine_log("==========================================");
eka_engine_log("Starting Migration Content Engine: " . date('Y-m-d H:i:s'));
eka_engine_log("==========================================");

$db_config = eka_get_db_config();
$mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
if ($mysqli->connect_error) {
    eka_engine_log("Database connection failed: " . $mysqli->connect_error, "ERROR");
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset("utf8mb4");

// ----------------------------------------------------------------------
// Helper Functions for Transformations
// ----------------------------------------------------------------------

function parse_fraction_width($width_str) {
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

function clean_html_inline_styles($html) {
    return preg_replace_callback(
        '/\s+style=["\']([^"\']*)["\']/i',
        function ($matches) {
            $raw_style = $matches[1];
            $clean_style = sanitize_inline_styles_fse($raw_style);
            if (empty($clean_style)) {
                return '';
            }
            return ' style="' . htmlspecialchars($clean_style, ENT_QUOTES, 'UTF-8') . '"';
        },
        $html
    );
}

// ----------------------------------------------------------------------
// Phase 3A: Replace Sliders ([rev_slider], [layerslider])
// ----------------------------------------------------------------------
function step_3a_transform_sliders($content, $post_id) {
    // Exception pages: Hero slider is handled natively by FSE templates. Strip shortcode & wrapper container completely.
    $exception_pages = [13236, 16894, 16892, 18, 16920, 16923];
    if (in_array((int)$post_id, $exception_pages, true)) {
        $content = preg_replace('/\[vc_row[^\]]*\]\s*(?:\[vc_column[^\]]*\])?\s*\[(?:rev_slider|rev_slider_vc|layerslider)[^\]]*\]\s*(?:\[\/vc_column\])?\s*\[\/vc_row\]/is', '', $content);
        $content = preg_replace('/\[(?:rev_slider|rev_slider_vc|layerslider)[^\]]*\]/i', '', $content);
        return $content;
    }

    if (strpos($content, 'wp:query') !== false || strpos($content, 'wp:gallery') !== false) {
        // Skip if already converted
    }

    $dynamic_pages = [8934, 17194, 17215, 17219];
    $query_loop_block = '<!-- wp:query {"queryId":1,"query":{"perPage":5,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query">
<!-- wp:post-template -->
<!-- wp:post-title {"isLink":true} /-->
<!-- wp:post-excerpt {"moreText":"Read more"} /-->
<!-- wp:post-date /-->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->';

    if (in_array((int)$post_id, $dynamic_pages, true)) {
        if (preg_match('/\[(rev_slider|rev_slider_vc|layerslider)[^\]]*\]/i', $content)) {
            $content = preg_replace('/\[(rev_slider|rev_slider_vc)[^\]]*\]/i', $query_loop_block, $content);
            $content = preg_replace('/\[layerslider[^\]]*\]/i', $query_loop_block, $content);
            return $content;
        }
    }

    $gallery_groups = [
        ['ids' => [7821, 7822, 7823], 'pages' => [7820, 17129, 17133]],
        ['ids' => [7813, 7814, 7815], 'pages' => [7811, 17137, 17139]],
        ['ids' => [10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673], 'pages' => [3442, 17023, 17027, 17155]],
        ['ids' => [7935, 7936, 7937, 7938, 7940, 7941, 7942], 'pages' => [7756, 17150]],
        ['ids' => [10328], 'pages' => [7390, 17018, 17020]],
    ];
    $static_galleries = [];
    foreach ($gallery_groups as $group) {
        foreach ($group['pages'] as $pid) {
            $static_galleries[$pid] = $group['ids'];
        }
    }

    if (isset($static_galleries[(int)$post_id])) {
        $media_ids = $static_galleries[(int)$post_id];
        $gallery_block = '<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">';
        foreach ($media_ids as $media_id) {
            $gallery_block .= sprintf(
                '<!-- wp:image {"id":%d,"linkDestination":"none"} -->' . "\n" .
                '<figure class="wp-block-image"><img src="" alt=""/></figure>' . "\n" .
                '<!-- /wp:image -->',
                $media_id
            );
        }
        $gallery_block .= '</figure>
<!-- /wp:gallery -->';

        if (preg_match('/\[(rev_slider|rev_slider_vc|layerslider)[^\]]*\]/i', $content)) {
            $content = preg_replace('/\[(rev_slider|rev_slider_vc)[^\]]*\]/i', $gallery_block, $content);
            $content = preg_replace('/\[layerslider[^\]]*\]/i', $gallery_block, $content);
            return $content;
        }
    }

    // Generic slider fallback
    $content = preg_replace_callback(
        '/\[(?:rev_slider|rev_slider_vc)\s+(?:(?:alias|title|id)=["\']([^"\']+)["\']|([a-zA-Z0-9_-]+))[^\]]*\]/i',
        function ($matches) {
            $alias = !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : 'default');
            $alias = htmlspecialchars($alias, ENT_QUOTES, 'UTF-8');
            return '<!-- wp:gallery {"className":"rev-slider-replaced"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped rev-slider-replaced"><!-- wp:paragraph --><p>Slider: ' . $alias . '</p><!-- /wp:paragraph --></figure><!-- /wp:gallery -->';
        },
        $content
    );

    $content = preg_replace_callback(
        '/\[layerslider\s+(?:(?:id|title)=["\']([^"\']+)["\']|([a-zA-Z0-9_-]+))[^\]]*\]/i',
        function ($matches) {
            $id = !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : 'default');
            $id = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
            return '<!-- wp:gallery {"className":"layerslider-replaced"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped layerslider-replaced"><!-- wp:paragraph --><p>LayerSlider ID: ' . $id . '</p><!-- /wp:paragraph --></figure><!-- /wp:gallery -->';
        },
        $content
    );

    return $content;
}

// ----------------------------------------------------------------------
// Phase 3B: Replace Testimonials ([testimonials])
// ----------------------------------------------------------------------
function step_3b_transform_testimonials($content) {
    if (strpos($content, '[testimonials') === false) {
        return $content;
    }

    $board_query = '<!-- wp:query {"queryId":2,"query":{"perPage":50,"pages":0,"offset":0,"postType":"board_member","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query">
<!-- wp:post-template {"layout":{"type":"grid","columnCount":3},"className":"board-members-list"} -->
<!-- wp:group {"className":"board-member-card"} -->
<div class="wp-block-group board-member-card">
<!-- wp:post-featured-image {"isLink":false} /-->
<!-- wp:post-title {"level":3} /-->
<!-- wp:post-content /-->
</div>
<!-- /wp:group -->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->';

    $content = preg_replace('/<!-- wp:shortcode -->\s*\[testimonials[^\]]*\]\s*<!-- \/wp:shortcode -->/is', $board_query, $content);
    $content = preg_replace('/\[testimonials[^\]]*\]/is', $board_query, $content);

    return $content;
}

// ----------------------------------------------------------------------
// Phase 3C: Subpages Query Loop Shortcodes & vc_posts_grid
// ----------------------------------------------------------------------
function step_3c_transform_vc_posts_grid($content) {
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
        } else {
            // Fallback for non-by_id vc_posts_grid tags
            $content = preg_replace_callback(
                '/\[vc_posts_grid[^\]]*\]/i',
                function ($matches) {
                    return '<!-- wp:html -->' . $matches[0] . '<!-- /wp:html -->';
                },
                $content
            );
        }
    }

    // Convert standalone numeric shortcodes outside Gutenberg block comments into subpage query loops
    $tokens = preg_split('/(<!--\s+\/?wp:[^>]+-->)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $in_block = false;
    $output = '';

    foreach ($tokens as $token) {
        if (preg_match('/^<!--\s+wp:/s', $token)) {
            $in_block = true;
            $output .= $token;
        } elseif (preg_match('/^<!--\s+\/wp:/s', $token)) {
            $in_block = false;
            $output .= $token;
        } else {
            if (!$in_block) {
                $token = preg_replace_callback(
                    '/\[(\d+)\]/',
                    function ($m) {
                        $pid = (int)$m[1];
                        return '<!-- wp:query {"queryId":4,"query":{"perPage":1,"pages":0,"offset":0,"postType":"page","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false,"include":[' . $pid . ']}} -->
<div class="wp-block-query">
<!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true} /-->
<!-- wp:post-title {"isLink":true,"level":3} /-->
<!-- wp:post-excerpt /-->
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->';
                    },
                    $token
                );
            }
            $output .= $token;
        }
    }

    return $output;
}

// ----------------------------------------------------------------------
// Phase 3G: Media Embeds & Plugin Shortcodes Migration
// ----------------------------------------------------------------------
function step_3g_transform_media_and_plugins($content) {
    // 1. [embed]url[/embed] -> core/embed
    $content = preg_replace_callback(
        '/\[embed[^\]]*\]\s*(https?:\/\/[^\s<]+)\s*\[\/embed\]/i',
        function ($matches) {
            $url = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            return '<!-- wp:embed {"url":"' . $url . '","type":"rich","providerNameSlug":"embed"} --><figure class="wp-block-embed"><div class="wp-block-embed__wrapper">' . $url . '</div></figure><!-- /wp:embed -->';
        },
        $content
    );

    // 2. [video src="url"] -> core/video
    $content = preg_replace_callback(
        '/\[video\s+[^\]]*?src=["\']([^"\']+)["\'][^\]]*\]/i',
        function ($matches) {
            $url = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            return '<!-- wp:video --><figure class="wp-block-video"><video controls src="' . $url . '"></video></figure><!-- /wp:video -->';
        },
        $content
    );

    // 3. [map lat="LAT" lng="LNG" ...] -> core/html iframe
    $content = preg_replace_callback(
        '/\[map\s+[^\]]*?lat=["\']([^"\']+)["\']\s+lng=["\']([^"\']+)["\'][^\]]*\]/i',
        function ($matches) {
            $lat = $matches[1];
            $lng = $matches[2];
            return '<!-- wp:html --><iframe src="https://maps.google.com/maps?q=' . $lat . ',' . $lng . '&amp;output=embed" width="100%" height="400" frameborder="0"></iframe><!-- /wp:html -->';
        },
        $content
    );

    // 4. [gview file="URL"] -> core/file
    $content = preg_replace_callback(
        '/\[gview\s+[^\]]*?file=["\']([^"\']+)["\'][^\]]*\]/i',
        function ($matches) {
            $url = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            return '<!-- wp:file {"href":"' . $url . '","displayPreview":true} --><div class="wp-block-file"><object class="wp-block-file__embed" data="' . $url . '" type="application/pdf" style="width:100%;height:600px"></object><a href="' . $url . '">Download Document</a></div><!-- /wp:file -->';
        },
        $content
    );

    // 5. [mc4wp_form] -> [eka_mailchimp_form]
    $content = str_replace('[mc4wp_form]', '[eka_mailchimp_form]', $content);

    // 6. [our_team_list] -> remove completely
    $content = preg_replace('/\[our_team_list[^\]]*\]/i', '', $content);

    return $content;
}

// ----------------------------------------------------------------------
// Phase 3D: Structural WPBakery & Caption Shortcodes
// ----------------------------------------------------------------------
function step_3d_transform_wpbakery_and_caption($content) {
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
            return '<!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image"><img src="" alt=""/></figure><!-- /wp:image -->';
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

    return $content;
}

// ----------------------------------------------------------------------
// Phase 3E: Residual Shortcode Clean-Up & Block Comment Isolation
// ----------------------------------------------------------------------
function step_3e_transform_residual_shortcodes($content) {
    $content = preg_replace('/\[\/?vc_[^\]]*\]/', '', $content);
    $content = preg_replace('/\[\/?mfn_[^\]]*\]/', '', $content);

    $ignored_tags = ['wp', 'caption', 'vc_row', 'vc_column', 'vc_column_text', 'vc_single_image', 'vc_raw_html', 'our_team', 'rev_slider', 'rev_slider_vc', 'layerslider', 'testimonials', 'vc_posts_grid', 'eka_mailchimp_form', 'polylang_langswitcher'];

    // Split content by Gutenberg HTML comments to prevent JSON attributes inside <!-- wp:... --> comments from being transformed
    $tokens = preg_split('/(<!--\s+\/?wp:[^>]+-->)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $in_block = false;
    $output = '';

    foreach ($tokens as $token) {
        if (preg_match('/^<!--\s+wp:/s', $token)) {
            $in_block = true;
            $output .= $token;
        } elseif (preg_match('/^<!--\s+\/wp:/s', $token)) {
            $in_block = false;
            $output .= $token;
        } else {
            if ($in_block) {
                $output .= $token;
            } else {
                $converted = preg_replace_callback(
                    '/\[([a-zA-Z0-9_]+)([^\]]*)\](?:(.*?)\[\/\1\])?/s',
                    function ($matches) use ($ignored_tags) {
                        $tag = strtolower($matches[1]);
                        if (in_array($tag, $ignored_tags, true)) {
                            return $matches[0];
                        }
                        // Ignore common Greek/English words in brackets or plain text annotations (e.g. [Sigma], [Greek], [5.4 acres])
                        if (preg_match('/^[A-Z][a-z]+$/', $matches[1]) || in_array($tag, ['endif', 'if', 'the', 'general', 'this', 'list', 'in', 'it', 'on', 'was', 'who', 'f', 'sigma', 'greek'], true)) {
                            return $matches[0];
                        }

                        $raw_shortcode = $matches[0];
                        return '<!-- wp:html -->' . $raw_shortcode . '<!-- /wp:html -->';
                    },
                    $token
                );
                $output .= $converted;
            }
        }
    }

    return $output;
}

// ----------------------------------------------------------------------
// Phase 3F: Classic HTML AST Block Conversion & Inline CSS Allowlist
// ----------------------------------------------------------------------
function convert_html_elements_to_blocks($html) {
    $rules = [
        '/<h([1-6])(\s+[^>]*)?>(.*?)<\/h\1>/is' => function ($m) {
            $level = (int)$m[1];
            $tag_html = clean_html_inline_styles("<h{$level}" . ($m[2] ?? '') . ">{$m[3]}</h{$level}>");
            return "<!-- wp:heading {\"level\":{$level}} -->{$tag_html}<!-- /wp:heading -->";
        },
        '/<ul(\s+[^>]*)?>(.*?)<\/ul>/is' => function ($m) {
            $tag_html = clean_html_inline_styles("<ul" . ($m[1] ?? '') . ">{$m[2]}</ul>");
            return "<!-- wp:list -->{$tag_html}<!-- /wp:list -->";
        },
        '/<ol(\s+[^>]*)?>(.*?)<\/ol>/is' => function ($m) {
            $tag_html = clean_html_inline_styles("<ol" . ($m[1] ?? '') . ">{$m[2]}</ol>");
            return "<!-- wp:list {\"ordered\":true} -->{$tag_html}<!-- /wp:list -->";
        },
        '/<table(\s+[^>]*)?>(.*?)<\/table>/is' => function ($m) {
            $tag_html = clean_html_inline_styles("<table" . ($m[1] ?? '') . ">{$m[2]}</table>");
            return "<!-- wp:table --><figure class=\"wp-block-table\">{$tag_html}</figure><!-- /wp:table -->";
        },
        '/<blockquote(\s+[^>]*)?>(.*?)<\/blockquote>/is' => function ($m) {
            $tag_html = clean_html_inline_styles("<blockquote class=\"wp-block-quote\"" . ($m[1] ?? '') . ">{$m[2]}</blockquote>");
            return "<!-- wp:quote -->{$tag_html}<!-- /wp:quote -->";
        },
        '/<p(\s+[^>]*)?>(.*?)<\/p>/is' => function ($m) {
            $tag_html = clean_html_inline_styles("<p" . ($m[1] ?? '') . ">{$m[2]}</p>");
            return "<!-- wp:paragraph -->{$tag_html}<!-- /wp:paragraph -->";
        },
    ];

    foreach ($rules as $pattern => $callback) {
        $html = preg_replace_callback($pattern, $callback, $html);
    }

    return $html;
}

function step_3f_process_classic_html($content) {
    if (empty(trim($content))) {
        return $content;
    }

    $tokens = preg_split('/(<!--\s+\/?wp:[^>]+-->)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    $in_block = false;
    $output = '';

    foreach ($tokens as $token) {
        if (preg_match('/^<!--\s+wp:/s', $token)) {
            $in_block = true;
            $output .= $token;
        } elseif (preg_match('/^<!--\s+\/wp:/s', $token)) {
            $in_block = false;
            $output .= $token;
        } else {
            if ($in_block) {
                $output .= $token;
            } else {
                $converted = convert_html_elements_to_blocks($token);
                $output .= $converted;
            }
        }
    }

    return $output;
}

// ----------------------------------------------------------------------
// Main Transformation Pipeline Execution
// ----------------------------------------------------------------------

$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE post_type IN ('page', 'post', 'testimonial', 'board_member', 'alx_tachydromos') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future')";
$res = $mysqli->query($sql);

if (!$res) {
    eka_engine_log("Query failed: " . $mysqli->error, "ERROR");
    exit(1);
}

$total_scanned = $res->num_rows;
eka_engine_log("Scanning {$total_scanned} posts across 6-step transformation pipeline...");

$converted_count = 0;
$skipped_count = 0;
$failed_ast_count = 0;
$failed_post_ids = [];

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID'];
    $original_content = $row['post_content'];

    // Sequence 3A -> 3B -> 3C -> 3G -> 3D -> 3E -> 3F
    $content = step_3a_transform_sliders($original_content, $id);
    $content = step_3b_transform_testimonials($content);
    $content = step_3c_transform_vc_posts_grid($content);
    $content = step_3g_transform_media_and_plugins($content);
    $content = step_3d_transform_wpbakery_and_caption($content);
    $content = step_3e_transform_residual_shortcodes($content);
    $content = step_3f_process_classic_html($content);

    if ($content === $original_content) {
        $skipped_count++;
        continue;
    }

    // AST Validation
    if (!eka_validate_blocks_ast($content)) {
        eka_engine_log("AST Validation failed for post ID {$id} ('{$row['post_title']}'). Skipping update.", "WARNING");
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
            eka_engine_log("Failed to update Post ID {$id}: " . $stmt->error, "ERROR");
            $failed_ast_count++;
            $failed_post_ids[] = $id;
        }
        $stmt->close();
    }
}

// ----------------------------------------------------------------------
// Metrics Summary
// ----------------------------------------------------------------------
eka_engine_log("==========================================");
eka_engine_log(" MIGRATION SUMMARY: Shortcode & Block Remediation");
eka_engine_log("==========================================");
eka_engine_log(" Total Posts Scanned   : {$total_scanned}");
eka_engine_log(" Successfully Converted: {$converted_count}");
eka_engine_log(" Skipped / Unchanged   : {$skipped_count}");
eka_engine_log(" Failed AST Validation : {$failed_ast_count}");
if (!empty($failed_post_ids)) {
    eka_engine_log(" Failed Post IDs       : [" . implode(', ', $failed_post_ids) . "]");
} else {
    eka_engine_log(" Failed Post IDs       : []");
}
eka_engine_log("==========================================");

$mysqli->close();
eka_engine_log("Content engine pipeline execution completed successfully.");
