#!/bin/bash

# EKA Portal Atomic Cutover Script
# This script will switch the active theme and run all migration WP-CLI commands

echo "Starting atomic cutover for EKA Portal..."

# Switch to the new FSE theme
wp theme activate ekalexandria-flagship

# Disable legacy plugins
echo "Disabling legacy plugins..."
wp plugin deactivate js_composer
wp plugin deactivate revslider
wp plugin deactivate w3-total-cache
# Add more plugins to deactivate as necessary

# Execute migration scripts
echo "Executing migrations..."
wp eka migrate-tachydromos
wp eka migrate-board-members
wp eka replace-sliders

# Flush cache
echo "Flushing cache..."
wp cache flush

echo "Cutover complete."
