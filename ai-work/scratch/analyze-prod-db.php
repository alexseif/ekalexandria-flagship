<?php
/**
 * ai-work/scratch/analyze-prod-db.php
 * Connects directly to local production DB (db207080_eka)
 * Analyzes all shortcodes, classic blocks, WPBakery, sliders, MFN builder items across posts/pages.
 */

$host = 'localhost';
$user = 'root';
$pass = '0024';
$dbname = 'db207080_eka';

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset("utf8mb4");

echo "Connected to production database local: $dbname\n";

$res = $mysqli->query("SELECT ID, post_title, post_type, post_name, post_content FROM wp_posts WHERE post_type IN ('page', 'post') AND post_status IN ('publish', 'draft', 'private', 'pending', 'future')");

$total_posts = $res->num_rows;
echo "Found $total_posts total posts/pages in $dbname.\n";

$inventory = [];
$stats = [];
$shortcodes_found = [];
$classic_blocks = [];

while ($row = $res->fetch_assoc()) {
    $id = $row['ID'];
    $title = $row['post_title'];
    $type = $row['post_type'];
    $slug = $row['post_name'];
    $content = $row['post_content'];

    // 1. Check for Gutenberg vs Classic / Shortcodes
    $has_gutenberg = (strpos($content, '<!-- wp:') !== false);

    // 2. Extract shortcodes
    if (preg_match_all('/\[([a-zA-Z0-9_]+)([^\]]*)\]/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $tag = strtolower($m[1]);
            if (in_array($tag, ['caption', 'gallery', 'audio', 'video'])) {
                // Core WP shortcodes
            }
            if (!isset($shortcodes_found[$tag])) {
                $shortcodes_found[$tag] = 0;
            }
            $shortcodes_found[$tag]++;

            $inventory[] = [
                'post_id' => $id,
                'title' => $title,
                'post_type' => $type,
                'tag' => $tag,
                'raw_snippet' => mb_substr($m[0], 0, 150)
            ];
        }
    }

    // 3. Classic content without gutenberg blocks
    if (!$has_gutenberg && !empty(trim($content))) {
        $classic_blocks[] = [
            'post_id' => $id,
            'title' => $title,
            'post_type' => $type,
            'length' => mb_strlen($content)
        ];
    }
}

echo "\n--- Shortcodes Summary in Prod DB ($dbname) ---\n";
arsort($shortcodes_found);
foreach ($shortcodes_found as $tag => $cnt) {
    echo sprintf(" - [%s]: %d occurrences\n", $tag, $cnt);
}

echo sprintf("\nTotal Shortcode Instances: %d\n", count($inventory));
echo sprintf("Posts/Pages without Gutenberg blocks (Classic Content): %d\n", count($classic_blocks));

// Check meta for MFN builder
$meta_res = $mysqli->query("SELECT post_id, meta_key FROM wp_postmeta WHERE meta_key IN ('_mfn-builder-items', 'mfn-post-slider')");
echo sprintf("MFN builder / post-slider meta rows found: %d\n", $meta_res->num_rows);

$mysqli->close();
