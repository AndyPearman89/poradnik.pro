#!/usr/bin/env bash
# rest-smoke.sh - REST API smoke tests for poradnik.pro
# Usage: bash tests/e2e/rest-smoke.sh https://your-wp-site.test

set -euo pipefail

BASE_URL="${1:-http://localhost}"
STRICT="${STRICT:-1}"

meta_json="$(curl -fsSL "${BASE_URL}/wp-json/" 2>/dev/null || true)"
namespace="poradnik/v1"

if echo "$meta_json" | grep -q '"poradnik/v1"'; then
    namespace="poradnik/v1"
elif echo "$meta_json" | grep -q '"peartree/v1"'; then
    namespace="peartree/v1"
fi

API="${BASE_URL}/wp-json/${namespace}"
PASS=0
FAIL=0

check() {
    local label="$1"
    local method="$2"
    local url="$3"
    local expected_statuses="$4"
    local payload="${5:-}"

    local actual
    if [ -n "$payload" ]; then
        actual=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" -H "Content-Type: application/json" -d "$payload" "$url")
    else
        actual=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$url")
    fi
    local ok=1
    IFS='|' read -r -a allowed <<< "$expected_statuses"
    for code in "${allowed[@]}"; do
        if [ "$actual" = "$code" ]; then
            ok=0
            break
        fi
    done

    if [ "$ok" -eq 0 ]; then
        echo "  PASS  [$actual] $method $label"
        PASS=$((PASS + 1))
    else
        echo "  FAIL  [expected $expected_statuses, got $actual] $method $label - $url"
        FAIL=$((FAIL + 1))
    fi
}

echo "=== REST API Smoke Tests: $BASE_URL ==="
echo "REST_SMOKE_NAMESPACE=$namespace"
echo ""

# Health
check "/health"                    "GET"  "$API/health"                        200

# Affiliate (POST endpoint - expect validation/auth error without payload)
check "/affiliate/click (invalid payload)" "POST" "$API/affiliate/click"       "400|401|403" "{}"

# Dashboard (requires auth - expect 401 without credentials)
check "/dashboard/statistics"      "GET"  "$API/dashboard/statistics"          "401|403"

# Ads (POST endpoints - expect 401 without credentials)
check "/ads/click (no auth)"       "POST" "$API/ads/click"                     "400|401|403" "{}"
check "/ads/impression (no auth)"  "POST" "$API/ads/impression"                "400|401|403" "{}"

# Sponsored (POST endpoint - expect validation/auth error without payload)
check "/sponsored/orders (invalid payload)" "POST" "$API/sponsored/orders"     "400|401|403" "{}"

# AI (requires auth)
check "/ai/content/generate (no auth)" "POST" "$API/ai/content/generate"     "400|401|403" "{}"
check "/ai/image/generate (no auth)"   "POST" "$API/ai/image/generate"       "400|401|403" "{}"

echo ""
echo "=== Results: $PASS passed, $FAIL failed ==="
echo "SMOKE_TOTAL=$((PASS + FAIL))"
echo "SMOKE_FAILED=$FAIL"

if [ "$STRICT" = "1" ] && [ "$FAIL" -gt 0 ]; then
    exit 1
fi

exit 0
