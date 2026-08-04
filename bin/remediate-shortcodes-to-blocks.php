<?php
/**
 * Stage 1: Shortcode & WPBakery Gutenberg Transformer Script
 *
 * Programmatically converts legacy shortcodes and WPBakery structures into
 * native Gutenberg block primitives in the backstage_eka database.
 */

require_once __DIR__ . '/migration-helpers.php';

$log_file = dirname(__DIR__) . '/ai-work/logs/remediate-shortcodes-to-blocks.log';
eka_init_log_file($log_file);

function log_msg($msg, $level = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[{$timestamp}] [{$level}] {$msg}\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
    echo $formatted;
}

log_msg("Starting Stage 1: Shortcode & WPBakery Gutenberg Transformer Script");

$host = 'localhost';
$user = 'root';
$pass = '0024';
$dbname = 'backstage_eka';

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    log_msg("Database connection failed: " . $mysqli->connect_error, "ERROR");
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset("utf8mb4");
log_msg("Connected to target database: $dbname");

/**
 * Calculates percentage from fraction string like "1/2", "1/3", "2/3", "1/4", "3/4", "1/1".
 */
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

/**
 * Transforms WPBakery shortcodes in content.
 */
function transform_wpbakery_shortcodes($content) {
    // 1. vc_row open / close
    $content = preg_replace(
        '/\[vc_row[^\]]*\]/i',
        '<!-- wp:columns --><div class="wp-block-columns">',
        $content
    );
    $content = preg_replace(
        '/\[\/vc_row\]/i',
        '</div><!-- /wp:columns -->',
        $content
    );

    // 2. vc_column open / close
    $content = preg_replace_callback(
        '/\[vc_column(?:\s+width=["\']([^"\']+)["\'])?[^\]]*\]/i',
        function ($matches) {
            $width = isset($matches[1]) ? parse_fraction_width($matches[1]) : '100%';
            return '<!-- wp:column {"width":"' . $width . '"} --><div class="wp-block-column" style="flex-basis: ' . $width . ';">';
        },
        $content
    );
    $content = preg_replace(
        '/\[\/vc_column\]/i',
        '</div><!-- /wp:column -->',
        $content
    );

    // If there are unclosed column or row block comments, append closing tags
    $open_cols = substr_count($content, '<!-- wp:columns -->');
    $close_cols = substr_count($content, '<!-- /wp:columns -->');
    while ($close_cols < $open_cols) {
        $content .= '</div><!-- /wp:columns -->';
        $close_cols++;
    }

    $open_col = substr_count($content, '<!-- wp:column {"width":');
    $close_col = substr_count($content, '<!-- /wp:column -->');
    while ($close_col < $open_col) {
        $content .= '</div><!-- /wp:column -->';
        $close_col++;
    }

    // 3. vc_column_text open / close
    $content = preg_replace(
        '/\[vc_column_text[^\]]*\]/i',
        '',
        $content
    );
    $content = preg_replace(
        '/\[\/vc_column_text\]/i',
        '',
        $content
    );

    // 4. vc_single_image
    $content = preg_replace_callback(
        '/\[vc_single_image(?:\s+[^\]]*?image=["\'](\d+)["\'])?[^\]]*\]/i',
        function ($matches) {
            $img_id = isset($matches[1]) ? (int)$matches[1] : 0;
            return '<!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image"><img src="" alt="" class="wp-image-' . $img_id . '"/></figure><!-- /wp:image -->';
        },
        $content
    );

    // 5. vc_raw_html and vc_posts_grid
    $content = preg_replace_callback(
        '/\[vc_raw_html[^\]]*\](.*?)\[\/vc_raw_html\]/is',
        function ($matches) {
            $raw_html = rawurldecode(base64_decode(trim($matches[1])));
            if (empty($raw_html)) {
                $raw_html = trim($matches[1]);
            }
            return '<!-- wp:html -->' . $raw_html . '<!-- /wp:html -->';
        },
        $content
    );

    $content = preg_replace_callback(
        '/\[vc_posts_grid[^\]]*\]/i',
        function ($matches) {
            return '<!-- wp:html -->' . $matches[0] . '<!-- /wp:html -->';
        },
        $content
    );

    return $content;
}

/**
 * Transforms [our_team] shortcode.
 */
function transform_our_team($content) {
    return preg_replace_callback(
        '/\[our_team(?:\s+heading=["\']([^"\']+)["\'])?(?:\s+title=["\']([^"\']+)["\'])?[^\]]*\]/i',
        function ($matches) {
            $heading = isset($matches[1]) ? htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') : 'Team Member';
            $title = isset($matches[2]) ? htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') : '';
            return '<!-- wp:group {"className":"our-team-card"} --><div class="wp-block-group our-team-card"><!-- wp:heading {"level":3} --><h3>' . $heading . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . $title . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
        },
        $content
    );
}

