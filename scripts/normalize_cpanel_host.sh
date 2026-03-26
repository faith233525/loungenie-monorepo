#!/usr/bin/env bash
# normalize_cpanel_host.sh
#
# Usage (source this file, then call normalize_cpanel_host):
#   source scripts/normalize_cpanel_host.sh
#   HOST=$(normalize_cpanel_host "$CPANEL_HOST" "${CPANEL_HOSTNAME:-cpanel.loungenie.com}")
#
# If the first argument is a bare IPv4 address the second argument (the
# canonical cPanel hostname) is returned instead. This avoids curl exit
# code 60 caused by TLS certificates that are issued to the hostname, not
# the IP. When the first argument is already a hostname it is returned
# unchanged.

normalize_cpanel_host() {
  local host="${1:?normalize_cpanel_host: HOST argument is required}"
  local canonical="${2:-cpanel.loungenie.com}"
  local ipv4_re='^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$'
  if echo "$host" | grep -qE "$ipv4_re"; then
    echo "Warning: CPANEL_HOST is a bare IP ($host); using $canonical for SSL." >&2
    echo "$canonical"
  else
    echo "$host"
  fi
}
