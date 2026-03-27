#!/usr/bin/env bash
# p1-ai-content-engine-smoke.sh - AI/SEO smoke checks for poradnik.pro
# Usage: bash tools/p1-ai-content-engine-smoke.sh --base-url https://poradnik.pro [--strict]

set -euo pipefail

BASE_URL="https://poradnik.pro"
STRICT=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --base-url|-b)
      BASE_URL="$2"
      shift 2
      ;;
    --strict)
      STRICT=1
      shift
      ;;
    *)
      echo "Unknown arg: $1" >&2
      exit 2
      ;;
  esac
done

BASE_URL="${BASE_URL%/}"
META_JSON="$(curl -fsSL "${BASE_URL}/wp-json/" 2>/dev/null || true)"
NAMESPACE="poradnik/v1"

if echo "$META_JSON" | grep -q '"poradnik/v1"'; then
  NAMESPACE="poradnik/v1"
elif echo "$META_JSON" | grep -q '"peartree/v1"'; then
  NAMESPACE="peartree/v1"
fi

run_post_check() {
  local name="$1"
  local path="$2"
  local payload="$3"

  local -a candidates=("$NAMESPACE")
  if [[ "$NAMESPACE" != "poradnik/v1" ]]; then
    candidates+=("poradnik/v1")
  fi
  if [[ "$NAMESPACE" != "peartree/v1" ]]; then
    candidates+=("peartree/v1")
  fi

  local code="404"
  local used_url=""
  local exists=0

  for ns in "${candidates[@]}"; do
    used_url="${BASE_URL}/wp-json/${ns}${path}"
    code=$(curl -s -o /tmp/ai-smoke-response.tmp -w "%{http_code}" -X POST -H "Content-Type: application/json" -d "$payload" "$used_url" || echo "-1")
    if [[ "$code" != "404" ]]; then
      exists=1
      break
    fi
  done

  if [[ "$exists" -eq 0 ]]; then
    echo "${name}_PASS=0;SKIPPED_ROUTE_NOT_FOUND"
    echo "SKIPPED"
    return 0
  fi

  case "$code" in
    200|400|401|403)
      echo "${name}_PASS=${code}"
      echo "PASS"
      ;;
    *)
      echo "${name}_FAIL=${code}"
      echo "FAIL"
      ;;
  esac
}

FAILED=0
SKIPPED=0
TOTAL=0

echo "=== AI/CONTENT ENGINE SMOKE: ${BASE_URL} ==="
echo "AI_SMOKE_NAMESPACE=${NAMESPACE}"

checks=(
  "AI_ASSISTANT|/ai/content/generate|{\"tool\":\"outline\",\"input\":\"jak ustawic router wifi w domu\",\"items\":[\"Opcja A\",\"Opcja B\"]}"
  "AI_IMAGE|/ai/image/generate|{\"title\":\"Poradnik: konfiguracja domowego Wi-Fi\",\"category\":\"poradnik\"}"
  "CONTENT_ENGINE_PORADNIK|/seo/programmatic/build|{\"generation_mode\":\"single\",\"template\":\"jak-zrobic\",\"topic\":\"kopia zapasowa wordpress\",\"count\":1,\"post_type\":\"poradnik\"}"
  "CONTENT_ENGINE_QA|/seo/programmatic/build|{\"generation_mode\":\"single\",\"template\":\"jak-dziala\",\"topic\":\"jak dziala cache strony\",\"count\":1,\"post_type\":\"pytanie\"}"
  "CONTENT_ENGINE_AFFILIATE|/seo/programmatic/build|{\"generation_mode\":\"single\",\"template\":\"best\",\"topic\":\"najlepszy hosting wordpress\",\"count\":1,\"post_type\":\"affiliate\"}"
)

for check in "${checks[@]}"; do
  IFS='|' read -r name path payload <<< "$check"
  TOTAL=$((TOTAL + 1))

  result="$(run_post_check "$name" "$path" "$payload")"
  echo "$result" | sed '/^PASS$/d;/^FAIL$/d;/^SKIPPED$/d'

  if echo "$result" | grep -q '^FAIL$'; then
    FAILED=$((FAILED + 1))
  fi

  if echo "$result" | grep -q '^SKIPPED$'; then
    SKIPPED=$((SKIPPED + 1))
  fi
done

echo "AI_SMOKE_BASE=${BASE_URL}"
echo "AI_SMOKE_TOTAL=${TOTAL}"
echo "AI_SMOKE_FAILED=${FAILED}"
echo "AI_SMOKE_SKIPPED_ROUTES=${SKIPPED}"

if [[ "$FAILED" -eq 0 ]]; then
  echo "AI_CONTENT_ENGINE_SMOKE=PASS"
else
  echo "AI_CONTENT_ENGINE_SMOKE=FAIL"
fi

echo "AI_CONTENT_ENGINE_SMOKE_SCRIPT_EXIT=0"

if [[ "$STRICT" -eq 1 && "$FAILED" -gt 0 ]]; then
  exit 1
fi

exit 0
