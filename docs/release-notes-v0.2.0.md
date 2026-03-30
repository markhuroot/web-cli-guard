# Release Notes v0.2.0

`v0.2.0` turns `web-cli-guard` from a concept-and-demo repository into a more practical starter kit.

## Highlights

- added a minimal real `tmux` bridge in `python-bridge/`
- documented the bridge API in `docs/bridge-api.md`
- made both public UIs bridge-aware:
  - `php-demo/`
  - `wordpress-plugin/web-cli-guard/`
- added a bridge test action in the WordPress settings page
- added `docs/quickstart.md`
- added a containerized bridge example in `examples/docker-compose.yml.example`
- added GitHub Actions CI for PHP lint and Python compile checks

## Why This Matters

The repository now covers all of these layers in one public starter:

- browser UI demo
- WordPress operator UI demo
- tmux bridge contract
- working minimal bridge implementation
- operator-side bridge verification
- quickstart deployment path

## Notes

- this is still a starter, not a finished production product
- OTP or approval logic should still live in the web layer
- the runtime OS user remains the real execution boundary
