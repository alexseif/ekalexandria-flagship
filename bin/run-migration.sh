#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Orchestration Runner Script: Shortcode & Classic Block Gutenberg Migration
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(dirname "$SCRIPT_DIR")"

echo "======================================================================"
echo " Starting 2-Stage Gutenberg Migration Pipeline"
echo " Working Directory: $THEME_DIR"
echo " Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================================================"

cd "$THEME_DIR"

echo ""
echo "--> Step 1/3: Running FSE Inline Style Sanitizer & Helper Unit Tests..."
php bin/test-fse-sanitizer.php
echo "✓ Helper Unit Tests Passed."

echo ""
echo "--> Step 2/3: Running Stage 1 Shortcode & WPBakery Transformer Script..."
php bin/remediate-shortcodes-to-blocks.php
echo "✓ Stage 1 Shortcode Transformation Completed."

echo ""
echo "--> Step 3/3: Running Stage 2 Idempotent Classic HTML Converter Script..."
php bin/convert-classic-to-gutenberg.php
echo "✓ Stage 2 Classic HTML Conversion Completed."

echo ""
echo "======================================================================"
echo " 2-Stage Gutenberg Migration Pipeline Completed Successfully!"
echo " Log files available in: $THEME_DIR/ai-work/logs/"
echo "======================================================================"
