#!/usr/bin/env bash
set -euo pipefail

TMUX_BIN="/usr/bin/tmux"
SCRIPT_BIN="/usr/bin/script"
SOCKET_DIR="${TMUX_SOCKET_DIR:-/var/lib/web-cli-guard-tmux}"
SOCKET_PATH="${TMUX_SOCKET_PATH:-${SOCKET_DIR}/default.sock}"
SESSION_NAME="${TMUX_SESSION_NAME:-agent-main}"
SESSION_WORKDIR="${TMUX_SESSION_WORKDIR:-/srv/web-cli-guard/workdir}"
SESSION_SHELL="${TMUX_SESSION_SHELL:-/bin/bash}"
WEB_USER="${TMUX_WEB_USER:-www-data}"
LOG_DIR="${TMUX_LOG_DIR:-${SOCKET_DIR}/logs}"

mkdir -p "${SOCKET_DIR}" "${LOG_DIR}"
chgrp "${WEB_USER}" "${SOCKET_DIR}" >/dev/null 2>&1 || true
chmod 2770 "${SOCKET_DIR}" >/dev/null 2>&1 || true

log_path="${LOG_DIR}/${SESSION_NAME}.log"
touch "${log_path}"

if ! "${TMUX_BIN}" -S "${SOCKET_PATH}" has-session -t "${SESSION_NAME}" >/dev/null 2>&1; then
  "${TMUX_BIN}" -S "${SOCKET_PATH}" new-session -d -s "${SESSION_NAME}" \
    "cd '${SESSION_WORKDIR}' && export TERM=xterm-256color && exec ${SCRIPT_BIN} -qef -a '${log_path}' -c '${SESSION_SHELL} -i'"
fi

"${TMUX_BIN}" -S "${SOCKET_PATH}" server-access -a "${WEB_USER}" >/dev/null 2>&1 || true

if [ -S "${SOCKET_PATH}" ]; then
  chgrp "${WEB_USER}" "${SOCKET_PATH}" || true
  chmod 660 "${SOCKET_PATH}" || true
fi
