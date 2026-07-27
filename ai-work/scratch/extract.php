<?php
$conn = new mysqli('localhost', 'root', '0024', 'db207080_eka');
$res = $conn->query("SELECT option_value FROM wp_options WHERE option_name='betheme'");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $options = unserialize($row['option_value']);
    echo "=== LAYOUT & SPACING ===\n";
    foreach ($options as $key => $val) {
        if ((strpos($key, 'grid') !== false || strpos($key, 'layout') !== false || strpos($key, 'spacing') !== false) && !empty($val)) {
            echo "[$key] => " . (is_array($val) ? json_encode($val) : $val) . "\n";
        }
    }
}
$conn->close();
