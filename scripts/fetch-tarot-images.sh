#!/usr/bin/env bash
# Download 78 RWS Tarot card images from Wikimedia Commons.
# Source: Pamela Colman Smith, 1909 (public domain — author d.1951).
# Output: public/img/tarot/00.jpg ~ 77.jpg (resized ≤1024px, JPEG quality 85).
#
# Idempotent: existing output files are skipped, so re-runs only fetch missing.
# Rate-limit aware: 1.5s sleep between fetches; 429 triggers exponential backoff.

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="${PROJECT_ROOT}/public/img/tarot"
TMP_DIR="${PROJECT_ROOT}/tmp/tarot-raw"
SLEEP_SEC="${SLEEP_SEC:-1.5}"
MAX_DIM="${MAX_DIM:-1024}"
USER_AGENT="Tiandao-LineBot/1.0 (educational/non-commercial)"

mkdir -p "$OUT_DIR" "$TMP_DIR"

# Resize tool detection (macOS sips / ImageMagick convert / magick)
if command -v magick >/dev/null 2>&1; then
  RESIZE_CMD="magick"
elif command -v convert >/dev/null 2>&1; then
  RESIZE_CMD="convert"
elif command -v sips >/dev/null 2>&1; then
  RESIZE_CMD="sips"
else
  echo "ERROR: need magick / convert / sips for image resize" >&2
  exit 1
fi
echo "Using resize tool: $RESIZE_CMD"

# id -> Wikimedia filename mapping (verified 2026-05 against Commons)
declare -a IDS NAMES

# 22 Major Arcana (id 00-21)
MAJOR_NAMES=(
  "RWS_Tarot_00_Fool.jpg"
  "RWS_Tarot_01_Magician.jpg"
  "RWS_Tarot_02_High_Priestess.jpg"
  "RWS_Tarot_03_Empress.jpg"
  "RWS_Tarot_04_Emperor.jpg"
  "RWS_Tarot_05_Hierophant.jpg"
  "RWS_Tarot_06_Lovers.jpg"
  "RWS_Tarot_07_Chariot.jpg"
  "RWS_Tarot_08_Strength.jpg"
  "RWS_Tarot_09_Hermit.jpg"
  "RWS_Tarot_10_Wheel_of_Fortune.jpg"
  "RWS_Tarot_11_Justice.jpg"
  "RWS_Tarot_12_Hanged_Man.jpg"
  "RWS_Tarot_13_Death.jpg"
  "RWS_Tarot_14_Temperance.jpg"
  "RWS_Tarot_15_Devil.jpg"
  "RWS_Tarot_16_Tower.jpg"
  "RWS_Tarot_17_Star.jpg"
  "RWS_Tarot_18_Moon.jpg"
  "RWS_Tarot_19_Sun.jpg"
  "RWS_Tarot_20_Judgement.jpg"
  "RWS_Tarot_21_World.jpg"
)
for i in "${!MAJOR_NAMES[@]}"; do
  IDS+=("$(printf '%02d' "$i")")
  NAMES+=("${MAJOR_NAMES[$i]}")
done

# 56 Minor Arcana (id 22-77): 4 suits × 14 cards each
SUITS=("Wands" "Cups" "Swords" "Pents")
SUIT_BASE_IDS=(22 36 50 64)
for s in "${!SUITS[@]}"; do
  suit="${SUITS[$s]}"
  base="${SUIT_BASE_IDS[$s]}"
  for n in $(seq 1 14); do
    id_num=$((base + n - 1))
    IDS+=("$(printf '%02d' "$id_num")")
    NAMES+=("$(printf '%s%02d.jpg' "$suit" "$n")")
  done
done

total=${#IDS[@]}
if [ "$total" -ne 78 ]; then
  echo "ERROR: expected 78 entries, got $total" >&2
  exit 1
fi

resize_image() {
  local src="$1" dst="$2"
  case "$RESIZE_CMD" in
    magick)
      magick "$src" -resize "${MAX_DIM}x${MAX_DIM}>" -quality 85 -strip "$dst"
      ;;
    convert)
      convert "$src" -resize "${MAX_DIM}x${MAX_DIM}>" -quality 85 -strip "$dst"
      ;;
    sips)
      # sips: -Z = max dimension preserving aspect, -s formatOptions for jpeg quality
      sips -Z "$MAX_DIM" -s formatOptions 85 "$src" --out "$dst" >/dev/null
      ;;
  esac
}

ok_count=0; skip_count=0; fail_count=0
for i in $(seq 0 $((total - 1))); do
  id="${IDS[$i]}"
  name="${NAMES[$i]}"
  out_file="$OUT_DIR/${id}.jpg"
  raw_file="$TMP_DIR/${id}.raw"

  if [ -f "$out_file" ]; then
    skip_count=$((skip_count + 1))
    continue
  fi

  url="https://commons.wikimedia.org/wiki/Special:FilePath/${name}"
  printf "[%2d/%d] %s -> %s ... " "$((i+1))" "$total" "$id" "$name"

  # Retry up to 3 times on 429 / network failure
  attempt=0; success=0
  while [ $attempt -lt 3 ]; do
    attempt=$((attempt + 1))
    http_code=$(curl -sL -A "$USER_AGENT" --max-time 60 \
      -o "$raw_file" -w "%{http_code}" "$url" || echo "000")
    if [ "$http_code" = "200" ]; then
      success=1
      break
    fi
    backoff=$((attempt * attempt * 2))
    printf "[%s, retry in %ds] " "$http_code" "$backoff"
    sleep "$backoff"
  done

  if [ $success -ne 1 ]; then
    echo "FAIL"
    fail_count=$((fail_count + 1))
    rm -f "$raw_file"
    continue
  fi

  if ! resize_image "$raw_file" "$out_file"; then
    echo "RESIZE FAIL"
    fail_count=$((fail_count + 1))
    rm -f "$raw_file"
    continue
  fi

  rm -f "$raw_file"
  size=$(stat -f%z "$out_file" 2>/dev/null || stat -c%s "$out_file")
  echo "ok (${size} bytes)"
  ok_count=$((ok_count + 1))
  sleep "$SLEEP_SEC"
done

echo ""
echo "Summary: $ok_count downloaded, $skip_count skipped, $fail_count failed"
echo "Output: $OUT_DIR"

# Clean up raw temp dir if empty
rmdir "$TMP_DIR" 2>/dev/null || true

if [ "$fail_count" -gt 0 ]; then
  echo "WARNING: $fail_count files failed; re-run script to retry missing." >&2
  exit 2
fi
