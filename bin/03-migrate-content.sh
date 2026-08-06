#!/bin/bash
# bin/03-migrate-content.sh
# Surgical content migration orchestrator for debugging and controlled re-runs.
# Targets: /var/www/backstage.ekalexandria.org (DB: extracted from environment/wp-config.php)

set -euo pipefail

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/03-migrate-content.log"

mkdir -p "$LOG_DIR"
: > "$MAIN_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

usage() {
    cat <<'EOF'
Usage: ./03-migrate-content.sh [--step 3|4|5] [--step 3 ...] [--help]

Runs the content-specific migration stages independently so each can be debugged in isolation.
- Without flags, runs steps 3, 4, and 5 in order.
- Use --step 3 to run only the surgical page migration stage.
- Use --step 4 to run only the shortcode remediation stage.
- Use --step 5 to run only the classic editor / HTML conversion stage.
EOF
}

run_eval_script() {
    local script_name="$1"
    local full_path="$THEME_DIR/bin/$script_name"
    if [ ! -f "$full_path" ]; then
        echo "ERROR: $full_path not found!"
        exit 1
    fi

    echo "Running $script_name"
    php7.4 "$(which wp)" eval-file "$full_path" --path="$WP_DIR"
}

run_stage() {
    local step="$1"
    local step_log="$LOG_DIR/03-migrate-content-step-${step}.log"

    : > "$step_log"
    echo "=========================================="
    echo "Starting Step ${step}: $(date)"
    echo "=========================================="

    case "$step" in
        3)
            echo "[Step 03] Executing surgical page migrations (bin/03-surgical-migrations.php)..."
            if ! run_eval_script "03-surgical-migrations.php" 2>&1 | tee -a "$step_log"; then
                echo "ERROR: Step 03 failed."
                exit 1
            fi
            ;;
        4)
            echo "[Step 04] Executing shortcode migrations (bin/04-shortcode-migrations.php)..."
            if ! run_eval_script "04-shortcode-migrations.php" 2>&1 | tee -a "$step_log"; then
                echo "ERROR: Step 04 failed."
                exit 1
            fi
            ;;
        5)
            echo "[Step 05] Executing classic editor / HTML conversions (bin/05-classic-editor-migrations.php)..."
            if ! run_eval_script "05-classic-editor-migrations.php" 2>&1 | tee -a "$step_log"; then
                echo "ERROR: Step 05 failed."
                exit 1
            fi
            ;;
        *)
            echo "ERROR: Unsupported step '$step'."
            usage
            exit 1
            ;;
    esac

    echo "Step ${step} completed successfully."
}

SELECTED_STEPS=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        --step)
            if [[ $# -lt 2 ]]; then
                echo "ERROR: --step requires a value."
                usage
                exit 1
            fi
            SELECTED_STEPS+=("$2")
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            echo "ERROR: Unknown argument '$1'."
            usage
            exit 1
            ;;
    esac
done

if [ ${#SELECTED_STEPS[@]} -eq 0 ]; then
    SELECTED_STEPS=(3 4 5)
fi

echo "=========================================="
echo "Starting surgical migration pipeline: $(date)"
echo "Target WP Path: $WP_DIR"
echo "=========================================="

for step in "${SELECTED_STEPS[@]}"; do
    run_stage "$step"
done

if [ -f "$LOG_DIR/missed-shortcodes.log" ]; then
    echo ""
    echo "=========================================="
    echo "MISSED SHORTCODES REPORT SUMMARY"
    echo "=========================================="
    echo "Detailed log saved to: $LOG_DIR/missed-shortcodes.log"
    echo "JSON report saved to: $LOG_DIR/missed-shortcodes.json"
    echo "------------------------------------------"
    head -n 20 "$LOG_DIR/missed-shortcodes.log"
    echo "=========================================="
fi

echo "Surgical migration pipeline completed successfully at $(date)!"
exit 0
