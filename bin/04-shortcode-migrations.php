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

function eka_shortcode_log($msg, $level = 'INFO') {
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

// ----------------------------------------------------------------------
// Shortcode Transformation Functions
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

/**
 * Structural WPBakery (vc_row, vc_column, vc_single_image) & Caption Transformations
 */
function step_4a_transform_wpbakery_and_caption($content) {
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

    return $content;
}

/**
 * Residual Shortcode Clean-Up & Html Block Wrapping
 */
function step_4b_transform_residual_shortcodes($content) {
    $content = preg_replace('/\[\/?vc_[^\]]*\]/', '', $content);
    $content = preg_replace('/\[\/?mfn_[^\]]*\]/', '', $content);

    $ignored_tags = ['wp', 'caption', 'vc_row', 'vc_column', 'vc_column_text', 'vc_single_image', 'vc_raw_html', 'our_team', 'rev_slider', 'rev_slider_vc', 'layerslider', 'testimonials', 'vc_posts_grid'];

    $content = preg_replace_callback(
        '/\[([a-zA-Z0-9_]+)([^\]]*)\](?:(.*?)\[\/\1\])?/s',
        function ($matches) use ($ignored_tags) {
            $tag = strtolower($matches[1]);
            if (in_array($tag, $ignored_tags, true)) {
                return $matches[0];
            }
            if (in_array($tag, ['endif', 'if', 'the', 'general', 'this', 'list', 'in', 'it', 'on', 'was', 'who', 'f'], true)) {
                return $matches[0];
            }

            $raw_shortcode = $matches[0];
            return '<!-- wp:html -->' . $raw_shortcode . '<!-- /wp:html -->';
        },
        $content
    );

    return $content;
}

// ----------------------------------------------------------------------
// Main Stage 04 Execution
// ----------------------------------------------------------------------

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

    // Apply shortcode transformations: 4a -> 4b
    $content = step_4a_transform_wpbakery_and_caption($original_content);
    $content = step_4b_transform_residual_shortcodes($content);

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
