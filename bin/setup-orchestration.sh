#!/bin/bash
# bin/setup-orchestration.sh

echo "Setting up orchestration layer..."

THEME_DIR="/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship"

cd "$THEME_DIR" || exit 1

if [ ! -f "package.json" ]; then
    echo "Initializing npm..."
    npm init -y
fi

echo "Installing dependencies: @wordpress/scripts, @playwright/test, @wordpress/block-serialization-default-parser..."
npm install @wordpress/scripts @playwright/test @wordpress/block-serialization-default-parser --save-dev

echo "Installing Playwright browsers..."
npx playwright install chromium

echo "Orchestration setup complete!"
