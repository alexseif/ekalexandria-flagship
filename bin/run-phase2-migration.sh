#!/bin/bash
# bin/run-phase2-migration.sh
# Phase 2 Programmatic Shortcode Remediation & Slider Replacement Unified Runner

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
WP_DIR="$(cd "$THEME_DIR/../../.." && pwd)"
LOG_DIR="$THEME_DIR/ai-work/logs"
MASTER_LOG="$LOG_DIR/phase2-unified-migration.log"

mkdir -p "$LOG_DIR"

# 1. Mandatory Startup Log Purge / Initialization
> "$MASTER_LOG"
> "$LOG_DIR/sliders-migration.log"
> "$LOG_DIR/remediate-shortcodes.log"

exec > >(tee -a "$MASTER_LOG") 2>&1

echo "=========================================="
echo "Starting Phase 2 Migration: $(date)"
echo "Target WordPress Dir: $WP_DIR"
echo "=========================================="

cd "$WP_DIR" || exit 1

WP_CLI="php7.4 $(which wp) --require=$THEME_DIR/inc/cli-commands.php"

# 2. Slider Replacement with Native Gutenberg Blocks
echo "[1/2] Executing Slider Replacement..."
$WP_CLI eka replace-sliders --path="$WP_DIR" --allow-root >> "$LOG_DIR/sliders-migration.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Slider Replacement completed successfully."
else
    echo "  -> ERROR: Slider Replacement encountered issues. Check $LOG_DIR/sliders-migration.log"
fi

# 3. Shortcode Remediation & Sub-navigation
echo "[2/2] Executing Shortcode Remediation & Sub-navigation Injections..."
$WP_CLI eka remediate-shortcodes --path="$WP_DIR" --allow-root >> "$LOG_DIR/remediate-shortcodes.log" 2>&1
if [ $? -eq 0 ]; then
    echo "  -> Shortcode Remediation completed successfully."
else
    echo "  -> ERROR: Shortcode Remediation encountered issues. Check $LOG_DIR/remediate-shortcodes.log"
fi

echo "=========================================="
echo "Phase 2 Migration Pass Finished: $(date)"
echo "=========================================="
exit 0
