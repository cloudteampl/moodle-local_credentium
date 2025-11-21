#!/bin/bash
#
# Credentium® Moodle Plugin Deployment Script
# Copyright © 2025 CloudTeam Sp. z o.o.
#
# This script packages the plugin for distribution to Moodle sites.
# Creates a properly structured ZIP file for Moodle plugin installation.
#

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Credentium® Moodle Plugin Deployment${NC}"
echo -e "${BLUE}Copyright © 2025 CloudTeam Sp. z o.o.${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Extract version from version.php
if [ ! -f "version.php" ]; then
    echo -e "${RED}Error: version.php not found!${NC}"
    exit 1
fi

VERSION=$(grep "^\$plugin->version" version.php | sed -E "s/.*= ([0-9]+);.*/\1/")
RELEASE=$(grep "^\$plugin->release" version.php | sed -E "s/.*'(.*)'.*/\1/")

if [ -z "$VERSION" ] || [ -z "$RELEASE" ]; then
    echo -e "${RED}Error: Could not extract version information from version.php${NC}"
    exit 1
fi

echo -e "${GREEN}Plugin Version:${NC} $VERSION"
echo -e "${GREEN}Release Version:${NC} $RELEASE"
echo ""

# Create distribution directory
DIST_DIR="$SCRIPT_DIR/dist"
BUILD_DIR="$DIST_DIR/build"
PLUGIN_DIR="$BUILD_DIR/credentium"

echo -e "${YELLOW}Creating build directories...${NC}"
rm -rf "$DIST_DIR"
mkdir -p "$PLUGIN_DIR"

# Load .buildignore patterns
IGNORE_PATTERNS=()
if [ -f ".buildignore" ]; then
    echo -e "${YELLOW}Loading .buildignore patterns...${NC}"
    while IFS= read -r line; do
        # Skip empty lines and comments
        [[ -z "$line" || "$line" =~ ^# ]] && continue
        IGNORE_PATTERNS+=("$line")
    done < .buildignore
fi

# Function to check if file should be ignored
should_ignore() {
    local file="$1"
    for pattern in "${IGNORE_PATTERNS[@]}"; do
        # Handle negation patterns (!)
        if [[ "$pattern" =~ ^! ]]; then
            local positive_pattern="${pattern#!}"
            if [[ "$file" == $positive_pattern ]]; then
                return 1  # Don't ignore
            fi
        else
            if [[ "$file" == $pattern ]]; then
                return 0  # Ignore
            fi
        fi
    done
    return 1  # Don't ignore by default
}

# Copy files to build directory
echo -e "${YELLOW}Copying plugin files...${NC}"
copied_count=0
ignored_count=0

# Use rsync for efficient copying, excluding patterns from .buildignore
rsync -av \
    --exclude='.git' \
    --exclude='.DS_Store' \
    --exclude='deploy.sh' \
    --exclude='.buildignore' \
    --exclude='README.md' \
    --exclude='CLAUDE.md' \
    --exclude='dist/' \
    --exclude='*.zip' \
    --exclude='.idea' \
    --exclude='.vscode' \
    . "$PLUGIN_DIR/" | while read -r line; do
    if [[ ! "$line" =~ ^sending|^sent|^total ]]; then
        ((copied_count++)) || true
    fi
done

echo -e "${GREEN}Copied plugin files to build directory${NC}"

# Verify critical files exist
echo -e "${YELLOW}Verifying critical files...${NC}"
critical_files=(
    "version.php"
    "lib.php"
    "settings.php"
    "db/install.xml"
    "db/access.php"
    "lang/en/local_credentium.php"
)

missing_files=()
for file in "${critical_files[@]}"; do
    if [ ! -f "$PLUGIN_DIR/$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -gt 0 ]; then
    echo -e "${RED}Error: Critical files missing from build:${NC}"
    printf '%s\n' "${missing_files[@]}"
    exit 1
fi

echo -e "${GREEN}All critical files present${NC}"

# Create ZIP file
ZIP_NAME="local_credentium-${RELEASE}.zip"
ZIP_PATH="$DIST_DIR/$ZIP_NAME"

echo -e "${YELLOW}Creating ZIP archive...${NC}"
cd "$BUILD_DIR"
zip -r "$ZIP_PATH" credentium/ -q

if [ $? -eq 0 ]; then
    ZIP_SIZE=$(du -h "$ZIP_PATH" | cut -f1)
    echo -e "${GREEN}✓ Successfully created: $ZIP_NAME ($ZIP_SIZE)${NC}"
else
    echo -e "${RED}Error creating ZIP file${NC}"
    exit 1
fi

# Calculate checksum
echo -e "${YELLOW}Calculating checksums...${NC}"
cd "$DIST_DIR"
sha256sum "$ZIP_NAME" > "${ZIP_NAME}.sha256"
md5sum "$ZIP_NAME" > "${ZIP_NAME}.md5"

echo -e "${GREEN}✓ Checksums created${NC}"

# Clean up build directory
echo -e "${YELLOW}Cleaning up temporary files...${NC}"
rm -rf "$BUILD_DIR"

# Display results
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Deployment Package Created Successfully!${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}Package Details:${NC}"
echo -e "  Name:     $ZIP_NAME"
echo -e "  Size:     $ZIP_SIZE"
echo -e "  Location: $ZIP_PATH"
echo ""
echo -e "${GREEN}Checksums:${NC}"
echo -e "  SHA256:   $(cat ${ZIP_NAME}.sha256 | cut -d' ' -f1)"
echo -e "  MD5:      $(cat ${ZIP_NAME}.md5 | cut -d' ' -f1)"
echo ""
echo -e "${YELLOW}Installation Instructions:${NC}"
echo -e "  1. Upload to Moodle: Site Administration > Plugins > Install plugins"
echo -e "  2. Or extract to: {moodle_root}/local/credentium/"
echo -e "  3. Visit: Site Administration > Notifications"
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Credentium® is a registered trademark${NC}"
echo -e "${BLUE}© 2025 CloudTeam Sp. z o.o.${NC}"
echo -e "${BLUE}========================================${NC}"
