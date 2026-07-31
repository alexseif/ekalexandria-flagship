#!/bin/bash
# bin/reset-env.sh

STAGING_DIR="/var/www/backstage.ekalexandria.org"
THEME_DIR="$STAGING_DIR/public/wp-content/themes/ekalexandria-flagship"
LOG_DIR="$THEME_DIR/ai-work/logs"
LOG_FILE="$LOG_DIR/reset-env.log"

mkdir -p "$LOG_DIR"

exec > >(tee -a "$LOG_FILE") 2>&1

echo "=========================================="
echo "Resetting Staging Environment: $(date)"
echo "=========================================="

cd "$THEME_DIR" || { echo "ERROR: Theme directory not found."; exit 1; }

if [ -x "bin/pre-flight.sh" ]; then
    ./bin/pre-flight.sh || { echo "ERROR: Pre-flight checks failed."; exit 1; }
else
    echo "ERROR: pre-flight.sh not found or not executable."
    exit 1
fi

PROD_DIR="/var/www/ekalexandria.org"
PROD_SQL="/var/www/backstage.ekalexandria.org/2026-06-21-224516-db207080_eka.sql"

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

echo "Importing clean database snapshot to staging..."
cd "$STAGING_DIR/public" || exit 1
php7.4 $(which wp) db import "$DUMP_FILE" --allow-root || { echo "ERROR: DB import failed."; exit 1; }

if [ "$DUMP_FILE" = "/tmp/prod_db.sql" ]; then
    rm -f /tmp/prod_db.sql
fi

echo "Performing DB domain mapping and search-replace..."
php7.4 $(which wp) search-replace 'ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --skip-plugins --allow-root
php7.4 $(which wp) search-replace 'www.ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --skip-plugins --allow-root

echo "Activating ekalexandria-flagship theme..."
php7.4 $(which wp) theme activate ekalexandria-flagship --skip-plugins --allow-root || echo "WARNING: Theme activation warning."

echo "Resolving Mailchimp vendor autoloader errors..."
if [ -d "$STAGING_DIR/public/wp-content/plugins/mailchimp-for-woocommerce/vendor" ]; then
    echo "Fixing Mailchimp WooCommerce autoloader cache..."
    rm -rf "$STAGING_DIR/public/wp-content/plugins/mailchimp-for-woocommerce/vendor"
fi

echo "Verifying WP-CLI connection post-reset..."
php7.4 $(which wp) core version --skip-plugins --allow-root || { echo "ERROR: WP-CLI verification failed."; exit 1; }

echo "Environment reset complete successfully at $(date)!"
exit 0
