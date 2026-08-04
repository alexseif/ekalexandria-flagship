<?php
/**
 * Stage 2: Idempotent Classic HTML to Gutenberg Block Converter Script
 *
 * Programmatically wraps bare HTML elements (<p>, <hN>, <ul>/<ol>, <table>, <blockquote>)
 * into native Gutenberg block markup while sanitizing inline CSS via FSE property allowlist.
 */

require_once __DIR__ . '/migration-helpers.php';

$log_file = dirname(__DIR__) . '/ai-work/logs/convert-classic-to-gutenberg.log';
eka_init_log_file($log_file);

function log_msg($msg, $level = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[{$timestamp}] [{$level}] {$msg}\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
    echo $formatted;
}

log_msg("Starting Stage 2: Idempotent Classic HTML to Gutenberg Block Converter Script");

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
 * Filters all inline CSS in HTML fragment using FSE allowlist.
 */
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

/**
 * Converts classic HTML block elements into Gutenberg block markup.
 */
function convert_html_elements_to_blocks($html) {
    // 1. Headings: <h1-6>
    $html = preg_replace_callback(
        '/<h([1-6])(\s+[^>]*)?>(.*?)<\/h\1>/is',
        function ($matches) {
            $level = (int)$matches[1];
            $attrs = isset($matches[2]) ? $matches[2] : '';
            $inner = $matches[3];
            $tag_html = clean_html_inline_styles("<h{$level}{$attrs}>{$inner}</h{$level}>");
            return "<!-- wp:heading {\"level\":{$level}} -->{$tag_html}<!-- /wp:heading -->";
        },
        $html
    );

    // 2. Unordered lists: <ul>
    $html = preg_replace_callback(
        '/<ul(\s+[^>]*)?>(.*?)<\/ul>/is',
        function ($matches) {
            $attrs = isset($matches[1]) ? $matches[1] : '';
            $inner = $matches[2];
            $tag_html = clean_html_inline_styles("<ul{$attrs}>{$inner}</ul>");
            return "<!-- wp:list -->{$tag_html}<!-- /wp:list -->";
        },
        $html
    );

    // 3. Ordered lists: <ol>
    $html = preg_replace_callback(
        '/<ol(\s+[^>]*)?>(.*?)<\/ol>/is',
        function ($matches) {
            $attrs = isset($matches[1]) ? $matches[1] : '';
            $inner = $matches[2];
            $tag_html = clean_html_inline_styles("<ol{$attrs}>{$inner}</ol>");
            return "<!-- wp:list {\"ordered\":true} -->{$tag_html}<!-- /wp:list -->";
        },
        $html
    );

    // 4. Tables: <table>
    $html = preg_replace_callback(
        '/<table(\s+[^>]*)?>(.*?)<\/table>/is',
        function ($matches) {
            $attrs = isset($matches[1]) ? $matches[1] : '';
            $inner = $matches[2];
            $tag_html = clean_html_inline_styles("<table{$attrs}>{$inner}</table>");
            return "<!-- wp:table --><figure class=\"wp-block-table\">{$tag_html}</figure><!-- /wp:table -->";
        },
        $html
    );

    // 5. Blockquotes: <blockquote>
    $html = preg_replace_callback(
        '/<blockquote(\s+[^>]*)?>(.*?)<\/blockquote>/is',
        function ($matches) {
            $attrs = isset($matches[1]) ? $matches[1] : '';
            $inner = $matches[2];
            $tag_html = clean_html_inline_styles("<blockquote class=\"wp-block-quote\"{$attrs}>{$inner}</blockquote>");
            return "<!-- wp:quote -->{$tag_html}<!-- /wp:quote -->";
        },
        $html
    );

    // 6. Paragraphs: <p>
    $html = preg_replace_callback(
        '/<p(\s+[^>]*)?>(.*?)<\/p>/is',
        function ($matches) {
            $attrs = isset($matches[1]) ? $matches[1] : '';
            $inner = $matches[2];
            $tag_html = clean_html_inline_styles("<p{$attrs}>{$inner}</p>");
            return "<!-- wp:paragraph -->{$tag_html}<!-- /wp:paragraph -->";
        },
        $html
    );

    return $html;
}

/**
 * Idempotently converts unannotated classic content to Gutenberg blocks.
 */
function process_post_content_idempotent($content) {
    if (empty(trim($content))) {
        return $content;
    }

    // Split content by existing Gutenberg block annotations to isolate bare HTML segments
    $tokens = preg_split('/(<!--\s+\/?wp:[^>]+-->)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $in_block = false;
    $output = '';

    foreach ($tokens as $token) {
        if (preg_match('/^<!--\s+wp:/s', $token)) {
            // Block start
            $in_block = true;
            $output .= $token;
        } elseif (preg_match('/^<!--\s+\/wp:/s', $token)) {
            // Block end
            $in_block = false;
            $output .= $token;
        } else {
            if ($in_block) {
                // Inside an existing Gutenberg block: leave untouched
                $output .= $token;
            } else {
                // Bare HTML segment: convert elements to Gutenberg blocks
                $converted = convert_html_elements_to_blocks($token);
                $output .= $converted;
            }
        }
    }

    return $output;
}

// Fetch posts
$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE post_type IN ('page', 'post', 'testimonial') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future')";
$res = $mysqli->query($sql);

if (!$res) {
    log_msg("Query failed: " . $mysqli->error, "ERROR");
    exit(1);
}

$total_posts = $res->num_rows;
log_msg("Analyzing {$total_posts} posts for classic HTML to Gutenberg conversion.");

$updated_count = 0;
$skipped_count = 0;

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['ID'];
    $original_content = $row['post_content'];

    $converted_content = process_post_content_idempotent($original_content);

    if ($converted_content === $original_content) {
        continue; // Idempotent skip (no changes needed)
    }

    // AST Validation check
    if (!eka_validate_blocks_ast($converted_content)) {
        log_msg("AST validation failed for post ID {$id} ('{$row['post_title']}'). Skipping update.", "WARNING");
        $skipped_count++;
        continue;
    }

    // Save update to backstage_eka
    $stmt = $mysqli->prepare("UPDATE wp_posts SET post_content = ? WHERE ID = ?");
    if ($stmt) {
        $stmt->bind_param("si", $converted_content, $id);
        if ($stmt->execute()) {
            $updated_count++;
            log_msg("Successfully converted classic HTML to blocks in Post ID {$id} ('{$row['post_title']}').");
        } else {
            log_msg("Failed to update Post ID {$id}: " . $stmt->error, "ERROR");
            $skipped_count++;
        }
        $stmt->close();
    }
}

log_msg("Stage 2 Migration Summary:");
log_msg(" - Posts updated: {$updated_count}");
log_msg(" - Posts skipped/unchanged: " . ($total_posts - $updated_count));

$mysqli->close();
log_msg("Stage 2 completed successfully.");
