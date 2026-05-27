#!/usr/bin/env bash
#
# Build a clean distribution ZIP of the plugin.
#
# Steps:
#   1. composer install --no-dev   (production dependencies only)
#   2. npm ci && npm run build      (production assets)
#   3. Copy plugin files into a staging dir, excluding everything in .distignore
#   4. Zip the staging dir as wp-movie-collector-<version>.zip in dist/
#
# Usage: bin/build-release.sh
#
set -euo pipefail

SLUG="wp-movie-collector"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
STAGE_DIR="${DIST_DIR}/${SLUG}"

cd "${ROOT_DIR}"

# Derive the version from the plugin header so the filename always matches.
VERSION="$(grep -E "^[[:space:]]*\*[[:space:]]*Version:" "${ROOT_DIR}/${SLUG}.php" | head -n1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
if [[ -z "${VERSION}" ]]; then
  echo "ERROR: could not determine plugin version from ${SLUG}.php" >&2
  exit 1
fi
echo "Building ${SLUG} version ${VERSION}"

ZIP_NAME="${SLUG}-${VERSION}.zip"

# 1. Production PHP dependencies.
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader
else
  echo "WARNING: composer not found; skipping PHP dependency install" >&2
fi

# 2. Production assets.
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "WARNING: npm not found; skipping asset build" >&2
fi

# 3. Stage files, honoring .distignore.
rm -rf "${STAGE_DIR}" "${DIST_DIR}/${ZIP_NAME}"
mkdir -p "${STAGE_DIR}"

EXCLUDES=("--exclude=./dist" "--exclude=./.git")
while IFS= read -r line; do
  # Skip comments and blank lines.
  [[ -z "${line}" || "${line}" =~ ^[[:space:]]*# ]] && continue
  EXCLUDES+=("--exclude=./${line}")
done < "${ROOT_DIR}/.distignore"

# Copy everything except excluded patterns using tar (portable, no rsync needed).
tar -C "${ROOT_DIR}" -cf - "${EXCLUDES[@]}" . | tar -C "${STAGE_DIR}" -xf -

# 4. Create the ZIP (top-level dir is the plugin slug, as WordPress expects).
cd "${DIST_DIR}"
if command -v zip >/dev/null 2>&1; then
  zip -rq "${ZIP_NAME}" "${SLUG}"
else
  echo "ERROR: zip not found; cannot create package" >&2
  exit 1
fi

rm -rf "${STAGE_DIR}"
echo "Created ${DIST_DIR}/${ZIP_NAME}"
