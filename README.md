# Web CLI Guard

Web CLI Guard is a small open-source starter for putting existing CLI tools behind a controlled web interface.

It is built around a practical pattern:

- `tmux` for persistent sessions
- a low-privilege Linux user for the real runtime boundary
- a narrow bridge for `list / capture / send`
- audit logs and session locking
- OTP or approval for elevated commands
- an optional WordPress-based operator UI

## Why This Exists

A lot of teams already use CLI-native tools such as:

- `codex`
- `claude`
- local shell assistants
- repo-specific scripts

The difficult part is not the CLI itself. The difficult part is giving people a usable web entrypoint without falling back to a raw unrestricted web terminal.

Done well, this pattern lets operators:

- inspect current AI CLI work from the web
- review ongoing session output without SSH
- use a safer remote-operations surface than a generic web shell
- rely on OS-level least privilege and sandbox boundaries
- add approval or OTP before elevated actions

This project documents one opinionated approach:

1. run the CLI inside a dedicated `tmux` session
2. run that session as a restricted OS user
3. expose only a narrow web bridge
4. add audit logs and per-session locking
5. require extra verification for elevated commands

## Scope

This repository is a starter, not a finished product.

Included today:

- architecture and security docs
- example `systemd` units
- example helper scripts
- a WordPress plugin demo with an interactive safe console
- a WordPress settings-page demo for bridge/runtime configuration
- a plain PHP interactive safe demo

Not included yet:

- a production-ready bridge package
- provider-specific adapters for `codex`, `claude`, or other CLIs
- a full settings UX
- a packaged installer

## Repository Layout

- `docs/architecture.md`
  High-level request flow and component boundaries
- `docs/threat-model.md`
  What this pattern mitigates and what it does not
- `docs/roadmap.md`
  Suggested milestones for turning this into a public release
- `docs/release-checklist.md`
  Sanity checks before publishing to GitHub
- `examples/systemd/`
  Example service units
- `examples/scripts/`
  Example bootstrap/helper scripts
- `wordpress-plugin/web-cli-guard/`
  Minimal WordPress plugin starter
- `php-demo/`
  Framework-agnostic PHP demo with no WordPress dependency

## Security Model

The main trust boundary should be the runtime OS user, not the web UI.

Recommended baseline:

- run the managed shell as a dedicated account such as `tmuxsvc`
- do not grant `sudo`
- keep writable paths narrow
- keep `tmux` sessions on an allowlist
- log every send action
- require OTP or approval for elevated commands

See [SECURITY.md](./SECURITY.md) for the operational model.

In other words, the goal is not to make the browser powerful. The goal is to make the browser a controlled window into an already-restricted runtime.

## Typical Architecture

`Browser -> Web App -> Narrow Bridge -> tmux -> CLI Process`

The browser should never execute shell commands directly.

This architecture is useful when you want web-based visibility into an AI CLI session while still keeping actual execution inside a constrained OS account or sandboxed environment.

The web app should:

- authenticate the operator
- authorize session access
- enforce session locks
- classify elevated commands
- proxy only approved bridge actions

The bridge should allow only a narrow command set such as:

- `list-sessions`
- `capture-pane`
- `send-keys`

## WordPress Use Case

This repository includes a minimal WordPress plugin scaffold because many teams already have an internal WordPress environment and want:

- a simple staff-facing UI
- existing login/session handling
- a familiar admin or portal surface

The plugin in this repo is intentionally minimal. It is meant as a clean public starting point, not a direct dump of an internal production portal.

The current public demo plugin now shows:

- session switching
- line-console style output
- simulated command send flow
- a configuration page for bridge/runtime values

## Good Fit

- internal engineering consoles
- support/admin workflows on one or two servers
- AI CLI access for operators without SSH access
- organizations that want auditability and approval gates

## Bad Fit

- public anonymous shells
- full multi-tenant isolation
- high-assurance sandboxing without OS/container hardening
- environments that require root-like access by default

## Getting Started

1. Read [architecture.md](./docs/architecture.md)
2. Read [threat-model.md](./docs/threat-model.md)
3. Review the example files under `examples/`
4. Adapt the WordPress plugin scaffold or build your own web UI
5. Keep secrets out of the repository

## Publishing Advice

Before pushing this style of project to GitHub:

1. remove any environment-specific branding
2. replace real domains, mail hosts, and tokens
3. verify the runtime account has no unexpected privilege
4. review the release checklist

See [release-checklist.md](./docs/release-checklist.md).

## Suggested Screenshots

If you publish this repo, the most useful first screenshots are:

1. the WordPress demo console
2. the WordPress settings page
3. the plain PHP demo console

See [screenshots.md](./docs/screenshots.md) for suggested captions and what to avoid revealing.

## First Release Draft

For a suggested first GitHub release note, see [release-notes-v0.1.md](./docs/release-notes-v0.1.md).

## Push Template

When your GitHub repository exists, use:

```bash
cd /var/www/html/server/oss/web-cli-guard
git remote add origin <your-github-repo-url>
git push -u origin main
```

## License

This scaffold is released under the MIT License. See [LICENSE](./LICENSE).
