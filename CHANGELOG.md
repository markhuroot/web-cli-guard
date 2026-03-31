# Changelog

All notable changes to this project will be documented in this file.

## v0.2.1

- added a zero-dependency `node-demo/` web UI for demo or bridge-backed operator workflows
- added local `.env` support and a built-in bridge test panel to `node-demo/`
- added a minimal `node-bridge/` implementation for real tmux-backed session access
- added a `systemd` example for keeping the Node bridge alive across reboots
- expanded `docs/quickstart.md` and the bilingual README files with Python vs Node bridge/runtime guidance
- extended GitHub Actions CI to syntax-check both the Node demo and the Node bridge

## v0.2.0

- added a minimal `python-bridge/` implementation for real tmux-backed session access
- documented the bridge contract in `docs/bridge-api.md`
- made the plain PHP demo bridge-aware through `WCG_BRIDGE_URL` and `WCG_BRIDGE_TOKEN`
- made the public WordPress plugin bridge-aware with safe fallback to demo mode
- added a WordPress settings-page bridge health/session test action
- added `docs/quickstart.md` and a bridge `docker-compose` example
- added GitHub Actions CI for PHP linting and Python compilation

## v0.1.1

- refined public positioning and GitHub About copy
- added screenshot gallery and public screenshot assets
- added release notes and publishing support docs

## v0.1.0

- initial public scaffold
- WordPress demo console
- plain PHP demo
- bilingual README and core docs
