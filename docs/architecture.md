# Architecture

English | [繁體中文](./architecture.zh-TW.md)

## Summary

`Web CLI Guard` exposes an existing CLI tool through a controlled web console.

The main product idea is not "web shell first". The main idea is:

- use the web to inspect current AI CLI work
- keep long-running CLI sessions observable
- allow selected remote operations
- preserve safety by relying on OS-level least privilege
- add extra verification on top for elevated actions

The design is:

`Browser -> Web App -> Narrow Bridge -> tmux -> CLI Process`

## Components

## 1. Browser UI

The browser should provide:

- session selection
- line output view
- readonly TUI monitor
- restricted special keys
- text command send
- operator-visible audit/log feedback

The browser should not talk to `tmux` directly.

The browser is best treated as an operator dashboard and interaction layer, not as the primary execution environment.

## 2. Web App

The web layer should:

- authenticate the operator
- authorize access to the console
- validate target host and session
- enforce session locks
- decide whether a command requires elevated verification
- proxy only approved bridge actions

Typical actions:

- `list`
- `capture`
- `monitor`
- `send`
- `logs`
- `lock-status`
- `release`

## 3. Narrow Bridge

The bridge is a small process or endpoint that exposes only a tiny command set to the web app.

It should not expose arbitrary shell execution.

Typical bridge behavior:

- accept only localhost traffic or a trusted reverse proxy
- require a shared token or mTLS
- allow only:
  - `list-sessions`
  - `capture-pane`
  - `send-keys`
- reject unknown commands

## 4. tmux Session Layer

`tmux` provides:

- persistent session state
- a stable target for send/capture
- a separation point between web requests and the CLI process

Example session names:

- `www-main`
- `svr-main`
- `agent-main`

## 5. Runtime User

The CLI should run as a dedicated low-privilege OS user, not as `root`.

This user should:

- have no `sudo`
- have a narrow writable surface
- have a controlled home/config directory

This is where the meaningful sandbox boundary lives. If the runtime user cannot write to a path or invoke privileged tooling, the web-exposed CLI should not be able to do so either.

## 6. Audit + Locking

Useful operational controls:

- audit log for `send-text` and `send-key`
- per-session lock ownership
- idle timeout and release

## 7. Elevated Verification

For high-risk commands, the web app can require:

- email OTP
- manager approval
- a second confirmation flow

This should happen in the web app before the command is sent into `tmux`.

## Request Flow

## Normal command

1. Operator opens console
2. Web app checks auth and allowed target/session
3. Operator sends `pwd`
4. Web app logs the request and forwards `send`
5. Bridge calls `tmux send-keys`
6. Web app refreshes capture output

## Elevated command

1. Operator sends `systemctl restart ...`
2. Web app classifies it as elevated
3. Web app issues OTP or approval challenge
4. Operator completes verification
5. Web app sends the exact validated command
6. Audit log records the action

## Why tmux Instead of Direct PTY Per Request

- easier persistence
- simpler operator handoff
- better compatibility with CLI/TUI tools
- easier capture for readonly monitoring

## Tradeoffs

- `tmux` is not a sandbox by itself
- TUI rendering in a browser is approximate
- elevated command detection is policy, not proof
- WordPress is convenient, but not the only host option
