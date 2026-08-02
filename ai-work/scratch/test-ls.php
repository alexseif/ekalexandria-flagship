<?php
$db = new mysqli('localhost', 'root', '0024', 'db207080_eka');
$res = $db->query("SELECT id, name, data FROM wp_layerslider LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo substr($row['data'], 0, 500) . "...\n"; // Just print a snippet of the data
}
$db->close();
