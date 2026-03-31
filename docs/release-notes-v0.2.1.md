# Release Notes v0.2.1

`v0.2.1` expands `web-cli-guard` from a Python-first bridge starter into a dual-runtime starter with a fuller Node.js path.

## Highlights

- added a zero-dependency `node-demo/` operator UI
- added local `.env` support for the Node demo
- added a built-in bridge health and session test panel to the Node demo
- added a minimal real `tmux` bridge in `node-bridge/`
- added a `systemd` example for keeping the Node bridge running across reboots
- updated docs to compare Python and Node runtime paths more clearly

## Why This Matters

The repository now supports two small runtime-facing bridge paths:

- `python-bridge/` for teams already comfortable with Python on admin hosts
- `node-bridge/` for teams that prefer a Node.js-only deployment surface

That means teams can keep the same narrow bridge contract while choosing the runtime that better fits their existing operators, packaging, and maintenance habits.

## Notes

- this is still a starter, not a production-ready control plane
- the runtime OS user remains the main execution boundary
- OTP or approval logic should still stay in the web layer
- both bridge implementations intentionally avoid arbitrary shell execution endpoints
