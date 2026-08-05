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

run_eval_script() {
    local script_name="$1"
    local full_path="$THEME_DIR/bin/$script_name"
    if [ ! -f "$full_path" ]; then
        echo "ERROR: $full_path not found!"
        exit 1
    fi
    php7.4 $(which wp) eval-file "$full_path" --path="$WP_DIR" --allow-root || { echo "ERROR: $script_name execution failed."; exit 1; }
}

# 1. Execute Content Transformation Engine (Steps 3A - 3F)
echo "Executing Content Transformation Engine (bin/migration-content-engine.php)..."
run_eval_script "migration-content-engine.php"

# 2. Transient Clean-Up (Step 3G)
echo "Flushing transient cache..."
cd "$WP_DIR" || exit 1
php7.4 $(which wp) transient delete --all --path="$WP_DIR" --allow-root

# 3. Page Template Assignments (Step 3H)
echo "Assigning FSE Page Templates (bin/assign-page-templates.php)..."
run_eval_script "assign-page-templates.php"

# 4. Navigation Menu Assignments, Footer Seeding & Sidebar Injection (Final Task)
echo "Assigning navigation menu locations..."
php7.4 $(which wp) eka assign-menus --path="$WP_DIR" --allow-root >> "$MENU_LOG" 2>&1

echo "Seeding footer navigation posts..."
php7.4 $(which wp) eka seed-footer-menus --path="$WP_DIR" --allow-root >> "$MENU_LOG" 2>&1

echo "Executing sidebar navigation menu injection (bin/inject-sidebar-menus.php)..."
# TODO: Sidebar menu assignment for parent/sub-pages is specified here, but implementation logic is pending in the next phase.
run_eval_script "inject-sidebar-menus.php"

echo "Content transformation & navigation assignment pipeline completed successfully at $(date)!"
exit 0
