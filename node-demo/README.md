# Node Demo

This folder contains a zero-dependency Node.js demo for Web CLI Guard.

It uses the built-in `http` module only.

## What It Can Do

- run as a safe local demo
- switch to real bridge mode with environment variables
- provide a browser UI for:
  - session switching
  - pane capture
  - text send
  - special-key send

## Run

Demo mode:

```bash
cd node-demo
node server.js
```

Then open:

`http://127.0.0.1:8090`

## Bridge Mode

```bash
export WCG_BRIDGE_URL=http://127.0.0.1:8765
export WCG_BRIDGE_TOKEN=change-me
cd node-demo
node server.js
```

In bridge mode, the same UI proxies:

- `GET /sessions`
- `GET /capture`
- `POST /send-text`
- `POST /send-key`

to the configured tmux bridge.

## Why This Demo Exists

Some teams prefer a small Node.js operator surface instead of PHP or WordPress.

This demo shows the same architecture in a stack that is easy to run at home, in the office, or behind an internal reverse proxy.
