#!/bin/bash
# bin/run-phase4-migration.sh
# Phase 4 Theme Activation, Plugin Cleanup, Slider Replacement, Shortcode Remediation, CPT Migration & Menu Assignment Runner

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
WP_DIR="$(cd "$THEME_DIR/../../.." && pwd)"
LOG_DIR="$THEME_DIR/ai-work/logs"
MASTER_LOG="$LOG_DIR/phase4-unified-migration.log"

mkdir -p "$LOG_DIR"

# 1. Startup Log Purge / Initialization
> "$MASTER_LOG"
> "$LOG_DIR/cleanup-plugins.log"
> "$LOG_DIR/sliders-migration.log"
> "$LOG_DIR/remediate-shortcodes.log"
> "$LOG_DIR/tachydromos-migration.log"
> "$LOG_DIR/board-migration.log"
> "$LOG_DIR/menu-assignments.log"

exec > >(tee -a "$MASTER_LOG") 2>&1

echo "=========================================="
echo "Starting Phase 4 Migration: $(date)"
echo "Target WordPress Dir: $WP_DIR"
echo "=========================================="

cd "$WP_DIR" || exit 1

WP_CLI="php7.4 $(which wp)"

# 2. Activate Flagship Theme
echo "[1/7] Activating ekalexandria-flagship theme..."
$WP_CLI theme activate ekalexandria-flagship --path="$WP_DIR" --allow-root
if [ $? -eq 0 ]; then
    echo "  -> Theme ekalexandria-flagship activated successfully."
else
    echo "  -> ERROR: Failed to activate ekalexandria-flagship theme."
fi

# 3. Legacy Plugin & Cache Cleanup Script Execution
echo "[2/7] Executing Legacy Plugin Cleanup & Caching Drop-in Purge..."
if [ -f "$SCRIPT_DIR/cleanup-plugins.sh" ]; then
    bash "$SCRIPT_DIR/cleanup-plugins.sh" >> "$LOG_DIR/cleanup-plugins.log" 2>&1
    if [ $? -eq 0 ]; then
        echo "  -> Legacy Plugin Cleanup completed successfully."
    else
        echo "  -> WARNING: Plugin cleanup completed with logged issues. Check $LOG_DIR/cleanup-plugins.log"
    fi
else
    echo "  -> WARNING: $SCRIPT_DIR/cleanup-plugins.sh not found. Skipping plugin cleanup step."
fi

# 4. Dynamic & Static Slider Replacement
echo "[3/7] Executing Slider Replacement (wp eka replace-sliders)..."
$WP_CLI eka replace-sliders --path="$WP_DIR" --allow-root >> "$LOG_DIR/sliders-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Slider replacement completed successfully."
else
    echo "  -> ERROR: Slider replacement encountered issues. Check $LOG_DIR/sliders-migration.log"
fi

# 5. Shortcode & MFN Gutenberg Remediation
echo "[4/7] Executing Shortcode & MFN Gutenberg Remediation (wp eka remediate-shortcodes)..."
$WP_CLI eka remediate-shortcodes --path="$WP_DIR" --allow-root >> "$LOG_DIR/remediate-shortcodes.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Shortcode & MFN Gutenberg remediation completed successfully."
else
    echo "  -> ERROR: Shortcode & MFN Gutenberg remediation encountered issues. Check $LOG_DIR/remediate-shortcodes.log"
fi

# 6. Alexandrinos Tachydromos CPT Migration
echo "[5/7] Executing Alexandrinos Tachydromos migration..."
$WP_CLI eka migrate-tachydromos --path="$WP_DIR" --allow-root >> "$LOG_DIR/tachydromos-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Alexandrinos Tachydromos migration completed successfully."
else
    echo "  -> ERROR: Alexandrinos Tachydromos migration encountered issues. Check $LOG_DIR/tachydromos-migration.log"
fi

# 7. Board Members CPT Migration & Polylang Linking
echo "[6/7] Executing Board Members migration..."
$WP_CLI eka migrate-board --path="$WP_DIR" --allow-root >> "$LOG_DIR/board-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Board Members migration completed successfully."
else
    echo "  -> ERROR: Board Members migration encountered issues. Check $LOG_DIR/board-migration.log"
fi

# 8. Navigation Menu Location Assignments
echo "[7/7] Executing Navigation Menu Location Assignments..."
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
echo "Phase 4 Migration Pass Finished: $(date)"
echo "=========================================="
exit 0

