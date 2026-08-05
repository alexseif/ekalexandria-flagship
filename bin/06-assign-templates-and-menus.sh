#!/bin/bash
# bin/06-assign-templates-and-menus.sh
# Stage 06: Transient/Cache Invalidation & Page Template / Menu Assignment Orchestrator
# Targets: /var/www/backstage.ekalexandria.org (DB: backstage_eka)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/06-assign-templates-and-menus.log"

mkdir -p "$LOG_DIR"
> "$MAIN_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

echo "=========================================="
echo "Starting Stage 06: Template & Menu Assignments: $(date)"
echo "Target WP Path: $WP_DIR"
echo "=========================================="

run_eval_script() {
    local script_name="$1"
    local full_path="$THEME_DIR/bin/$script_name"
    if [ ! -f "$full_path" ]; then
        echo "ERROR: $full_path not found!"
        exit 1
    fi
    php7.4 $(which wp) eval-file "$full_path" --path="$WP_DIR" || { echo "ERROR: $script_name execution failed."; exit 1; }
}

# 1. Transient Clean-Up & Cache Flush
echo "Flushing transient cache and object cache..."
cd "$WP_DIR" || exit 1
php7.4 $(which wp) transient delete --all --path="$WP_DIR"
php7.4 $(which wp) cache flush --path="$WP_DIR"

# 2. Page Template Assignments
echo "Assigning FSE Page Templates (bin/assign-page-templates.php)..."
run_eval_script "assign-page-templates.php"

# 3. Menu Assignments
echo "Assigning navigation menu locations (bin/assign-menus.php)..."
run_eval_script "assign-menus.php"

# 4. Footer & Sidebar Menus
echo "Seeding footer navigation posts (bin/seed-footer-menus.php)..."
run_eval_script "seed-footer-menus.php"

echo "Executing sidebar navigation menu injection (bin/inject-sidebar-menus.php)..."
run_eval_script "inject-sidebar-menus.php"

echo "Stage 06 template & menu assignment completed successfully at $(date)!"
exit 0
