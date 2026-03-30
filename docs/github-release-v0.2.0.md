# Web CLI Guard v0.2.0

`v0.2.0` moves `web-cli-guard` from a concept-and-demo repository toward a practical starter for web-facing control of existing CLI and AI CLI workflows.

## Highlights

- added a minimal real tmux bridge in `python-bridge/`
- documented the bridge contract in `docs/bridge-api.md`
- made both public UIs bridge-aware:
  - `php-demo/`
  - `wordpress-plugin/web-cli-guard/`
- added a bridge connection test action in the WordPress settings page
- added `docs/quickstart.md`
- added `examples/docker-compose.yml.example`
- added GitHub Actions CI for PHP lint and Python compile checks
- added `CHANGELOG.md`

## Why This Release Matters

The project now covers the main layers needed for a realistic starter:

- browser UI demo
- WordPress operator UI demo
- tmux bridge API
- working minimal Python bridge
- bridge-aware demo clients
- bridge health/session verification in WordPress
- quickstart deployment guidance
- basic CI validation

## Included In This Repository

- a narrow tmux bridge pattern
- a minimal Python bridge implementation
- a bridge-aware plain PHP demo
- a bridge-aware WordPress plugin demo
- example `systemd`, bootstrap, and compose files
- security, architecture, threat-model, and quickstart docs

## Not Included Yet

- a full production bridge package with complete hardening
- built-in OTP or approval backend workflows
- provider-specific adapters for Codex, Claude, or other CLI agents
- a packaged installer

## Recommended Next Step

If you are trying this repository for the first time:

1. read `docs/quickstart.md`
2. run `python-bridge/server.py`
3. connect `php-demo/` or the WordPress plugin to the bridge
4. keep the runtime under a low-privilege OS user

## Verification Notes

This release was checked with:

- `php -l php-demo/api.php`
- `php -l php-demo/index.php`
- `php -l wordpress-plugin/web-cli-guard/web-cli-guard.php`
- `python3 -m py_compile python-bridge/server.py`
- `python3 -m py_compile examples/scripts/tmuxsvc-http.example.py`

## Compare

- `v0.1.1...v0.2.0`
