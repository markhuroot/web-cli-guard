#!/usr/bin/env python3
import json
import os
import subprocess
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

SOCKET_PATH = os.environ.get("TMUX_SOCKET_PATH", "/var/lib/web-cli-guard-tmux/default.sock")
SESSION_NAME = os.environ.get("TMUX_SESSION_NAME", "agent-main")
LISTEN_HOST = os.environ.get("TMUX_HELPER_HOST", "127.0.0.1")
LISTEN_PORT = int(os.environ.get("TMUX_HELPER_PORT", "47631"))
HELPER_TOKEN = os.environ.get("TMUX_HELPER_TOKEN", "replace-this-token")


def tmux_run(args):
    proc = subprocess.run(
        ["/usr/bin/tmux", "-S", SOCKET_PATH, *args],
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        text=True,
    )
    return proc.returncode, proc.stdout.strip()


def allowed_args(args):
    if not isinstance(args, list) or not args:
        return False
    return str(args[0]) in {"list-sessions", "capture-pane", "send-keys"}


class Handler(BaseHTTPRequestHandler):
    def _respond(self, status, payload):
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_POST(self):
        if self.client_address[0] not in ("127.0.0.1", "::1"):
            self._respond(403, {"ok": False, "message": "forbidden"})
            return
        if self.headers.get("X-Web-CLI-Guard-Token", "") != HELPER_TOKEN:
            self._respond(403, {"ok": False, "message": "invalid token"})
            return

        try:
            payload = json.loads(self.rfile.read(int(self.headers.get("Content-Length", "0"))) or b"{}")
        except Exception:
            self._respond(400, {"ok": False, "message": "invalid json"})
            return

        args = payload.get("args", [])
        if not allowed_args(args):
            self._respond(400, {"ok": False, "message": "unsupported command"})
            return

        code, output = tmux_run([str(item) for item in args])
        self._respond(200, {"ok": code == 0, "exit_code": code, "output": output})

    def log_message(self, fmt, *args):
        return


if __name__ == "__main__":
    server = ThreadingHTTPServer((LISTEN_HOST, LISTEN_PORT), Handler)
    server.serve_forever()
