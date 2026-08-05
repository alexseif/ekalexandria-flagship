#!/bin/bash
# bin/03-migrate-content.sh
# Content Transformation & Navigation Assignment Script (Script 3)
# Targets: /var/www/backstage.ekalexandria.org (DB: extracted from environment/wp-config.php)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/03-migrate-content.log"
ENGINE_LOG="$LOG_DIR/content-engine.log"
MENU_LOG="$LOG_DIR/menu-assignments.log"

mkdir -p "$LOG_DIR"
> "$MAIN_LOG"
> "$ENGINE_LOG"
> "$MENU_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

echo "=========================================="
echo "Starting Content Transformation & Navigation Assignment: $(date)"
echo "Target WP Path: $WP_DIR"
echo "=========================================="

# 1. Execute Content Transformation Engine (Steps 3A - 3F)
echo "Executing Content Transformation Engine (bin/migration-content-engine.php)..."
if [ -f "$THEME_DIR/bin/migration-content-engine.php" ]; then
    php7.4 $(which wp) eval-file "$THEME_DIR/bin/migration-content-engine.php" --path="$WP_DIR" --allow-root || { echo "ERROR: Content engine execution failed."; exit 1; }
else
    echo "ERROR: bin/migration-content-engine.php not found!"
    exit 1
fi

# 2. Transient Clean-Up (Step 3G)
echo "Flushing transient cache..."
cd "$WP_DIR" || exit 1
php7.4 $(which wp) transient delete --all --path="$WP_DIR" --allow-root

# 3. Page Template Assignments (Step 3H)
echo "Assigning FSE Page Templates (bin/assign-page-templates.php)..."
if [ -f "$THEME_DIR/bin/assign-page-templates.php" ]; then
    php7.4 $(which wp) eval-file "$THEME_DIR/bin/assign-page-templates.php" --path="$WP_DIR" --allow-root || { echo "ERROR: Page template assignment failed."; exit 1; }
else
    echo "ERROR: bin/assign-page-templates.php not found!"
    exit 1
fi

# 4. Navigation Menu Assignments, Footer Seeding & Sidebar Injection (Final Task)
echo "Assigning navigation menu locations..."
php7.4 $(which wp) eka assign-menus --path="$WP_DIR" --allow-root >> "$MENU_LOG" 2>&1

echo "Seeding footer navigation posts..."
php7.4 $(which wp) eka seed-footer-menus --path="$WP_DIR" --allow-root >> "$MENU_LOG" 2>&1

echo "Executing sidebar navigation menu injection (bin/inject-sidebar-menus.php)..."
# TODO: Sidebar menu assignment for parent/sub-pages is specified here, but implementation logic is pending in the next phase.
if [ -f "$THEME_DIR/bin/inject-sidebar-menus.php" ]; then
    php7.4 $(which wp) eval-file "$THEME_DIR/bin/inject-sidebar-menus.php" --path="$WP_DIR" --allow-root || { echo "ERROR: Sidebar menu injection failed."; exit 1; }
else
    echo "ERROR: bin/inject-sidebar-menus.php not found!"
    exit 1
fi

echo "Content transformation & navigation assignment pipeline completed successfully at $(date)!"
exit 0
