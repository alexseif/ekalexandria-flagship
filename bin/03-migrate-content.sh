#!/bin/bash
# bin/03-migrate-content.sh
# Master Content Transformation & Navigation Assignment Pipeline
# Targets: /var/www/backstage.ekalexandria.org (DB: extracted from environment/wp-config.php)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/03-migrate-content.log"

mkdir -p "$LOG_DIR"
> "$MAIN_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

echo "=========================================="
echo "Starting Master Migration Content Pipeline: $(date)"
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

# Step 03: Surgical Migrations
echo "[Step 03] Executing Surgical Page Migrations (bin/03-surgical-migrations.php)..."
run_eval_script "03-surgical-migrations.php"

# Step 04: Shortcode Migrations
echo "[Step 04] Executing Shortcode Migrations (bin/04-shortcode-migrations.php)..."
run_eval_script "04-shortcode-migrations.php"

# Step 05: Classic Editor & CSS Sanitizer Migrations
echo "[Step 05] Executing Classic Editor & CSS Sanitizer Migrations (bin/05-classic-editor-migrations.php)..."
run_eval_script "05-classic-editor-migrations.php"

# Step 06: Template & Menu Assignments
echo "[Step 06] Executing Template & Menu Assignments (bin/06-assign-templates-and-menus.sh)..."
bash "$THEME_DIR/bin/06-assign-templates-and-menus.sh" || { echo "ERROR: 06-assign-templates-and-menus.sh execution failed."; exit 1; }

echo "Master migration content pipeline completed successfully at $(date)!"
exit 0
