# Web CLI Guard v0.2.1

`v0.2.1` expands `web-cli-guard` with a more complete Node.js route for teams that want a small JavaScript-based operator surface while keeping the same narrow tmux bridge model.

Use this as the GitHub Release title:

`v0.2.1 - Node bridge and dual-runtime operator path`

## Highlights

- added a zero-dependency `node-demo/` operator UI
- added `.env` support and a built-in bridge test action to the Node demo
- added a minimal real `node-bridge/` with:
  - `GET /health`
  - `GET /sessions`
  - `GET /capture`
  - `POST /send-text`
  - `POST /send-key`
- added a `systemd` service example for the Node bridge
- updated quickstart and README docs to clarify Python vs Node deployment choices
- extended CI to syntax-check both `node-demo/server.js` and `node-bridge/server.js`

## Why This Release Matters

`web-cli-guard` is intended as one practical way to safely reach an existing CLI environment from the web, whether that environment lives at home, in the office, or on an internal server.

With `v0.2.1`, the repository now offers:

- a Python bridge path
- a Node bridge path
- demo UIs in PHP and Node.js
- a WordPress-based operator UI starter

This makes it easier to adapt the same architecture to teams with different runtime preferences without changing the underlying security model:

- low-privilege OS user
- narrow bridge
- tmux session allowlist
- approval or OTP in the web layer

## Suggested Release Body

```md
`v0.2.1` expands `web-cli-guard` with a more complete Node.js route for teams that want a small JavaScript-based operator surface while keeping the same narrow tmux bridge model.

### Highlights

- added a zero-dependency `node-demo/` operator UI
- added `.env` support and a built-in bridge test action to the Node demo
- added a minimal real `node-bridge/` with:
  - `GET /health`
  - `GET /sessions`
  - `GET /capture`
  - `POST /send-text`
  - `POST /send-key`
- added a `systemd` service example for the Node bridge
- updated quickstart and README docs to clarify Python vs Node deployment choices
- extended CI to syntax-check both `node-demo/server.js` and `node-bridge/server.js`

### Why This Release Matters

`web-cli-guard` is intended as one practical way to safely reach an existing CLI environment from the web, whether that environment lives at home, in the office, or on an internal server.

With `v0.2.1`, the repository now offers:

- a Python bridge path
- a Node bridge path
- demo UIs in PHP and Node.js
- a WordPress-based operator UI starter

This makes it easier to adapt the same architecture to teams with different runtime preferences without changing the underlying security model:

- low-privilege OS user
- narrow bridge
- tmux session allowlist
- approval or OTP in the web layer
```

## Verification

- `php -l` on the public PHP demo and WordPress plugin demo
- `python3 -m py_compile` on the Python bridge files
- `node --check node-demo/server.js`
- `node --check node-bridge/server.js`

## Compare

- range: `v0.2.0...v0.2.1`

## Next Step

Start with:

1. `docs/quickstart.md`
2. `python-bridge/` or `node-bridge/`
3. `php-demo/`, `node-demo/`, or `wordpress-plugin/web-cli-guard/`
