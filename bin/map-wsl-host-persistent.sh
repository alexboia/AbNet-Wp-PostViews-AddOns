#!/usr/bin/env bash
set -euo pipefail

# Persistent mapping for a domain to Windows host IP from inside WSL2.
# Usage: ./map-wsl-host-persistent.sh [domain]
# Default domain: alexboia.net.local
#
# What it does:
# 1) Sets [network] generateHosts=false in /etc/wsl.conf
# 2) Updates /etc/hosts with domain -> Windows host IP
# 3) Prints next steps (wsl --shutdown)

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

TMP_WSLCONF="$(mktemp)"
TMP_HOSTS="$(mktemp)"

# Build /etc/wsl.conf with generateHosts=false and preserve other settings.
if [[ -f /etc/wsl.conf ]]; then
  cp /etc/wsl.conf "${TMP_WSLCONF}"
else
  : > "${TMP_WSLCONF}"
fi

if grep -q '^\[network\]' "${TMP_WSLCONF}"; then
  if grep -q '^generateHosts=' "${TMP_WSLCONF}"; then
    sed -Ei 's/^generateHosts=.*/generateHosts=false/' "${TMP_WSLCONF}"
  else
    awk '
      BEGIN {in_network=0; inserted=0}
      /^\[network\]$/ {in_network=1; print; next}
      /^\[/ {
        if (in_network && !inserted) {
          print "generateHosts=false"
          inserted=1
        }
        in_network=0
      }
      {print}
      END {
        if (in_network && !inserted) {
          print "generateHosts=false"
        }
      }
    ' "${TMP_WSLCONF}" > "${TMP_WSLCONF}.new"
    mv "${TMP_WSLCONF}.new" "${TMP_WSLCONF}"
  fi
else
  if [[ -s "${TMP_WSLCONF}" ]]; then
    printf '\n' >> "${TMP_WSLCONF}"
  fi
  cat >> "${TMP_WSLCONF}" <<'EOF'
[network]
generateHosts=false
EOF
fi

sudo cp "${TMP_WSLCONF}" /etc/wsl.conf

# Update /etc/hosts idempotently.
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

rm -f "${TMP_WSLCONF}" "${TMP_HOSTS}"

echo "Configured persistent mapping: ${DOMAIN} -> ${WINDOWS_HOST_IP}"
echo "Updated /etc/wsl.conf with [network] generateHosts=false"
echo ""
echo "Next step (from Windows PowerShell):"
echo "  wsl --shutdown"
echo "Then reopen WSL and verify with:"
echo "  getent hosts ${DOMAIN}"
