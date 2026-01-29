#!/usr/bin/env bash
set -euo pipefail

if [[ "${RUN_QUEUE_WORKER:-false}" != "true" ]]; then
  echo "[queue-worker] RUN_QUEUE_WORKER=false; worker disabled. Sleeping indefinitely."
  exec sleep infinity
fi

TRIES="${QUEUE_MAX_TRIES:-3}"
TIMEOUT="${QUEUE_TIMEOUT:-90}"
SLEEP="${QUEUE_SLEEP:-3}"

echo "[queue-worker] Starting queue worker (tries=$TRIES timeout=${TIMEOUT}s sleep=${SLEEP}s)"
exec php /var/www/html/artisan queue:work --sleep="${SLEEP}" --tries="${TRIES}" --timeout="${TIMEOUT}"
