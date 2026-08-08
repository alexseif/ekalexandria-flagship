#!/bin/bash
# bin/03-assign-templates.sh
# Stage 03: Transient/Cache Invalidation & Page Template Assignment Orchestrator
# Targets: /var/www/backstage.ekalexandria.org (DB: backstage_eka)

set -euo pipefail

STAGING_DIR="/var/www/backstage.ekalexandria.org"
WP_DIR="$STAGING_DIR/public"
THEME_DIR="$WP_DIR/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
MAIN_LOG="$LOG_DIR/03-assign-templates.log"

mkdir -p "$LOG_DIR"
: > "$MAIN_LOG"

exec > >(tee -a "$MAIN_LOG") 2>&1

echo "=========================================="
echo "Starting Stage 03: Template Assignments: $(date)"
echo "Target WP Path: $WP_DIR"
echo "=========================================="

# 1. Transient Clean-Up & Cache Flush
echo "Flushing transient cache and object cache..."
cd "$WP_DIR" || exit 1
php7.4 "$(which wp)" transient delete --all --path="$WP_DIR"
php7.4 "$(which wp)" cache flush --path="$WP_DIR"

# 2. Page Template Assignments
echo "Assigning FSE Page Templates (bin/assign-page-templates.php)..."
if [ -f "$THEME_DIR/bin/assign-page-templates.php" ]; then
    php7.4 "$(which wp)" eval-file "$THEME_DIR/bin/assign-page-templates.php" --path="$WP_DIR"
else
    echo "ERROR: $THEME_DIR/bin/assign-page-templates.php not found!"
    exit 1
fi

echo "Stage 03 template assignment completed successfully at $(date)!"
exit 0
