#!/usr/bin/env bash
set -euo pipefail

# Deploy portal and mu-plugins via lftp. Environment variables expected:
# FTP_HOST, FTP_USERNAME or FTP_USER, FTP_PASSWORD or FTP_PASS, FTP_PORT (optional), REMOTE_BASE (optional)

HOST="${FTP_HOST:?FTP_HOST is required}"
USER="${FTP_USER:-${FTP_USERNAME:-}}"
PASS="${FTP_PASS:-${FTP_PASSWORD:-}}"
PORT="${FTP_PORT:-21}"
REMOTE_BASE="${REMOTE_BASE:-/public_html/stage}"

if [ -z "$USER" ] || [ -z "$PASS" ]; then
  echo "FTP username or password missing"
  exit 2
fi

echo "Starting lftp deploy to $HOST (port $PORT) -> $REMOTE_BASE"

LFTP_CMD="lftp -u \"$USER\",\"$PASS\" -p \"$PORT\" \"$HOST\""

echo "Uploading portal -> $REMOTE_BASE/wp-content/plugins/loungenie-portal"
eval "$LFTP_CMD" <<LFTP
set ftp:ssl-allow no
set net:max-retries 2
mirror -R --verbose --continue --parallel=4 --delete portal ${REMOTE_BASE}/wp-content/plugins/loungenie-portal
quit
LFTP

echo "Uploading mu-plugins -> $REMOTE_BASE/wp-content/mu-plugins"
eval "$LFTP_CMD" <<LFTP
set ftp:ssl-allow no
set net:max-retries 2
mirror -R --verbose --continue --parallel=4 --delete wp-content/mu-plugins ${REMOTE_BASE}/wp-content/mu-plugins
quit
LFTP

echo "Deploy finished"
