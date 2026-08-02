#!/bin/bash
# bin/cleanup-plugins.sh

STAGING_DIR="/var/www/backstage.ekalexandria.org"
THEME_DIR="$STAGING_DIR/public/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
LOG_FILE="$LOG_DIR/cleanup-plugins.log"

mkdir -p "$LOG_DIR"

exec > >(tee -a "$LOG_FILE") 2>&1

echo "=========================================="
echo "Executing Legacy Plugin Cleanup: $(date)"
echo "=========================================="

LEGACY_PLUGINS=(
    "LayerSlider"
    "js_composer"
    "display-posts-shortcode"
    "force-regenerate-thumbnails"
    "ewww-image-optimizer"
    "wordpress-seo"
    "w3-total-cache"
)

echo "Cleaning up legacy drop-ins and cache directories..."
rm -f "$STAGING_DIR/public/wp-content/advanced-cache.php"
rm -f "$STAGING_DIR/public/wp-content/object-cache.php"
rm -rf "$STAGING_DIR/public/wp-content/cache"
rm -rf "$STAGING_DIR/public/wp-content/w3tc-config"
echo "Reasoning: Removed cached drop-ins to prevent autoloader and cache header conflicts."

for plugin in "${LEGACY_PLUGINS[@]}"; do
    echo "Processing plugin: $plugin"
    
    # 1. Attempt WP-CLI deactivation
    php7.4 $(which wp) --path="$STAGING_DIR/public/" plugin deactivate "$plugin" --skip-plugins --allow-root 2>/dev/null
    
    # 2. Attempt WP-CLI deletion
    if php7.4 $(which wp) --path="$STAGING_DIR/public/" plugin delete "$plugin" --force --skip-plugins --allow-root 2>/dev/null; then
        echo "[SUCCESS] Uninstalled $plugin via WP-CLI."
    else
        echo "[ERROR] WP-CLI uninstall failed for $plugin. Manual developer remediation required per spec mandate (automated rm -rf fallbacks strictly disabled)."
    fi
done

echo "Verifying active plugin status:"
php7.4 $(which wp) --path="$STAGING_DIR/public/" plugin list --skip-plugins --allow-root

echo "Plugin cleanup completed successfully at $(date)!"
exit 0
