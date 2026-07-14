#!/usr/bin/env bash
set -euo pipefail

HOST_HEADER="februandik.duckdns.org"
URL="http://127.0.0.1/"

echo "Checking main page (Host: $HOST_HEADER)..."
if ! curl -sS -H "Host: $HOST_HEADER" "$URL" | grep -q "Undangan Pernikahan" ; then
  echo "Main page content check failed" >&2
  exit 1
fi

echo "Checking messages endpoint..."
if ! curl -sS -H "Host: $HOST_HEADER" "http://127.0.0.1/messages.php" | jq . >/dev/null 2>&1; then
  echo "messages.php did not return valid JSON" >&2
  # continue but report
fi

echo "Health checks passed."
