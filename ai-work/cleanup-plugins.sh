#!/bin/bash
# cleanup-plugins.sh
# Run this from /var/www/backstage.ekalexandria.org/public

echo "Cleaning up legacy drop-ins..."
rm -f wp-content/advanced-cache.php
rm -f wp-content/object-cache.php
rm -rf wp-content/cache
rm -rf wp-content/w3tc-config

echo "Deactivating legacy plugins..."
php7.4 $(which wp) plugin deactivate LayerSlider js_composer ewww-image-optimizer wordpress-seo || wp plugin deactivate LayerSlider js_composer ewww-image-optimizer wordpress-seo

echo "Uninstalling legacy plugins..."
php7.4 $(which wp) plugin uninstall LayerSlider js_composer ewww-image-optimizer wordpress-seo || wp plugin uninstall LayerSlider js_composer ewww-image-optimizer wordpress-seo

echo "Plugin cleanup complete. Running wp plugin list to verify:"
php7.4 $(which wp) plugin list || wp plugin list
