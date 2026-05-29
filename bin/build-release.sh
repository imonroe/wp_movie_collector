#!/usr/bin/env bash
#
# Build a clean distribution ZIP of the plugin.
#
# Steps:
#   1. composer install --no-dev   (production dependencies only)
#   2. Copy plugin files into a staging dir, excluding everything in .distignore
#      (the hand-written admin/js, public/js, and *.css files ARE included,
#      since they are now the canonical assets the enqueue logic loads)
#   3. Zip the staging dir as wp-movie-collector-<version>.zip in build/
#
# Usage: bin/build-release.sh
#
set -euo pipefail

SLUG="wp-movie-collector"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# The release staging dir and the final ZIP go to build/ so they are not
# packaged into themselves.
OUTPUT_DIR="${ROOT_DIR}/build"
STAGE_DIR="${OUTPUT_DIR}/${SLUG}"

cd "${ROOT_DIR}"

# Prefer an explicitly supplied version, then a CI tag ref, then the plugin
# header. The tag wins over the header so a release tagged 0.2.1 ships a ZIP
# named after the tag even if someone forgot to bump the header.
VERSION="${RELEASE_VERSION:-}"
if [[ -z "${VERSION}" && "${GITHUB_REF_TYPE:-}" == "tag" && -n "${GITHUB_REF_NAME:-}" ]]; then
  # Strip an optional leading "v" so both v1.2.3 and 1.2.3 tags work.
  VERSION="${GITHUB_REF_NAME#v}"
fi
if [[ -z "${VERSION}" ]]; then
  VERSION="$(grep -E "^[[:space:]]*\*[[:space:]]*Version:" "${ROOT_DIR}/${SLUG}.php" | head -n1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
fi
if [[ -z "${VERSION}" ]]; then
  echo "ERROR: could not determine plugin version" >&2
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

# 2. Stage files, honoring .distignore.
rm -rf "${OUTPUT_DIR}"
mkdir -p "${STAGE_DIR}"

# Always exclude the build output dir, git metadata, and source maps.
EXCLUDES=("--exclude=./build" "--exclude=./.git" "--exclude=*.map")
while IFS= read -r line; do
  # Skip comments and blank lines.
  [[ -z "${line}" || "${line}" =~ ^[[:space:]]*# ]] && continue
  EXCLUDES+=("--exclude=./${line}")
done < "${ROOT_DIR}/.distignore"

# Copy everything except excluded patterns using tar (portable, no rsync needed).
tar -C "${ROOT_DIR}" -cf - "${EXCLUDES[@]}" . | tar -C "${STAGE_DIR}" -xf -

# 3. Create the ZIP (top-level dir is the plugin slug, as WordPress expects).
cd "${OUTPUT_DIR}"
if command -v zip >/dev/null 2>&1; then
  zip -rq "${ZIP_NAME}" "${SLUG}"
else
  echo "ERROR: zip not found; cannot create package" >&2
  exit 1
fi

rm -rf "${STAGE_DIR}"
echo "Created ${OUTPUT_DIR}/${ZIP_NAME}"

# When running in GitHub Actions, expose the exact artifact path so later
# steps don't have to parse `ls` output.
if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
  echo "zip=build/${ZIP_NAME}" >> "${GITHUB_OUTPUT}"
fi
