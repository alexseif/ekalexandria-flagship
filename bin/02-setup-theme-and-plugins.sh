#!/bin/bash
# bin/02-setup-theme-and-plugins.sh
# Theme Activation, CPT Import & Legacy Plugin Cleanup Script (Script 2)
# Targets: /var/www/backstage.ekalexandria.org (DB: backstage_eka)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/02-setup-theme-and-plugins.log"
CPT_LOG="$LOG_DIR/cpt-migration.log"
CLEANUP_LOG="$LOG_DIR/cleanup-plugins.log"

mkdir -p "$LOG_DIR"
> "$MAIN_LOG"
> "$CPT_LOG"
> "$CLEANUP_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

echo "=========================================="
echo "Starting Theme & Plugins Setup: $(date)"
echo "Target DB: backstage_eka"
echo "=========================================="

# 1. Activate Flagship Theme
echo "Activating ekalexandria-flagship theme..."
cd "$WP_DIR" || exit 1
php7.4 $(which wp) theme activate ekalexandria-flagship --path="$WP_DIR" --allow-root || { echo "ERROR: Theme activation failed."; exit 1; }

# 2. Remove Legacy BeTheme Theme if Present
echo "Removing legacy BeTheme theme if present..."
THEME_SLUG="betheme"
THEME_PATH="$WP_DIR/wp-content/themes/$THEME_SLUG"

if [ -d "$THEME_PATH" ]; then
    echo "Deleting BeTheme theme via WP-CLI..." | tee -a "$CLEANUP_LOG"
    php7.4 $(which wp) theme delete "$THEME_SLUG" --path="$WP_DIR" --allow-root >> "$CLEANUP_LOG" 2>&1 || echo "WP-CLI theme delete returned a non-zero exit code; continuing with filesystem cleanup." | tee -a "$CLEANUP_LOG"
fi

if [ -d "$THEME_PATH" ]; then
    echo "Removing stale BeTheme theme directory: $THEME_PATH" | tee -a "$CLEANUP_LOG"
    rm -rf "$THEME_PATH"
fi

# 3. Execute CPT Migration (Tachydromos PDFs & Board Member Testimonials)
echo "Executing CPT Migration script (bin/migrate-cpts.php)..."
if [ -f "$THEME_DIR/bin/migrate-cpts.php" ]; then
    php7.4 $(which wp) eval-file "$THEME_DIR/bin/migrate-cpts.php" --path="$WP_DIR" --allow-root || { echo "WARNING: CPT migration returned non-zero exit code."; }
else
    echo "ERROR: bin/migrate-cpts.php not found!"
    exit 1
fi

# 3. Deactivate & Delete Legacy Plugins with Automated Fallback
echo "Cleaning up legacy plugins..."
LEGACY_PLUGINS=(
    "LayerSlider"
    "js_composer"
    "display-posts-shortcode"
    "force-regenerate-thumbnails"
    "ewww-image-optimizer"
    "wordpress-seo"
    "w3-total-cache"
)

PLUGINS_DIR="$WP_DIR/wp-content/plugins"

for plugin in "${LEGACY_PLUGINS[@]}"; do
    echo "Processing legacy plugin: $plugin..."
    php7.4 $(which wp) plugin deactivate "$plugin" --path="$WP_DIR" --allow-root >> "$CLEANUP_LOG" 2>&1
    php7.4 $(which wp) plugin uninstall "$plugin" --deactivate --path="$WP_DIR" --allow-root >> "$CLEANUP_LOG" 2>&1
    
    # Fallback enforcement if directory remains
    TARGET_DIR="$PLUGINS_DIR/$plugin"
    if [ -d "$TARGET_DIR" ]; then
        echo "WP-CLI deletion left directory behind ($plugin). Executing rm -rf fallback..." | tee -a "$CLEANUP_LOG"
        rm -rf "$TARGET_DIR"
    fi
done

# 4. Remove Legacy Drop-ins and Cache Directories
echo "Purging legacy cache drop-ins and configurations..."
DROPINS=(
    "$WP_DIR/wp-content/advanced-cache.php"
    "$WP_DIR/wp-content/object-cache.php"
)

for dropin in "${DROPINS[@]}"; do
    if [ -f "$dropin" ]; then
        echo "Removing drop-in file: $dropin" | tee -a "$CLEANUP_LOG"
        rm -f "$dropin"
    fi
done

CACHE_DIRS=(
    "$WP_DIR/wp-content/cache"
    "$WP_DIR/wp-content/w3tc-config"
)

for cdir in "${CACHE_DIRS[@]}"; do
    if [ -d "$cdir" ]; then
        echo "Removing legacy cache directory: $cdir" | tee -a "$CLEANUP_LOG"
        rm -rf "$cdir"
    fi
done

# 5. Flush Permalinks and Rewrite Rules
echo "Flushing rewrite rules post-setup..."
php7.4 $(which wp) rewrite flush --path="$WP_DIR" --allow-root

# 6. Transient Clean-Up & Cache Flush
echo "Flushing transient cache and object cache..."
cd "$WP_DIR" || exit 1
php7.4 $(which wp) transient delete --all --path="$WP_DIR"
php7.4 $(which wp) cache flush --path="$WP_DIR"

echo "Theme activation, CPT migration, and legacy plugin cleanup complete successfully at $(date)!"
exit 0
