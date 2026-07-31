#!/bin/bash
# bin/cleanup-plugins.sh

echo "Deactivating legacy plugins..."
php7.4 $(which wp) --path=/var/www/backstage.ekalexandria.org/public/ plugin deactivate LayerSlider js_composer display-posts-shortcode force-regenerate-thumbnails

echo "Uninstalling legacy plugins..."
php7.4 $(which wp) --path=/var/www/backstage.ekalexandria.org/public/ plugin delete LayerSlider js_composer display-posts-shortcode force-regenerate-thumbnails

echo "Legacy plugin cleanup complete!"
exit 0
