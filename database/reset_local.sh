#!/usr/bin/env bash
# Rebuild the LOCAL database from scratch: schema, reference data, sample
# questions and demo users. Destroys every row in the target database.
#
#   database/reset_local.sh            # full dev reset
#   database/reset_local.sh --no-demo  # schema + reference data only
#
# Credentials come from application/config/env.php, the same file the app
# reads, so this can never target a database the app is not configured for.
set -euo pipefail
cd "$(dirname "$0")/.."

ENV_FILE=application/config/env.php
[ -f "$ENV_FILE" ] || { echo "missing $ENV_FILE (copy env.php.example)" >&2; exit 1; }

# schema.sql drops tables. Refuse anywhere that is configured as production.
if [ "${CI_ENV:-development}" = "production" ]; then
  echo "refusing: CI_ENV=production" >&2; exit 1
fi

val() { sed -n "s/.*define('$1', *'\([^']*\)').*/\1/p" "$ENV_FILE"; }
DB_HOST=$(val DB_HOSTNAME); DB_USER=$(val DB_USERNAME); DB_NAME=$(val DB_DATABASE)
export MYSQL_PWD="$(val DB_PASSWORD)"     # env var: keeps it out of `ps`
run() { mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$1"; echo "  loaded $1"; }

echo "Resetting '$DB_NAME' on $DB_HOST"
run database/schema.sql
run database/seed.sql
if [ "${1:-}" != "--no-demo" ]; then
  run database/seed_sample_questions.sql
  run database/seed_demo.sql
fi
echo "done"
