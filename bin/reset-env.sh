#!/bin/bash
# bin/reset-env.sh
# Staging Environment Reset & Resynchronization Script
# Preserves: wp-config.php, ai-work/, bin/, AGY_INSTRUCTIONS.md, Master Project Roadmap...
# Targets: /var/www/backstage.ekalexandria.org (DB: backstage_eka)

STAGING_DIR="/var/www/backstage.ekalexandria.org"
THEME_DIR="$STAGING_DIR/public/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
LOG_FILE="$LOG_DIR/reset-env.log"

mkdir -p "$LOG_DIR"
> "$LOG_FILE"
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

# 2. Database Export via WP-CLI / MySQL from Live Production
PROD_DIR="/var/www/ekalexandria.org"
DUMP_FILE="/tmp/prod_db.sql"

if [ -d "$PROD_DIR/public" ]; then
    echo "Exporting live production database snapshot from $PROD_DIR/public..."
    cd "$PROD_DIR/public" || exit 1
    php7.4 $(which wp) db export "$DUMP_FILE" --skip-plugins --allow-root || { echo "ERROR: Live DB export failed."; exit 1; }
else
    echo "ERROR: Production directory $PROD_DIR/public not found. Live DB export aborted."
    exit 1
fi

# 3. Drop, Recreate & Import Fresh Snapshot into backstage_eka
echo "Dropping residual database backstage_eka..."
cd "$STAGING_DIR/public" || exit 1
php7.4 $(which wp) db drop --yes --skip-plugins --allow-root || echo "Notice: Database drop returned warning/non-zero."

echo "Recreating clean database backstage_eka..."
php7.4 $(which wp) db create --skip-plugins --allow-root || { echo "ERROR: DB creation failed."; exit 1; }

echo "Importing clean database snapshot into backstage_eka..."
php7.4 $(which wp) db import "$DUMP_FILE" --skip-plugins --allow-root || { echo "ERROR: DB import failed."; exit 1; }
rm -f "$DUMP_FILE"

# 4. Synchronize Staging Files with Preservation Rules
if [ -d "$PROD_DIR/public" ]; then
    echo "Synchronizing staging files from production baseline ($PROD_DIR/public)..."
    rsync -av --delete \
        --exclude='wp-config.php' \
        --exclude='wp-content/plugins/mailchimp/vendor' \
        --exclude='wp-content/plugins/mailchimp-for-woocommerce/vendor' \
        --exclude='wp-content/themes/ekalexandria-flagship/ai-work' \
        --exclude='wp-content/themes/ekalexandria-flagship/bin' \
        --exclude='wp-content/themes/ekalexandria-flagship/AGY_INSTRUCTIONS.md' \
        --exclude='wp-content/themes/ekalexandria-flagship/Master Project Roadmap*' \
        --exclude='wp-content/themes/ekalexandria-flagship/.git' \
        --exclude='wp-content/themes/ekalexandria-flagship/.gitignore' \
        "$PROD_DIR/public/" "$STAGING_DIR/public/"
fi


# 5. Fix Staging File Permissions
echo "Setting file permissions ownership to alexseif:www-data..."
chown -R alexseif:www-data "$STAGING_DIR"
chmod -R u+w "$STAGING_DIR/public/wp-content"

# 6. Search-Replace Domain Mapping
echo "Performing DB domain mapping (ekalexandria.org -> backstage.ekalexandria.org)..."
cd "$STAGING_DIR/public" || exit 1
php7.4 $(which wp) search-replace 'ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --skip-plugins --allow-root
php7.4 $(which wp) search-replace 'www.ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --skip-plugins --allow-root

# 7. Patch Known PHP 7.4/8.0+ Fatal Errors
VC_FILE="$STAGING_DIR/public/wp-content/plugins/js_composer/include/classes/editors/class-vc-frontend-editor.php"
if [ -f "$VC_FILE" ]; then
    echo "Patching WPBakery line 339 nested ternary operator error..."
    sed -i 's/\$mode === \$key \? '\'' vc_active'\'' : \$key === '\''default'\'' \&\& \$mode \!== '\''desktop'\'' \? '\'\'': '\'' vc_st_hidden'\''/((\$mode === \$key) ? '\'' vc_active'\'' : ((\$key === '\''default'\'' \&\& \$mode \!== '\''desktop'\'') ? '\'\'': '\'' vc_st_hidden'\''))/g' "$VC_FILE"
fi

# 8. Purge Vendor Autoload Caches & Fix Autoloaders
echo "Resolving Mailchimp vendor autoloader errors..."
if [ -d "$STAGING_DIR/public/wp-content/plugins/mailchimp/vendor" ]; then
    rm -rf "$STAGING_DIR/public/wp-content/plugins/mailchimp/vendor"
fi
if [ -d "$STAGING_DIR/public/wp-content/plugins/mailchimp-for-woocommerce/vendor" ]; then
    rm -rf "$STAGING_DIR/public/wp-content/plugins/mailchimp-for-woocommerce/vendor"
fi

POLYLANG_STATIC="$STAGING_DIR/public/wp-content/plugins/polylang/vendor/composer/autoload_static.php"
if [ -f "$POLYLANG_STATIC" ]; then
    echo "Patching Polylang static autoloader class hash..."
    sed -i 's/ComposerStaticInited5bec60c42d525a1c1222212c9f9cff/ComposerStaticInit8f862f0d8b75b7170c1f5eb4256b99b4/g' "$POLYLANG_STATIC"
fi

# 9. Verify WP-CLI Connection
echo "Verifying WP-CLI connection post-reset..."
php7.4 $(which wp) core version --skip-plugins --allow-root || { echo "ERROR: WP-CLI verification failed."; exit 1; }

echo "Environment reset complete successfully at $(date)!"
exit 0
