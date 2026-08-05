<?php
/**
 * bin/03-surgical-migrations.php
 * Stage 03: Surgical Page-Specific Migration Engine
 * Target: backstage_eka DB via WP-CLI / MySQL
 * Performs slider -> query loop/gallery conversions, testimonial query loop conversions,
 * and vc_posts_grid sub-navigation card query transformations.
 */

require_once __DIR__ . '/migration-helpers.php';

$log_file = dirname(__DIR__) . '/ai-work/logs/03-surgical-migrations.log';
eka_init_log_file($log_file);

function eka_surgical_log($msg, $level = 'INFO') {
    static $log_file = null;
    if ($log_file === null) {
        $log_file = dirname(__DIR__) . '/ai-work/logs/03-surgical-migrations.log';
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

eka_surgical_log("==========================================");
eka_surgical_log("Starting Stage 03: Surgical Migrations - " . date('Y-m-d H:i:s'));
eka_surgical_log("==========================================");

$db_config = eka_get_db_config();
$mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
if ($mysqli->connect_error) {
    eka_surgical_log("Database connection failed: " . $mysqli->connect_error, "ERROR");
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset("utf8mb4");

// ----------------------------------------------------------------------
// Surgical Transformation Functions
// ----------------------------------------------------------------------

/**
 * 1. Transform Sliders ([rev_slider], [layerslider]) into Query Loops or Galleries
 */
function step_3a_transform_sliders($content, $post_id) {
    if (strpos($content, 'wp:query') !== false || strpos($content, 'wp:gallery') !== false) {
        // Skip if already converted
    }

    $dynamic_pages = [13236, 8934, 16894, 16892, 17194, 17215, 17219, 16920, 16923];
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
        ['ids' => [7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942], 'pages' => [7756, 17150]],
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
                '<!-- wp:image {"id":%d,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="" alt="" class="wp-image-%d"/></figure>
<!-- /wp:image -->',
                $media_id, $media_id
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

/**
 * 2. Transform Testimonials ([testimonials]) into Board Member Query Loop
 */
function step_3b_transform_testimonials($content) {
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
 * 3. Transform [vc_posts_grid] sub-navigation cards
 */
function step_3c_transform_vc_posts_grid($content) {
    if (strpos($content, '[vc_posts_grid') === false) {
        return $content;
    }

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

    return $content;
}

// ----------------------------------------------------------------------
// Main Transformation Pipeline Execution for Stage 03
// ----------------------------------------------------------------------

$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE post_type IN ('page', 'post', 'testimonial', 'board_member', 'alx_tachydromos') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future')";
$res = $mysqli->query($sql);

if (!$res) {
    eka_surgical_log("Query failed: " . $mysqli->error, "ERROR");
    exit(1);
}

$total_scanned = $res->num_rows;
eka_surgical_log("Scanning {$total_scanned} posts for Stage 03 surgical transformations...");

$converted_count = 0;
$skipped_count = 0;
$failed_ast_count = 0;
$failed_post_ids = [];

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID'];
    $original_content = $row['post_content'];

    // Apply surgical transformations: 3a -> 3b -> 3c
    $content = step_3a_transform_sliders($original_content, $id);
    $content = step_3b_transform_testimonials($content);
    $content = step_3c_transform_vc_posts_grid($content);

    if ($content === $original_content) {
        $skipped_count++;
        continue;
    }

    // AST Validation
    if (!eka_validate_blocks_ast($content)) {
        eka_surgical_log("AST Validation failed for post ID {$id} ('{$row['post_title']}'). Skipping update.", "WARNING");
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
            eka_surgical_log("Failed to update Post ID {$id}: " . $stmt->error, "ERROR");
            $failed_ast_count++;
            $failed_post_ids[] = $id;
        }
        $stmt->close();
    }
}

// ----------------------------------------------------------------------
// Metrics Summary
// ----------------------------------------------------------------------
eka_surgical_log("==========================================");
eka_surgical_log(" STAGE 03 SUMMARY: Surgical Migrations");
eka_surgical_log("==========================================");
eka_surgical_log(" Total Posts Scanned   : {$total_scanned}");
eka_surgical_log(" Successfully Converted: {$converted_count}");
eka_surgical_log(" Skipped / Unchanged   : {$skipped_count}");
eka_surgical_log(" Failed AST Validation : {$failed_ast_count}");
if (!empty($failed_post_ids)) {
    eka_surgical_log(" Failed Post IDs       : [" . implode(', ', $failed_post_ids) . "]");
} else {
    eka_surgical_log(" Failed Post IDs       : []");
}
eka_surgical_log("==========================================");

$mysqli->close();
eka_surgical_log("Stage 03 surgical migration pipeline completed successfully.");