/**
 * Transforms [rev_slider] and [layerslider].
 */
function transform_sliders($content) {
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

/**
 * Transforms classic WP [caption] shortcode.
 */
function transform_caption($content) {
    return preg_replace_callback(
        '/\[caption(?:\s+id=["\']([^"\']+)["\'])?(?:\s+align=["\']([^"\']+)["\'])?(?:\s+width=["\']([^"\']+)["\'])?[^\]]*\](.*?)\[\/caption\]/is',
        function ($matches) {
            $id_attr = isset($matches[1]) ? $matches[1] : '';
            $img_id = preg_replace('/\D/', '', $id_attr);
            $inner = trim($matches[4]);

            // Separate <img> tag and caption text
            if (preg_match('/(<img[^>]+>)(.*)/is', $inner, $img_matches)) {
                $img_tag = $img_matches[1];
                $caption_text = trim(strip_tags($img_matches[2]));
                return '<!-- wp:image {"id":' . (int)$img_id . '} --><figure class="wp-block-image">' . $img_tag . '<figcaption>' . htmlspecialchars($caption_text, ENT_QUOTES, 'UTF-8') . '</figcaption></figure><!-- /wp:image -->';
            }

            return '<!-- wp:image --><figure class="wp-block-image">' . $inner . '</figure><!-- /wp:image -->';
        },
        $content
    );
}

/**
 * Wraps unhandled custom shortcodes in core/html.
 */
function transform_generic_shortcodes($content) {
    $ignored_tags = ['wp', 'caption', 'vc_row', 'vc_column', 'vc_column_text', 'vc_single_image', 'vc_raw_html', 'our_team', 'rev_slider', 'rev_slider_vc', 'layerslider'];
    
    return preg_replace_callback(
        '/\[([a-zA-Z0-9_]+)([^\]]*)\](?:(.*?)\[\/\1\])?/s',
        function ($matches) use ($ignored_tags) {
            $tag = strtolower($matches[1]);
            if (in_array($tag, $ignored_tags, true)) {
                return $matches[0];
            }
            // Skip false positives
            if (in_array($tag, ['endif', 'if', 'the', 'general', 'this', 'list', 'in', 'it', 'on', 'was', 'who', 'f'], true)) {
                return $matches[0];
            }

            $raw_shortcode = $matches[0];
            return '<!-- wp:html -->' . $raw_shortcode . '<!-- /wp:html -->';
        },
        $content
    );
}

// Fetch posts containing shortcodes
$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE post_type IN ('page', 'post', 'testimonial') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future') AND (post_content LIKE '%[%' AND post_content LIKE '%]%')";
$res = $mysqli->query($sql);

if (!$res) {
    log_msg("Query failed: " . $mysqli->error, "ERROR");
    exit(1);
}

$total_matched = $res->num_rows;
log_msg("Found {$total_matched} posts with potential shortcodes to transform.");

$updated_count = 0;
$skipped_count = 0;

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID'];
    $original_content = $row['post_content'];

    $content = transform_wpbakery_shortcodes($original_content);
    $content = transform_our_team($content);
    $content = transform_sliders($content);
    $content = transform_caption($content);
    $content = transform_generic_shortcodes($content);

    if ($content === $original_content) {
        continue;
    }

    // AST Validation check
    if (!eka_validate_blocks_ast($content)) {
        log_msg("AST validation failed for post ID {$id} ('{$row['post_title']}'). Skipping update.", "WARNING");
        $skipped_count++;
        continue;
    }

    // Save update to backstage_eka
    $stmt = $mysqli->prepare("UPDATE wp_posts SET post_content = ? WHERE ID = ?");
    if ($stmt) {
        $stmt->bind_param("si", $content, $id);
        if ($stmt->execute()) {
            $updated_count++;
            log_msg("Successfully remediated shortcodes in Post ID {$id} ('{$row['post_title']}').");
        } else {
            log_msg("Failed to update Post ID {$id}: " . $stmt->error, "ERROR");
            $skipped_count++;
        }
        $stmt->close();
    }
}

log_msg("Stage 1 Migration Summary:");
log_msg(" - Posts updated: {$updated_count}");
log_msg(" - Posts skipped/failed: {$skipped_count}");

$mysqli->close();
log_msg("Stage 1 completed successfully.");
