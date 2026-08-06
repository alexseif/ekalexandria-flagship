<?php
/**
 * bin/assign-menus.php
 * Re-assigns WordPress Navigation Menu Locations.
 */

$locations = get_theme_mod('nav_menu_locations', []);

$menu_map = [
    'main-menu' => 13,   // Greek Main Menu
    'main-menu___en' => 3315, // English Main Menu
    'main-menu___ar' => 3316, // Arabic Main Menu
    'social-menu-bottom' => 21,   // Greek Footer Menu
];

foreach ($menu_map as $location => $term_id) {
    $locations[$location] = $term_id;
    echo "Assigned menu ID $term_id to location '$location'\n";
}

set_theme_mod('nav_menu_locations', $locations);
echo "Menu locations successfully updated and verified.\n";
