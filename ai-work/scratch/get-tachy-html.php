<?php
$legacy_db = new mysqli('localhost', 'root', '0024', 'db207080_eka');
if ($legacy_db->connect_error) {
    die("Connection failed: " . $legacy_db->connect_error);
}
$result = $legacy_db->query("SELECT post_content FROM wp_posts WHERE post_title LIKE '%Ταχυδρόμος%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    echo $row['post_content'];
}
$legacy_db->close();
