#!/bin/bash

# Deploy script for Flux Media Optimizer WordPress plugin
# Commits trunk to SVN and creates version tags
# Requires: wporg/trunk/ to be built first using build-plugin.sh
# Note: wporg/ is the SVN repo root directory

set -e

# Get the plugin directory (parent of bin directory)
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_NAME="flux-media-optimizer"
WPORG_DIR="$PLUGIN_DIR/wporg"
TRUNK_DIR="$WPORG_DIR/trunk"
SVN_REPO_URL="https://plugins.svn.wordpress.org/$PLUGIN_NAME"

# Function to extract version from plugin file header
extract_plugin_header_version() {
    local plugin_file=$1
    if [ -f "$plugin_file" ]; then
        grep "Version:" "$plugin_file" | sed 's/.*Version:[[:space:]]*//' | tr -d '\r\n' | tr -d ' ' | head -1
    fi
}

# Function to extract version from PHP constant
extract_php_constant_version() {
    local plugin_file=$1
    if [ -f "$plugin_file" ]; then
        grep "FLUX_MEDIA_OPTIMIZER_VERSION" "$plugin_file" | sed -n "s/.*'FLUX_MEDIA_OPTIMIZER_VERSION',[[:space:]]*'\([^']*\)'.*/\1/p" | head -1
    fi
}

# Function to validate version format (semver: x.y.z)
validate_version() {
    local version=$1
    if [[ $version =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.-]+)?(\+[a-zA-Z0-9.-]+)?$ ]]; then
        return 0
    else
        return 1
    fi
}

# Check if trunk directory exists
if [ ! -d "$TRUNK_DIR" ]; then
    echo "❌ Error: Trunk directory not found: $TRUNK_DIR"
    echo "   Please run ./bin/build-plugin.sh first to build the plugin."
    exit 1
fi

# Check if plugin file exists in trunk
PLUGIN_FILE="$TRUNK_DIR/flux-media-optimizer.php"
if [ ! -f "$PLUGIN_FILE" ]; then
    echo "❌ Error: Plugin file not found in trunk: $PLUGIN_FILE"
    echo "   Please run ./bin/build-plugin.sh first to build the plugin."
    exit 1
fi

# Extract version from trunk plugin file
TRUNK_VERSION=$(extract_plugin_header_version "$PLUGIN_FILE")
if [ -z "$TRUNK_VERSION" ]; then
    TRUNK_VERSION=$(extract_php_constant_version "$PLUGIN_FILE")
fi

if [ -z "$TRUNK_VERSION" ]; then
    echo "❌ Error: Could not determine version from plugin file in trunk."
    exit 1
fi

# Display version information
echo "📋 Version Information:"
echo "   Trunk Version: $TRUNK_VERSION"
echo ""

# Check if SVN is available
if ! command -v svn &> /dev/null; then
    echo "❌ Error: SVN is not installed or not in PATH."
    echo "   Please install Subversion to use this script."
    exit 1
fi

# Check if SVN repo is checked out (wporg/ is the SVN repo root)
if [ ! -d "$WPORG_DIR/.svn" ]; then
    echo "❌ Error: SVN repository not found: $WPORG_DIR"
    echo "   Please run ./bin/build-plugin.sh first to build and checkout the SVN repository."
    exit 1
fi

# Display deployment options
echo ""
echo "🚀 Deployment Options:"
echo "   1) Update trunk only (for development/continuous updates)"
echo "   2) Create/update tag: $TRUNK_VERSION (for versioned release)"
echo "   3) Both: Update trunk and create tag"
read -p "   Select option (1, 2, or 3, default: 3): " DEPLOY_OPTION
DEPLOY_OPTION="${DEPLOY_OPTION:-3}"

if [[ ! "$DEPLOY_OPTION" =~ ^[123]$ ]]; then
    echo "❌ Invalid option. Must be 1, 2, or 3."
    exit 1
fi

# Validate version format
if ! validate_version "$TRUNK_VERSION"; then
    echo "⚠️  Warning: Version format may be invalid: $TRUNK_VERSION"
    echo "   Expected format: x.y.z or x.y.z-suffix"
    read -p "   Continue anyway? (y/N): " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo "❌ Deployment cancelled."
        exit 1
    fi
fi

# Check if tag already exists
TAG_DIR="tags/$TRUNK_VERSION"
TAG_EXISTS=false
cd "$WPORG_DIR"
# Check if tag exists in SVN (must check via SVN, not just local directory)
if svn info "$TAG_DIR" &> /dev/null; then
    TAG_EXISTS=true
