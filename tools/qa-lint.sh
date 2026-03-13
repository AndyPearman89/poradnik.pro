#!/usr/bin/env bash
# qa-lint.sh – PHP syntax check for all platform-core files.
# Usage: bash tools/qa-lint.sh
# Exit code 0 = all files pass; non-zero = syntax errors found.

set -euo pipefail

PLATFORM_DIR="$(cd "$(dirname "$0")/.." && pwd)/platform-core"

if [ ! -d "$PLATFORM_DIR" ]; then
    echo "ERROR: platform-core directory not found at $PLATFORM_DIR" >&2
    exit 1
fi

ERRORS=0
FILES=0

while IFS= read -r -d '' file; do
    FILES=$((FILES + 1))
    if ! php -l "$file" > /dev/null 2>&1; then
        php -l "$file" >&2
        ERRORS=$((ERRORS + 1))
    fi
done < <(find "$PLATFORM_DIR" -name "*.php" -print0)

echo "Checked $FILES PHP files. Errors: $ERRORS"

exit $ERRORS
