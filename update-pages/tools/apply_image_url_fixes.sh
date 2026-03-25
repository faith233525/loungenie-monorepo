#!/usr/bin/env bash
set -euo pipefail

USAGE() {
  cat <<EOF
Usage: $0 [--apply]

Without --apply the script runs WP-CLI search-replace in dry-run mode and writes results to files.
Pass --apply to perform the replacements (after verifying dry-run output).

Run this on the WordPress site root over SSH where `wp` is available.
EOF
}

APPLY=false
if [[ ${1:-} == "--apply" ]]; then
  APPLY=true
fi

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP="loungenie-db-backup-$TIMESTAMP.sql"
DRYLOG="loungenie-searchreplace-dryrun-$TIMESTAMP.txt"
APPLYLOG="loungenie-searchreplace-apply-$TIMESTAMP.txt"

if ! command -v wp >/dev/null 2>&1; then
  echo "ERROR: wp-cli not found in PATH. Run this on the site server where WordPress is installed."
  exit 1
fi

echo "Creating DB backup: $BACKUP"
wp db export "$BACKUP"

# Replacement pairs: old => new
REPLACEMENTS=(
  "loungenie%E2%84%A2/wp-content|loungenie/wp-content"
  "loungenie™/wp-content|loungenie/wp-content"
  "https://www.loungenie.com|https://loungenie.com"
)

# Common WP-CLI options used
COMMON_OPTS=(--skip-columns=guid --precise --recurse-objects)

echo "Running dry-run search-replace for ${#REPLACEMENTS[@]} patterns (output -> $DRYLOG)"
rm -f "$DRYLOG" "$APPLYLOG"
for pair in "${REPLACEMENTS[@]}"; do
  OLD=${pair%%|*}
  NEW=${pair##*|}
  echo "--- Pattern: '$OLD' -> '$NEW' ---" | tee -a "$DRYLOG"
  wp search-replace "$OLD" "$NEW" "${COMMON_OPTS[@]}" --dry-run | tee -a "$DRYLOG"
  echo >> "$DRYLOG"
done

if [ "$APPLY" = true ]; then
  echo "Applying replacements (this will modify the DB). See $APPLYLOG for output."
  for pair in "${REPLACEMENTS[@]}"; do
    OLD=${pair%%|*}
    NEW=${pair##*|}
    echo "--- Applying: '$OLD' -> '$NEW' ---" | tee -a "$APPLYLOG"
    wp search-replace "$OLD" "$NEW" "${COMMON_OPTS[@]}" | tee -a "$APPLYLOG"
    echo >> "$APPLYLOG"
  done
  echo "Flushing caches..."
  wp cache flush || true
  wp transient delete --all || true
  echo "Apply complete. Review $APPLYLOG and purge CDN/LiteSpeed cache if used." 
else
  echo "Dry-run complete. Inspect $DRYLOG, then re-run with --apply to perform changes."
fi

echo "Backup file saved: $BACKUP"

exit 0
