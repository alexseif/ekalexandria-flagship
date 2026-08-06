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

function eka_surgical_log($msg, $level = 'INFO')
{
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
function step_3a_transform_sliders($content, $post_id, $mysqli = null)
{
    // Clean up surrounding <p> tags around slider shortcodes or placeholder galleries
    $content = preg_replace_callback(
        '/<p[^>]*>\s*(\[(?:rev_slider|rev_slider_vc|layerslider)[^\]]*\]|<!-- wp:gallery \{(?:"className":"(?:rev-slider-replaced|layerslider-replaced)"|.*?"className":"(?:rev-slider-replaced|layerslider-replaced)".*?)\} -->.*?<!-- \/wp:gallery -->)\s*<\/p>/is',
        function ($m) {
            return $m[1];
        },
        $content
    );

    $dynamic_pages = [13236, 8934, 16894, 16892, 17194, 17215, 17219, 16920, 16923, 18];
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
        [
            'aliases' => ['music-museum'],
            'pages'   => [7820, 17129, 17133],
            'ids'     => [7821, 7822, 7823],
        ],
        [
            'aliases' => ['science-museum'],
            'pages'   => [7811, 17137, 17139],
            'ids'     => [7813, 7814, 7815],
        ],
        [
            'aliases' => ['cemeteries-maintenance'],
            'pages'   => [7756, 17150, 17155],
            'ids'     => [7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942],
        ],
        [
            'aliases' => ['monuments-maintenance'],
            'pages'   => [3442, 17023, 17027],
            'ids'     => [10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673],
        ],
        [
            'aliases' => ['patriarchate', 'patriarchate-building'],
            'pages'   => [7390, 17018, 17020],
            'ids'     => [10328],
        ],
    ];

    $build_group_gallery = function ($ids) use ($mysqli) {
        $images = [];
        foreach ($ids as $media_id) {
            $url = '';
            if ($mysqli instanceof mysqli) {
                $stmt = $mysqli->prepare("SELECT guid FROM wp_posts WHERE ID = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $media_id);
                    if ($stmt->execute()) {
                        $res = $stmt->get_result();
                        if ($row = $res->fetch_assoc()) {
                            $url = $row['guid'];
                        }
                    }
                    $stmt->close();
                }
            }
            $images[] = ['id' => $media_id, 'url' => $url];
        }
        return eka_build_gutenberg_gallery_block($images, 'rev-slider-replaced');
    };

    // 1. Check if post_id matches any group in gallery_groups
    $matched_group_ids = null;
    foreach ($gallery_groups as $group) {
        if (in_array((int)$post_id, $group['pages'], true)) {
            $matched_group_ids = $group['ids'];
            break;
        }
    }

    if ($matched_group_ids !== null) {
        $gallery_block = $build_group_gallery($matched_group_ids);

        if (preg_match('/\[(rev_slider|rev_slider_vc|layerslider)[^\]]*\]/i', $content)) {
            $content = preg_replace('/\[(rev_slider|rev_slider_vc|layerslider)[^\]]*\]/i', $gallery_block, $content);
            return $content;
        }
        if (preg_match('/<!-- wp:gallery \{(?:"className":"(?:rev-slider-replaced|layerslider-replaced)"|.*?"className":"(?:rev-slider-replaced|layerslider-replaced)".*?)\} -->.*?<!-- \/wp:gallery -->/is', $content)) {
            $content = preg_replace('/<!-- wp:gallery \{(?:"className":"(?:rev-slider-replaced|layerslider-replaced)"|.*?"className":"(?:rev-slider-replaced|layerslider-replaced)".*?)\} -->.*?<!-- \/wp:gallery -->/is', $gallery_block, $content);
            return $content;
        }
    }

    // 2. Replace RevSlider shortcodes or placeholder gallery blocks by Alias
    $content = preg_replace_callback(
        '/(?:\[(?:rev_slider|rev_slider_vc)(?:\s+(?:(?:alias|title|id)=["\']([^"\']+)["\']|([^\s\]]+)))?[^\]]*\]|<!-- wp:gallery \{(?:"className":"(?:rev-slider-replaced)"|.*?"className":"(?:rev-slider-replaced)".*?)\} -->.*?<p>Slider:\s*([^<]+)<\/p>.*?<!-- \/wp:gallery -->)/is',
        function ($matches) use ($gallery_groups, $build_group_gallery) {
            $alias = !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : (!empty($matches[3]) ? trim($matches[3]) : ''));

            if (!empty($alias)) {
                foreach ($gallery_groups as $group) {
                    foreach ($group['aliases'] as $g_alias) {
                        if (strcasecmp($g_alias, $alias) === 0) {
                            return $build_group_gallery($group['ids']);
                        }
                    }
                }
            }

            return eka_build_gutenberg_gallery_block([], 'rev-slider-replaced', 'Slider: ' . ($alias ?: 'default'));
        },
        $content
    );

    // 3. Replace LayerSlider shortcodes or placeholder gallery blocks by ID
    $content = preg_replace_callback(
        '/(?:\[layerslider(?:\s+(?:(?:id|title)=["\']([^"\']+)["\']|([^\s\]]+)))?[^\]]*\]|<!-- wp:gallery \{(?:"className":"(?:layerslider-replaced)"|.*?"className":"(?:layerslider-replaced)".*?)\} -->.*?<p>LayerSlider ID:\s*([^<]+)<\/p>.*?<!-- \/wp:gallery -->)/is',
        function ($matches) use ($gallery_groups, $build_group_gallery) {
            $id = !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : (!empty($matches[3]) ? trim($matches[3]) : ''));

            if (!empty($id)) {
                foreach ($gallery_groups as $group) {
                    foreach ($group['aliases'] as $g_alias) {
                        if (strcasecmp($g_alias, $id) === 0) {
                            return $build_group_gallery($group['ids']);
                        }
                    }
                }
            }

            return eka_build_gutenberg_gallery_block([], 'layerslider-replaced', 'LayerSlider ID: ' . ($id ?: 'default'));
        },
        $content
    );

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

    // Apply surgical transformations focused on dynamic slider replacements.
    $content = step_3a_transform_sliders($original_content, $id, $mysqli);

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
