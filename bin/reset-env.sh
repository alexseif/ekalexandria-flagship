#!/bin/bash
# bin/reset-env.sh

echo "Resetting Environment..."

PROD_DIR="/var/www/ekalexandria.org"
STAGING_DIR="/var/www/backstage.ekalexandria.org"
THEME_DIR="$STAGING_DIR/public/wp-content/themes/ekalexandria-flagship"

cd "$THEME_DIR" || exit 1

if [ -x "bin/pre-flight.sh" ]; then
    ./bin/pre-flight.sh || exit 1
else
    echo "ERROR: pre-flight.sh not found or not executable."
    exit 1
fi

echo "Syncing plugins from production..."
rsync -a --delete "$PROD_DIR/public/wp-content/plugins/" "$STAGING_DIR/public/wp-content/plugins/"

echo "Exporting production database..."
cd "$PROD_DIR/public" || exit 1
php7.4 $(which wp) db export /tmp/prod_db.sql --allow-root || exit 1

echo "Importing database to staging..."
cd "$STAGING_DIR/public" || exit 1
php7.4 $(which wp) db import /tmp/prod_db.sql --allow-root || exit 1
rm /tmp/prod_db.sql

echo "Performing DB domain mapping..."
php7.4 $(which wp) search-replace --skip-plugins 'ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --allow-root
php7.4 $(which wp) search-replace --skip-plugins 'www.ekalexandria.org' 'backstage.ekalexandria.org' --all-tables --allow-root

echo "Activating flagship theme..."
php7.4 $(which wp) theme activate --skip-plugins ekalexandria-flagship --allow-root

echo "Environment reset complete!"
exit 0
