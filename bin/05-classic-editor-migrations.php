<?php

/**
 * bin/05-classic-editor-migrations.php
 * Stage 05: Classic HTML Block Conversion & CSS Sanitizer Engine
 * Target: backstage_eka DB via WP-CLI / MySQL
 * Converts unhandled HTML elements outside existing block tags into native Gutenberg block markup
 * (<p>, <h1>-<h6>, <ul>, <ol>, <table>, <blockquote>) and filters inline CSS styles against the FSE allowlist.
 */

require_once __DIR__ . '/migration-helpers.php';

$log_file = dirname(__DIR__) . '/ai-work/logs/05-classic-editor-migrations.log';
eka_init_log_file($log_file);

function eka_classic_log($msg, $level = 'INFO')
{
    static $log_file = null;
    if ($log_file === null) {
        $log_file = dirname(__DIR__) . '/ai-work/logs/05-classic-editor-migrations.log';
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

eka_classic_log("==========================================");
eka_classic_log("Starting Stage 05: Classic HTML & CSS Sanitizer Migrations - " . date('Y-m-d H:i:s'));
eka_classic_log("==========================================");

$db_config = eka_get_db_config();
$mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);
if ($mysqli->connect_error) {
    eka_classic_log("Database connection failed: " . $mysqli->connect_error, "ERROR");
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset("utf8mb4");

// ----------------------------------------------------------------------
// HTML & FSE CSS Helper Functions
// ----------------------------------------------------------------------

function clean_html_inline_styles($html)
{
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

function eka_has_classic_html_markup($content)
{
    return preg_match('/<(p|h[1-6]|ul|ol|table|blockquote)\b/i', $content) === 1;
}

function convert_html_elements_to_blocks($html)
{
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

/**
 * Split content into existing block tokens vs non-block HTML, converting non-block elements.
 */
function step_5a_process_classic_html($content)
{
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
// Main Stage 05 Execution
// ----------------------------------------------------------------------

$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE post_type IN ('page', 'post', 'testimonial', 'board_member', 'alx_tachydromos') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future')";
$res = $mysqli->query($sql);

if (!$res) {
    eka_classic_log("Query failed: " . $mysqli->error, "ERROR");
    exit(1);
}

$total_scanned = $res->num_rows;
eka_classic_log("Scanning {$total_scanned} posts for Stage 05 classic HTML conversions & CSS sanitization...");

$converted_count = 0;
$skipped_count = 0;
$failed_ast_count = 0;
$failed_post_ids = [];

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID'];
    $original_content = $row['post_content'];

    if (!eka_has_classic_html_markup($original_content)) {
        $skipped_count++;
        continue;
    }

    // Apply Stage 05 classic HTML conversion
    $content = step_5a_process_classic_html($original_content);

    if ($content === $original_content) {
        $skipped_count++;
        continue;
    }

    // AST Validation
    if (!eka_validate_blocks_ast($content)) {
        eka_classic_log("AST Validation failed for post ID {$id} ('{$row['post_title']}'). Skipping update.", "WARNING");
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
            eka_classic_log("Failed to update Post ID {$id}: " . $stmt->error, "ERROR");
            $failed_ast_count++;
            $failed_post_ids[] = $id;
        }
        $stmt->close();
    }
}

// ----------------------------------------------------------------------
// Metrics Summary
// ----------------------------------------------------------------------
eka_classic_log("==========================================");
eka_classic_log(" STAGE 05 SUMMARY: Classic HTML & CSS Sanitizer");
eka_classic_log("==========================================");
eka_classic_log(" Total Posts Scanned   : {$total_scanned}");
eka_classic_log(" Successfully Converted: {$converted_count}");
eka_classic_log(" Skipped / Unchanged   : {$skipped_count}");
eka_classic_log(" Failed AST Validation : {$failed_ast_count}");
if (!empty($failed_post_ids)) {
    eka_classic_log(" Failed Post IDs       : [" . implode(', ', $failed_post_ids) . "]");
} else {
    eka_classic_log(" Failed Post IDs       : []");
}
eka_classic_log("==========================================");

$mysqli->close();
eka_classic_log("Stage 05 classic editor migration pipeline completed successfully.");
