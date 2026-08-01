#!/bin/bash
# bin/run-phase4-migration.sh
# Phase 4 Theme Activation, CPT Migration, Menu Assignment & Plugin Cleanup Runner

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
WP_DIR="$(cd "$THEME_DIR/../../.." && pwd)"
LOG_DIR="$THEME_DIR/ai-work/logs"
MASTER_LOG="$LOG_DIR/phase4-unified-migration.log"

mkdir -p "$LOG_DIR"

# 1. Startup Log Purge / Initialization
> "$MASTER_LOG"
> "$LOG_DIR/tachydromos-migration.log"
> "$LOG_DIR/board-migration.log"
> "$LOG_DIR/menu-assignments.log"
> "$LOG_DIR/cleanup-plugins.log"

exec > >(tee -a "$MASTER_LOG") 2>&1

echo "=========================================="
echo "Starting Phase 4 Migration: $(date)"
echo "Target WordPress Dir: $WP_DIR"
echo "=========================================="

cd "$WP_DIR" || exit 1

WP_CLI="php7.4 $(which wp)"

# 2. Activate Flagship Theme
echo "[1/5] Activating ekalexandria-flagship theme..."
$WP_CLI theme activate ekalexandria-flagship --path="$WP_DIR" --allow-root
if [ $? -eq 0 ]; then
    echo "  -> Theme ekalexandria-flagship activated successfully."
else
    echo "  -> ERROR: Failed to activate ekalexandria-flagship theme."
fi

# 3. Alexandrinos Tachydromos CPT Migration
echo "[2/5] Executing Alexandrinos Tachydromos migration..."
$WP_CLI eka migrate-tachydromos --path="$WP_DIR" --allow-root >> "$LOG_DIR/tachydromos-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Alexandrinos Tachydromos migration completed successfully."
else
    echo "  -> ERROR: Alexandrinos Tachydromos migration encountered issues. Check $LOG_DIR/tachydromos-migration.log"
fi

# 4. Board Members CPT Migration & Polylang Linking
echo "[3/5] Executing Board Members migration..."
$WP_CLI eka migrate-board --path="$WP_DIR" --allow-root >> "$LOG_DIR/board-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Board Members migration completed successfully."
else
    echo "  -> ERROR: Board Members migration encountered issues. Check $LOG_DIR/board-migration.log"
fi

# 5. Navigation Menu Location Assignments
echo "[4/5] Executing Navigation Menu Location Assignments..."
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

# 6. Legacy Plugin & Cache Cleanup Script Execution
echo "[5/5] Executing Legacy Plugin Cleanup & Caching Drop-in Purge..."
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

echo "=========================================="
echo "Phase 4 Migration Pass Finished: $(date)"
echo "=========================================="
exit 0
