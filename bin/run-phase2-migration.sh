#!/bin/bash
# bin/run-phase2-migration.sh
# Phase 2 Programmatic CPT Content, Shortcode & Menu Migration Unified Runner

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
STAGING_DIR="$(cd "$THEME_DIR/../../.." && pwd)"
WP_DIR="$STAGING_DIR/public"
LOG_DIR="$THEME_DIR/ai-work/logs"
MASTER_LOG="$LOG_DIR/phase2-unified-migration.log"

mkdir -p "$LOG_DIR"

# 1. Mandatory Startup Log Purge / Initialization
> "$MASTER_LOG"
> "$LOG_DIR/tachydromos-migration.log"
> "$LOG_DIR/board-migration.log"
> "$LOG_DIR/sliders-migration.log"
> "$LOG_DIR/remediate-shortcodes.log"
> "$LOG_DIR/menu-assignments.log"

exec > >(tee -a "$MASTER_LOG") 2>&1

echo "=========================================="
echo "Starting Phase 2 Migration: $(date)"
echo "Target WordPress Dir: $WP_DIR"
echo "=========================================="

WP_CLI="php7.4 $(which wp)"

# 2. Alexandrinos Tachydromos CPT Migration
echo "[1/5] Executing Alexandrinos Tachydromos migration..."
$WP_CLI eka migrate-tachydromos --path="$WP_DIR" --allow-root >> "$LOG_DIR/tachydromos-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Alexandrinos Tachydromos migration completed successfully."
else
    echo "  -> ERROR: Alexandrinos Tachydromos migration encountered issues. Check $LOG_DIR/tachydromos-migration.log"
fi

# 3. Board Members CPT Migration & Polylang Linking
echo "[2/5] Executing Board Members migration..."
$WP_CLI eka migrate-board --path="$WP_DIR" --allow-root >> "$LOG_DIR/board-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Board Members migration completed successfully."
else
    echo "  -> ERROR: Board Members migration encountered issues. Check $LOG_DIR/board-migration.log"
fi

# 4. Slider Replacement with Native Gutenberg Blocks
echo "[3/5] Executing Slider Replacement..."
$WP_CLI eka replace-sliders --path="$WP_DIR" --allow-root >> "$LOG_DIR/sliders-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Slider Replacement completed successfully."
else
    echo "  -> ERROR: Slider Replacement encountered issues. Check $LOG_DIR/sliders-migration.log"
fi

# 5. Shortcode Remediation & Sub-navigation
echo "[4/5] Executing Shortcode Remediation & Sub-navigation Injections..."
$WP_CLI eka remediate-shortcodes --path="$WP_DIR" --allow-root >> "$LOG_DIR/remediate-shortcodes.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Shortcode Remediation completed successfully."
else
    echo "  -> ERROR: Shortcode Remediation encountered issues. Check $LOG_DIR/remediate-shortcodes.log"
fi

# 6. Navigation Menu Location Assignments
echo "[5/5] Executing Navigation Menu Location Assignments..."
{
    echo "Assigning Greek Main Menu (13 -> main-menu)..."
    $WP_CLI menu location assign 13 main-menu --path="$WP_DIR" --allow-root
    echo "Assigning English Main Menu (3315 -> main-menu___en)..."
    $WP_CLI menu location assign 3315 main-menu___en --path="$WP_DIR" --allow-root
    echo "Assigning Arabic Main Menu (3316 -> main-menu___ar)..."
    $WP_CLI menu location assign 3316 main-menu___ar --path="$WP_DIR" --allow-root
    echo "Assigning Greek Footer Menu (21 -> social-menu-bottom)..."
    $WP_CLI menu location assign 21 social-menu-bottom --path="$WP_DIR" --allow-root
} >> "$LOG_DIR/menu-assignments.log" 2>&1

if [ $? -eq 0 ]; then
    echo "  -> Menu Location Assignments completed successfully."
else
    echo "  -> ERROR: Menu Location Assignments encountered issues. Check $LOG_DIR/menu-assignments.log"
fi

echo "=========================================="
echo "Phase 2 Migration Pass Finished: $(date)"
echo "=========================================="
exit 0
