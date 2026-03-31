# Node Bridge Example

This folder contains a minimal tmux bridge implemented with the Node.js standard library.

It is intentionally small:

- no Express dependency
- no arbitrary shell execution
- localhost-oriented by default
- token-protected
- session allowlist based

## What It Does

The example server exposes:

- `GET /health`
- `GET /sessions`
- `GET /capture?session=<name>`
- `POST /send-text`
- `POST /send-key`

It shells out only to `tmux` subcommands such as:

- `list-sessions`
- `capture-pane`
- `send-keys`

## Run

Example:

```bash
node server.js \
  --host 127.0.0.1 \
  --port 8766 \
  --token change-me \
  --socket /tmp/tmux-example.sock \
  --allow-session svr-main \
  --allow-session www-main
```

Then call it with:

```bash
curl -H 'Authorization: Bearer change-me' http://127.0.0.1:8766/health
```

## Notes

- The bridge should usually sit behind a web app, not be exposed directly to the public internet.
- The bridge does not decide whether a command is elevated.
- OTP or approval should happen in the web app before the command is sent here.
- The runtime OS user is still the real privilege boundary.

## Production Hardening

For real use, add at least:

- systemd service confinement
- reverse proxy or local-only binding
- request logging
- rate limiting
- tighter key allowlists
- clearer operator audit correlation
