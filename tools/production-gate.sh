#!/usr/bin/env bash
# production-gate.sh - aggregate production gate for poradnik.pro
# Usage: bash tools/production-gate.sh --base-url https://poradnik.pro [--require-ai-routes]

set -euo pipefail

BASE_URL="https://poradnik.pro"
REQUIRE_AI_ROUTES=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --base-url|-b)
      BASE_URL="$2"
      shift 2
      ;;
    --require-ai-routes)
      REQUIRE_AI_ROUTES=1
      shift
      ;;
    *)
      echo "Unknown arg: $1" >&2
      exit 2
      ;;
  esac
done

BASE_URL="${BASE_URL%/}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "=== PORADNIK.PRO PRODUCTION GATE ==="
echo "BASE_URL=${BASE_URL}"
echo "GATE_TIMESTAMP=$(date -u +%Y-%m-%dT%H:%M:%SZ)"

set +e
REST_OUTPUT="$(STRICT=1 bash "${ROOT_DIR}/tests/e2e/rest-smoke.sh" "${BASE_URL}" 2>&1)"
REST_EXIT=$?
set -e
echo "$REST_OUTPUT"

set +e
AI_OUTPUT="$(bash "${ROOT_DIR}/tools/p1-ai-content-engine-smoke.sh" --base-url "${BASE_URL}" --strict 2>&1)"
AI_EXIT=$?
set -e
echo "$AI_OUTPUT"

REST_FAILED="$(echo "$REST_OUTPUT" | sed -n 's/^SMOKE_FAILED=//p' | tail -n1)"
AI_FAILED="$(echo "$AI_OUTPUT" | sed -n 's/^AI_SMOKE_FAILED=//p' | tail -n1)"
AI_SKIPPED="$(echo "$AI_OUTPUT" | sed -n 's/^AI_SMOKE_SKIPPED_ROUTES=//p' | tail -n1)"

REST_FAILED="${REST_FAILED:-1}"
AI_FAILED="${AI_FAILED:-1}"
AI_SKIPPED="${AI_SKIPPED:-0}"

echo ""
echo "GATE_REST_EXIT=${REST_EXIT}"
echo "GATE_AI_EXIT=${AI_EXIT}"
echo "GATE_REST_FAILED=${REST_FAILED}"
echo "GATE_AI_FAILED=${AI_FAILED}"
echo "GATE_AI_SKIPPED_ROUTES=${AI_SKIPPED}"

FAILED=0

if [[ "$REST_EXIT" -ne 0 || "$AI_EXIT" -ne 0 ]]; then
  FAILED=1
fi

if [[ "$REST_FAILED" -gt 0 || "$AI_FAILED" -gt 0 ]]; then
  FAILED=1
fi

if [[ "$REQUIRE_AI_ROUTES" -eq 1 && "$AI_SKIPPED" -gt 0 ]]; then
  echo "GATE_AI_ROUTE_POLICY=FAIL (RequireAiRoutes enabled and one or more AI routes were skipped)"
  FAILED=1
else
  echo "GATE_AI_ROUTE_POLICY=PASS"
fi

if [[ "$FAILED" -eq 1 ]]; then
  echo "PRODUCTION_GATE=FAIL"
  exit 1
fi

echo "PRODUCTION_GATE=PASS"
exit 0
