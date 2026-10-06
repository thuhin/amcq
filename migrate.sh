#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# migrate.sh — bring an existing AcademicMCQ database up to date.
#
#   ./migrate.sh              apply application/migrations/*.sql (schema + reference data)
#   ./migrate.sh --test-data  also apply database/test_data/*.sql (refused when CI_ENV=production)
#   ./migrate.sh --list       list the files that would run (no DB connection)
#
# Files run in filename (timestamp) order. Every migration is idempotent, so
# re-running is safe. Fresh installs don't need this: database/schema.sql and
# the seeds already include everything (see database/reset_local.sh).
#
# Host/user/database come from application/config/env.php. The PASSWORD is
# asked for at run time: never read from a file, never put on the command
# line or in the process list.
# ---------------------------------------------------------------------------
set -euo pipefail
cd "$(dirname "$0")"

ENV_FILE=application/config/env.php
[ -f "$ENV_FILE" ] || { echo "✗ $ENV_FILE not found (copy env.php.example)"; exit 1; }

# Flags in any order.
WITH_TEST_DATA=0; LIST_ONLY=0
for arg in "$@"; do
  case "$arg" in
    --test-data) WITH_TEST_DATA=1 ;;
    --list)      LIST_ONLY=1 ;;
    *) echo "✗ unknown option: $arg (use --test-data and/or --list)"; exit 1 ;;
  esac
done

shopt -s nullglob
files=( application/migrations/*.sql )
if [ "$WITH_TEST_DATA" = 1 ]; then
  if [ "${CI_ENV:-development}" = "production" ]; then
    echo "✗ refusing --test-data: CI_ENV=production"; exit 1
  fi
  files+=( database/test_data/*.sql )
  # Strict timestamp order across both folders (sort by file name, not path).
  mapfile -t files < <(for f in "${files[@]}"; do printf '%s|%s\n' "$(basename "$f")" "$f"; done | sort | cut -d'|' -f2)
fi

if [ "$LIST_ONLY" = 1 ]; then
  printf '%s\n' "${files[@]}"; exit 0
fi

val() { php -r 'define("BASEPATH", 1); require $argv[1]; echo constant($argv[2]);' "$ENV_FILE" "$1"; }
DBH=$(val DB_HOSTNAME); DBU=$(val DB_USERNAME); DBN=$(val DB_DATABASE)

read -r -s -p "MySQL password for ${DBU}@${DBH} (database: ${DBN}): " DBPASS || true
echo

# Password goes to mysql through a temp options file (mode 600), removed on exit.
MYCNF="$(mktemp)"; chmod 600 "$MYCNF"
trap 'rm -f "$MYCNF"' EXIT
printf '[client]\nhost=%s\nuser=%s\npassword=%s\n' "$DBH" "$DBU" "$DBPASS" > "$MYCNF"
unset DBPASS

for f in "${files[@]}"; do
  printf '  %-62s ' "$f"
  mysql --defaults-extra-file="$MYCNF" "$DBN" < "$f"
  echo "ok"
done
echo "✓ ${#files[@]} file(s) applied to ${DBN}"
