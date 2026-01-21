#!/bin/bash
#
# Build script for Forminator Export Formats plugin
# Creates an optimized distribution ZIP with minimal dependencies
#

set -e

# Configuration
PLUGIN_NAME="forminator-export-formats"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="${PLUGIN_DIR}/build"
DIST_DIR="${BUILD_DIR}/${PLUGIN_NAME}"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Building ${PLUGIN_NAME}...${NC}"

# Clean previous build
rm -rf "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# Copy plugin files (excluding dev files)
echo -e "${YELLOW}Copying plugin files...${NC}"

# Copy main files
cp "${PLUGIN_DIR}/forminator-export-formats.php" "${DIST_DIR}/"
cp "${PLUGIN_DIR}/uninstall.php" "${DIST_DIR}/"
cp "${PLUGIN_DIR}/README.md" "${DIST_DIR}/"
cp "${PLUGIN_DIR}/readme.txt" "${DIST_DIR}/"

# Copy directories
cp -r "${PLUGIN_DIR}/includes" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/admin" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/languages" "${DIST_DIR}/"

# Create minimal vendor directory with only TCPDF essentials
echo -e "${YELLOW}Creating minimal vendor package...${NC}"
mkdir -p "${DIST_DIR}/vendor/tecnickcom/tcpdf"
mkdir -p "${DIST_DIR}/vendor/tecnickcom/tcpdf/include"
mkdir -p "${DIST_DIR}/vendor/tecnickcom/tcpdf/config"
mkdir -p "${DIST_DIR}/vendor/tecnickcom/tcpdf/fonts"

# Copy TCPDF core files
TCPDF_SRC="${PLUGIN_DIR}/vendor/tecnickcom/tcpdf"
cp "${TCPDF_SRC}/tcpdf.php" "${DIST_DIR}/vendor/tecnickcom/tcpdf/"
cp "${TCPDF_SRC}/tcpdf_autoconfig.php" "${DIST_DIR}/vendor/tecnickcom/tcpdf/"
cp "${TCPDF_SRC}/LICENSE.TXT" "${DIST_DIR}/vendor/tecnickcom/tcpdf/"

# Copy include directory
cp -r "${TCPDF_SRC}/include/"* "${DIST_DIR}/vendor/tecnickcom/tcpdf/include/"

# Copy config
cp -r "${TCPDF_SRC}/config/"* "${DIST_DIR}/vendor/tecnickcom/tcpdf/config/"

# Copy only essential fonts (Helvetica, Courier, Times - core PDF fonts)
echo -e "${YELLOW}Copying minimal fonts...${NC}"
FONTS_SRC="${TCPDF_SRC}/fonts"
FONTS_DEST="${DIST_DIR}/vendor/tecnickcom/tcpdf/fonts"

# Core PDF fonts (no external files needed, just PHP definitions)
for font in helvetica helveticab helveticabi helveticai courier courierb courierbi courieri times timesb timesbi timesi symbol zapfdingbats; do
    if [ -f "${FONTS_SRC}/${font}.php" ]; then
        cp "${FONTS_SRC}/${font}.php" "${FONTS_DEST}/"
    fi
done

# Copy DejaVu fonts for UTF-8 support (minimal set)
mkdir -p "${FONTS_DEST}/dejavu-fonts-ttf-2.34/ttf"
if [ -d "${FONTS_SRC}/dejavu-fonts-ttf-2.34" ]; then
    cp "${FONTS_SRC}/dejavu-fonts-ttf-2.34/ttf/DejaVuSans.ttf" "${FONTS_DEST}/dejavu-fonts-ttf-2.34/ttf/" 2>/dev/null || true
    cp "${FONTS_SRC}/dejavu-fonts-ttf-2.34/ttf/DejaVuSans-Bold.ttf" "${FONTS_DEST}/dejavu-fonts-ttf-2.34/ttf/" 2>/dev/null || true
fi

# Copy DejaVu PHP font definitions
for font in dejavusans dejavusansb dejavusansbi dejavusansi; do
    if [ -f "${FONTS_SRC}/${font}.php" ]; then
        cp "${FONTS_SRC}/${font}.php" "${FONTS_DEST}/"
    fi
    if [ -f "${FONTS_SRC}/${font}.z" ]; then
        cp "${FONTS_SRC}/${font}.z" "${FONTS_DEST}/"
    fi
    if [ -f "${FONTS_SRC}/${font}.ctg.z" ]; then
        cp "${FONTS_SRC}/${font}.ctg.z" "${FONTS_DEST}/"
    fi
done

# Create minimal Composer autoloader
echo -e "${YELLOW}Creating minimal autoloader...${NC}"
mkdir -p "${DIST_DIR}/vendor/composer"

cat > "${DIST_DIR}/vendor/autoload.php" << 'EOF'
<?php
/**
 * Minimal autoloader for Forminator Export Formats
 * 
 * This replaces the full Composer autoloader with a minimal version
 * that only loads TCPDF when needed.
 */

// Load TCPDF
$tcpdf_path = __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
if (file_exists($tcpdf_path) && !class_exists('TCPDF', false)) {
    require_once $tcpdf_path;
}
EOF

# Create the ZIP file
echo -e "${YELLOW}Creating ZIP archive...${NC}"
cd "${BUILD_DIR}"
zip -r "${PLUGIN_NAME}.zip" "${PLUGIN_NAME}" -x "*.DS_Store" -x "*__MACOSX*"

# Get final size
ZIP_SIZE=$(du -h "${PLUGIN_NAME}.zip" | cut -f1)
UNZIPPED_SIZE=$(du -sh "${PLUGIN_NAME}" | cut -f1)

echo -e "${GREEN}Build complete!${NC}"
echo ""
echo "Distribution package: ${BUILD_DIR}/${PLUGIN_NAME}.zip"
echo "ZIP size: ${ZIP_SIZE}"
echo "Unzipped size: ${UNZIPPED_SIZE}"
echo ""
echo "The ZIP file is ready for distribution."
