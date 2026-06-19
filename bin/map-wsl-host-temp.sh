#!/usr/bin/env bash
set -euo pipefail

# Temporary mapping for a domain to Windows host IP from inside WSL2.
# Usage: ./map-wsl-host-temp.sh [domain]
# Default domain: alexboia.net.local

DOMAIN="${1:-alexboia.net.local}"

if [[ -z "${DOMAIN}" ]]; then
  echo "Domain cannot be empty." >&2
  exit 1
fi

WINDOWS_HOST_IP="$(ip route 2>/dev/null | awk '/^default[[:space:]]/ {print $3; exit}')"

if [[ -z "${WINDOWS_HOST_IP}" && -f /etc/resolv.conf ]]; then
  WINDOWS_HOST_IP="$(awk '/^nameserver[[:space:]]+/ {print $2; exit}' /etc/resolv.conf)"
fi

if [[ -z "${WINDOWS_HOST_IP}" ]]; then
  echo "Could not determine Windows host IP from WSL default route or /etc/resolv.conf." >&2
  exit 1
fi

TMP_HOSTS="$(mktemp)"

awk -v domain="${DOMAIN}" '
{
  for (i = 2; i <= NF; i++) {
    if ($i == domain) {
      next
    }
  }
  print
}
' /etc/hosts > "${TMP_HOSTS}"

echo "${WINDOWS_HOST_IP} ${DOMAIN}" >> "${TMP_HOSTS}"
sudo cp "${TMP_HOSTS}" /etc/hosts
rm -f "${TMP_HOSTS}"

echo "Mapped ${DOMAIN} -> ${WINDOWS_HOST_IP} in /etc/hosts"
echo "Note: IP can change after restart. Re-run this script when needed."
