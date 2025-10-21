#!/bin/bash

# Build script for Flux Media WordPress plugin
# Creates a zip file excluding development files and directories

set -e

# Get the plugin directory (parent of scripts directory)
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_NAME="flux-media"

# Try to extract version from main plugin file, fallback to timestamp
if [ -f "$PLUGIN_DIR/flux-media.php" ]; then
    VERSION=$(grep "Version:" "$PLUGIN_DIR/flux-media.php" | sed 's/.*Version:[[:space:]]*//' | tr -d '\r\n' | tr -d ' ')
else
    VERSION="$(date +%Y%m%d-%H%M%S)"
fi

# If version is empty or contains wildcards, use timestamp
if [ -z "$VERSION" ] || [[ "$VERSION" == *"*"* ]]; then
    VERSION="$(date +%Y%m%d-%H%M%S)"
fi

# Create build directory
BUILD_DIR="$PLUGIN_DIR"
mkdir -p "$BUILD_DIR"

# Create zip file name with version (no quotes)
ZIP_FILE="$BUILD_DIR/${PLUGIN_NAME}-v${VERSION}.zip"

# Remove existing zip if it exists
if [ -f "$ZIP_FILE" ]; then
    rm "$ZIP_FILE"
fi

# Change to plugin directory
cd "$PLUGIN_DIR"

# Install production-only dependencies
echo "🔧 Installing production-only dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets
echo "🏗️ Building frontend assets..."
npm run build

# Create temporary directory for WordPress.org structure
TEMP_BUILD_DIR="/tmp/flux-media-build-$$"
mkdir -p "$TEMP_BUILD_DIR/$PLUGIN_NAME"

# Copy all files to temp directory with proper structure
echo "📦 Creating WordPress.org compatible structure..."
cp -r . "$TEMP_BUILD_DIR/$PLUGIN_NAME/"

# Change to temp directory
cd "$TEMP_BUILD_DIR"

# Create zip with proper WordPress.org structure
echo "📦 Creating plugin zip file..."
zip -r "$ZIP_FILE" "$PLUGIN_NAME/" \
    -x "$PLUGIN_NAME/bin/*" \
    -x "$PLUGIN_NAME/node_modules/*" \
    -x "$PLUGIN_NAME/.git/*" \
    -x "$PLUGIN_NAME/.vscode/*" \
    -x "$PLUGIN_NAME/tests/*" \
    -x "$PLUGIN_NAME/.htaccess" \
    -x "$PLUGIN_NAME/.git*" \
    -x "$PLUGIN_NAME/.phpunit*" \
    -x "$PLUGIN_NAME/assets/js/src/*" \
    -x "$PLUGIN_NAME/assets/js/dist/*.html" \
    -x "$PLUGIN_NAME/assets/js/dist/*.LICENSE.txt" \
    -x "$PLUGIN_NAME/*.zip" \
    -x "$PLUGIN_NAME/*.log" \
    -x "$PLUGIN_NAME/*.xml" \
    -x "$PLUGIN_NAME/*.lock" \
    -x "$PLUGIN_NAME/.gitignore" \
    -x "$PLUGIN_NAME/package.json" \
    -x "$PLUGIN_NAME/package-lock.json" \
    -x "$PLUGIN_NAME/webpack.config.js" \
    -x "$PLUGIN_NAME/*.log" \
    -x "$PLUGIN_NAME/.DS_Store" \
    -x "$PLUGIN_NAME/Thumbs.db" \
    -x "$PLUGIN_NAME/*.phar" \
    -x "$PLUGIN_NAME/phpunit.xml" \
    -x "$PLUGIN_NAME/vendor-prefixed/plugins/*"

# Clean up temp directory
rm -rf "$TEMP_BUILD_DIR"

# Return to plugin directory for cleanup
cd "$PLUGIN_DIR"

# Restore full development environment
echo "🔄 Restoring development environment..."

composer install --optimize-autoloader --no-interaction

echo "✅ Plugin built successfully: $ZIP_FILE"
echo "📦 File size: $(du -h "$ZIP_FILE" | cut -f1)"
