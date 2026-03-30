#!/usr/bin/env python3
"""Minimal tmux bridge for Web CLI Guard."""

from __future__ import annotations

import argparse
import json
import subprocess
from http import HTTPStatus
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Any
from urllib.parse import parse_qs, urlparse


ALLOWED_KEYS = {
    "Enter": "Enter",
    "C-c": "C-c",
    "Escape": "Escape",
    "Tab": "Tab",
    "Up": "Up",
    "Down": "Down",
    "Left": "Left",
    "Right": "Right",
    "PageUp": "PageUp",
    "PageDown": "PageDown",
}


def json_bytes(payload: dict[str, Any]) -> bytes:
    return json.dumps(payload, ensure_ascii=False).encode("utf-8")


class TmuxBridge:
    def __init__(self, socket_path: str, allowed_sessions: list[str]) -> None:
        self.socket_path = socket_path
        self.allowed_sessions = set(allowed_sessions)

    def _tmux(self, *args: str) -> subprocess.CompletedProcess[str]:
        cmd = ["tmux", "-S", self.socket_path, *args]
        return subprocess.run(cmd, capture_output=True, text=True, check=False)

    def _require_allowed_session(self, session: str) -> None:
        if not session or session not in self.allowed_sessions:
            raise ValueError("Unknown or disallowed session.")

    def list_sessions(self) -> list[dict[str, str]]:
        result = self._tmux("list-sessions", "-F", "#{session_name}")
        if result.returncode != 0:
            raise RuntimeError(result.stderr.strip() or "tmux list-sessions failed")
        names = [line.strip() for line in result.stdout.splitlines() if line.strip()]
        return [{"name": name} for name in names if name in self.allowed_sessions]

    def capture(self, session: str) -> str:
        self._require_allowed_session(session)
        result = self._tmux("capture-pane", "-p", "-t", session)
        if result.returncode != 0:
            raise RuntimeError(result.stderr.strip() or "tmux capture-pane failed")
        return result.stdout

    def send_text(self, session: str, text: str, append_enter: bool) -> None:
        self._require_allowed_session(session)
        result = self._tmux("send-keys", "-t", session, text)
        if result.returncode != 0:
            raise RuntimeError(result.stderr.strip() or "tmux send-keys failed")
        if append_enter:
            enter_result = self._tmux("send-keys", "-t", session, "Enter")
            if enter_result.returncode != 0:
                raise RuntimeError(enter_result.stderr.strip() or "tmux send Enter failed")

    def send_key(self, session: str, key: str) -> None:
        self._require_allowed_session(session)
        if key not in ALLOWED_KEYS:
            raise ValueError("Unknown or disallowed key.")
        result = self._tmux("send-keys", "-t", session, ALLOWED_KEYS[key])
        if result.returncode != 0:
            raise RuntimeError(result.stderr.strip() or "tmux send-keys failed")


class BridgeHandler(BaseHTTPRequestHandler):
    bridge: TmuxBridge
    bearer_token: str

    server_version = "WebCliGuardPythonBridge/0.1"

    def do_GET(self) -> None:  # noqa: N802
        if not self._authorize():
            return

        parsed = urlparse(self.path)
        if parsed.path == "/health":
            self._send_json(HTTPStatus.OK, {"ok": True, "service": "web-cli-guard-python-bridge"})
            return

        if parsed.path == "/sessions":
            self._safe_call(lambda: {"ok": True, "sessions": self.bridge.list_sessions()})
            return

        if parsed.path == "/capture":
            query = parse_qs(parsed.query)
            session = (query.get("session") or [""])[0]
            self._safe_call(
                lambda: {"ok": True, "session": session, "output": self.bridge.capture(session)}
            )
            return

        self._send_error_json(HTTPStatus.NOT_FOUND, "not_found", "Unknown endpoint.")

    def do_POST(self) -> None:  # noqa: N802
        if not self._authorize():
            return

        parsed = urlparse(self.path)
        payload = self._read_json()
        if payload is None:
            return

        if parsed.path == "/send-text":
            session = str(payload.get("session") or "")
            text = str(payload.get("text") or "")
            append_enter = bool(payload.get("append_enter", True))

            def op() -> dict[str, Any]:
                self.bridge.send_text(session, text, append_enter)
                return {"ok": True, "session": session}

            self._safe_call(op)
            return

        if parsed.path == "/send-key":
            session = str(payload.get("session") or "")
            key = str(payload.get("key") or "")

            def op() -> dict[str, Any]:
                self.bridge.send_key(session, key)
                return {"ok": True, "session": session, "key": key}

            self._safe_call(op)
            return

        self._send_error_json(HTTPStatus.NOT_FOUND, "not_found", "Unknown endpoint.")

    def log_message(self, fmt: str, *args: Any) -> None:
        return

    def _read_json(self) -> dict[str, Any] | None:
        try:
            content_length = int(self.headers.get("Content-Length", "0"))
        except ValueError:
            self._send_error_json(HTTPStatus.BAD_REQUEST, "bad_request", "Invalid content length.")
            return None

        raw = self.rfile.read(content_length) if content_length > 0 else b"{}"
        try:
            payload = json.loads(raw.decode("utf-8") or "{}")
        except json.JSONDecodeError:
            self._send_error_json(HTTPStatus.BAD_REQUEST, "bad_request", "Invalid JSON body.")
            return None
        if not isinstance(payload, dict):
            self._send_error_json(HTTPStatus.BAD_REQUEST, "bad_request", "JSON body must be an object.")
            return None
        return payload

    def _authorize(self) -> bool:
        auth_header = self.headers.get("Authorization", "")
        expected = f"Bearer {self.bearer_token}"
        if auth_header != expected:
            self._send_error_json(HTTPStatus.UNAUTHORIZED, "unauthorized", "Missing or invalid token.")
            return False
        return True

    def _safe_call(self, fn: Any) -> None:
        try:
            payload = fn()
            self._send_json(HTTPStatus.OK, payload)
        except ValueError as exc:
            self._send_error_json(HTTPStatus.FORBIDDEN, "forbidden", str(exc))
        except RuntimeError as exc:
            self._send_error_json(HTTPStatus.BAD_GATEWAY, "bridge_error", str(exc))

    def _send_json(self, status: HTTPStatus, payload: dict[str, Any]) -> None:
        body = json_bytes(payload)
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def _send_error_json(self, status: HTTPStatus, code: str, message: str) -> None:
        self._send_json(status, {"ok": False, "error": code, "message": message})


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Minimal tmux bridge for Web CLI Guard")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=8765)
    parser.add_argument("--token", required=True, help="Shared bearer token")
    parser.add_argument("--socket", required=True, help="tmux socket path")
    parser.add_argument(
        "--allow-session",
        action="append",
        dest="allowed_sessions",
        default=[],
        help="Allowed tmux session name. Repeat for multiple sessions.",
    )
    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()
    if not args.allowed_sessions:
        parser.error("At least one --allow-session is required.")

    BridgeHandler.bridge = TmuxBridge(args.socket, args.allowed_sessions)
    BridgeHandler.bearer_token = args.token

    server = ThreadingHTTPServer((args.host, args.port), BridgeHandler)
    print(f"Listening on http://{args.host}:{args.port}")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()


if __name__ == "__main__":
    main()