fi
cd "$PLUGIN_DIR"

# Only show warning if tag actually exists and we're creating/updating a tag
if [ "$TAG_EXISTS" = true ] && [[ "$DEPLOY_OPTION" == "2" || "$DEPLOY_OPTION" == "3" ]]; then
    echo ""
    echo "⚠️  Warning: Tag $TRUNK_VERSION already exists in SVN."
    read -p "   Overwrite existing tag? (y/N): " OVERWRITE_TAG
    if [[ ! "$OVERWRITE_TAG" =~ ^[Yy]$ ]]; then
        echo "❌ Deployment cancelled."
        exit 1
    fi
fi

# Show what will be deployed
echo ""
echo "📋 Deployment Summary:"
echo "   Version: $TRUNK_VERSION"
if [[ "$DEPLOY_OPTION" == "1" ]]; then
    echo "   Action: Update trunk only"
elif [[ "$DEPLOY_OPTION" == "2" ]]; then
    echo "   Action: Create/update tag: tags/$TRUNK_VERSION"
    if [ "$TAG_EXISTS" = true ]; then
        echo "   Note: Existing tag will be overwritten"
    fi
else
    echo "   Action: Update trunk and create tag: tags/$TRUNK_VERSION"
    if [ "$TAG_EXISTS" = true ]; then
        echo "   Note: Existing tag will be overwritten"
    fi
fi
echo "   SVN Repository: $SVN_REPO_URL"
echo ""

# Final confirmation
read -p "⚠️  Proceed with deployment? (yes/no): " FINAL_CONFIRM
if [[ ! "$FINAL_CONFIRM" == "yes" ]]; then
    echo "❌ Deployment cancelled."
    exit 1
fi

# Deploy to trunk
if [[ "$DEPLOY_OPTION" == "1" || "$DEPLOY_OPTION" == "3" ]]; then
    echo ""
    echo "📦 Committing trunk changes..."
    
    # Files are already in wporg/trunk/ from build script
    # Perform SVN operations from repo root to properly track all files including assets/
    cd "$WPORG_DIR"
    
    # Add new files to SVN (from repo root, specify trunk path)
    svn add --force trunk 2>/dev/null || true
    
    # Remove deleted files from SVN
    svn status trunk | grep '^!' | awk '{print $2}' | xargs svn rm 2>/dev/null || true
    
    # Show status (filter to show only trunk changes)
    echo "   SVN Status:"
    svn status trunk | head -20
    STATUS_COUNT=$(svn status trunk | wc -l)
    if [ "$STATUS_COUNT" -gt 20 ]; then
        echo "   ... (showing first 20 changes)"
    fi
    
    echo ""
    read -p "   Commit trunk changes? (Y/n): " COMMIT_TRUNK
    if [[ ! "$COMMIT_TRUNK" =~ ^[Nn]$ ]]; then
        svn commit -m "Update trunk to version $TRUNK_VERSION" trunk
        echo "   ✅ Trunk updated successfully!"
    else
        echo "   ⚠️  Trunk changes not committed."
    fi
fi

# Deploy to tag
if [[ "$DEPLOY_OPTION" == "2" || "$DEPLOY_OPTION" == "3" ]]; then
    echo ""
    echo "🏷️  Creating/updating tag: $TRUNK_VERSION"
    
    # Change to SVN repo root
    cd "$WPORG_DIR"
    
    TAG_DIR="tags/$TRUNK_VERSION"
    
    if [ "$TAG_EXISTS" = true ]; then
        # Remove existing tag directory
        svn rm "$TAG_DIR" -m "Remove existing tag $TRUNK_VERSION for update"
    fi
    
    # Copy trunk to tag
    echo "   Copying trunk to tags/$TRUNK_VERSION..."
    svn cp trunk "$TAG_DIR" -m "Tag version $TRUNK_VERSION"
    
    echo "   ✅ Tag created successfully!"
    echo ""
    echo "   Tag URL: $SVN_REPO_URL/tags/$TRUNK_VERSION/"
fi

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "📝 Next Steps:"
echo "   - Review the changes in: $WPORG_DIR"
echo "   - The plugin will be available on WordPress.org after SVN sync (usually within minutes)"
if [[ "$DEPLOY_OPTION" == "2" || "$DEPLOY_OPTION" == "3" ]]; then
    echo "   - Tag URL: $SVN_REPO_URL/tags/$TRUNK_VERSION/"
fi
echo ""

