#!/usr/bin/env bash
# rest-smoke.sh – REST API smoke tests for poradnik.pro
# Usage: bash tests/e2e/rest-smoke.sh https://your-wp-site.test

set -euo pipefail

BASE_URL="${1:-http://localhost}"
API="${BASE_URL}/wp-json/poradnik/v1"
PASS=0
FAIL=0

check() {
    local label="$1"
    local url="$2"
    local expected_status="${3:-200}"

    actual=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    if [ "$actual" = "$expected_status" ]; then
        echo "  PASS  [$actual] $label"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  [expected $expected_status, got $actual] $label – $url"
        FAIL=$((FAIL + 1))
    fi
}

echo "=== REST API Smoke Tests: $BASE_URL ==="
echo ""

# Health
check "GET /health"                    "$API/health"                        200

# Affiliate
check "GET /affiliate/products"        "$API/affiliate/products"            200

# Dashboard (requires auth – expect 401 without credentials)
check "GET /dashboard/statistics"      "$API/dashboard/statistics"          401

# Ads (POST endpoints – expect 400/401 without body/auth)
check "POST /ads/click (no auth)"      "$API/ads/click"                     401
check "POST /ads/impression (no auth)" "$API/ads/impression"                401

# Sponsored
check "GET /sponsored/orders (no auth)" "$API/sponsored/orders"             401

# AI (requires auth)
check "POST /ai/content/generate (no auth)" "$API/ai/content/generate"     401
check "POST /ai/image/generate (no auth)"   "$API/ai/image/generate"       401

echo ""
echo "=== Results: $PASS passed, $FAIL failed ==="

if [ "$FAIL" -gt 0 ]; then
    exit 1
fi
