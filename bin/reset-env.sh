#!/bin/bash
# bin/reset-env.sh
# Staging Environment Reset & Resynchronization Script
# Preserves: ai-work/, bin/, AGY_INSTRUCTIONS.md, Master Project Roadmap...
# Targets: /var/www/backstage.ekalexandria.org (DB: backstage_eka)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
THEME_DIR="$STAGING_DIR/public/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
LOG_FILE="$LOG_DIR/reset-env.log"

mkdir -p "$LOG_DIR"
exec > >(tee -a "$LOG_FILE") 2>&1

echo "=========================================="
echo "Resetting Staging Environment: $(date)"
echo "Target DB: backstage_eka"
echo "=========================================="

# 1. Run Pre-flight Checks
if [ -x "$THEME_DIR/bin/pre-flight.sh" ]; then
    "$THEME_DIR/bin/pre-flight.sh" || { echo "ERROR: Pre-flight checks failed."; exit 1; }
else
    echo "Notice: pre-flight.sh not executable or missing, skipping."
fi

# 2. Database Export / Dump Resolution
PROD_DIR="/var/www/ekalexandria.org"
PROD_SQL="$STAGING_DIR/2026-06-21-224516-db207080_eka.sql"

if [ -d "$PROD_DIR/public" ]; then
    echo "Exporting live production database snapshot from $PROD_DIR..."
    cd "$PROD_DIR/public" || exit 1
    php7.4 $(which wp) db export /tmp/prod_db.sql --allow-root || exit 1
    DUMP_FILE="/tmp/prod_db.sql"
elif [ -f "$PROD_SQL" ]; then
    echo "Using local production SQL dump at $PROD_SQL..."
    DUMP_FILE="$PROD_SQL"
else
    echo "ERROR: Neither live production site nor SQL dump found."
    exit 1
fi

# 3. Import Fresh Snapshot into backstage_eka
echo "Importing clean database snapshot into backstage_eka..."
cd "$STAGING_DIR/public" || exit 1
php7.4 $(which wp) db import "$DUMP_FILE" --allow-root || { echo "ERROR: DB import failed."; exit 1; }

if [ "$DUMP_FILE" = "/tmp/prod_db.sql" ]; then
    rm -f /tmp/prod_db.sql
fi

# 4. Search-Replace Domain Mapping
echo "Performing DB domain mapping (ekalexandria.org -> backstage.ekalexandria.org)..."
php7.4 $(which wp) search-replace 'ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --skip-plugins --allow-root
php7.4 $(which wp) search-replace 'www.ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --skip-plugins --allow-root

# 5. Patch Known PHP 7.4/8.0+ Fatal Errors
VC_FILE="$STAGING_DIR/public/wp-content/plugins/js_composer/include/classes/editors/class-vc-frontend-editor.php"
if [ -f "$VC_FILE" ]; then
    echo "Patching WPBakery line 339 nested ternary operator error..."
    sed -i 's/\$mode === \$key \? '\'' vc_active'\'' : \$key === '\''default'\'' \&\& \$mode \!== '\''desktop'\'' \? '\'\'': '\'' vc_st_hidden'\''/((\$mode === \$key) ? '\'' vc_active'\'' : ((\$key === '\''default'\'' \&\& \$mode \!== '\''desktop'\'') ? '\'\'': '\'' vc_st_hidden'\''))/g' "$VC_FILE"
fi

# 6. Purge Vendor Autoload Caches
echo "Resolving Mailchimp vendor autoloader errors..."
if [ -d "$STAGING_DIR/public/wp-content/plugins/mailchimp-for-woocommerce/vendor" ]; then
    rm -rf "$STAGING_DIR/public/wp-content/plugins/mailchimp-for-woocommerce/vendor"
fi

# 7. Activate Theme & Verify WP-CLI
echo "Activating ekalexandria-flagship theme..."
php7.4 $(which wp) theme activate ekalexandria-flagship --skip-plugins --allow-root || echo "WARNING: Theme activation warning."

echo "Verifying WP-CLI connection post-reset..."
php7.4 $(which wp) core version --skip-plugins --allow-root || { echo "ERROR: WP-CLI verification failed."; exit 1; }

echo "Environment reset complete successfully at $(date)!"
exit 0
