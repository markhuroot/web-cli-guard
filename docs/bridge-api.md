# Bridge API

English | [繁體中文](./bridge-api.zh-TW.md)

## Purpose

This document defines a minimal bridge contract for a tmux-backed web CLI console.

The bridge should stay narrow on purpose. It is not a generic shell API.

Recommended bridge actions:

- `health`
- `list-sessions`
- `capture-pane`
- `send-text`
- `send-key`

## Transport

The examples in this repository assume:

- HTTP JSON
- localhost binding only
- a shared bearer token between the web app and the bridge

You can replace this with another transport if the same behavior is preserved.

## Security Expectations

The bridge should:

- bind to `127.0.0.1` unless there is a strong reason not to
- reject requests without a valid token
- allow only configured session names
- reject unknown actions
- reject unknown special keys
- never expose arbitrary shell execution

The bridge is still not the main sandbox boundary.

The real boundary should be:

- the runtime OS user
- tmux session allowlists
- limited writable paths
- no `sudo`

## Authentication

Use an `Authorization` header:

```http
Authorization: Bearer YOUR_SHARED_TOKEN
```

## Endpoints

### `GET /health`

Returns a basic health response.

Example response:

```json
{
  "ok": true,
  "service": "web-cli-guard-python-bridge"
}
```

### `GET /sessions`

Returns the allowed tmux sessions that currently exist.

Example response:

```json
{
  "ok": true,
  "sessions": [
    {
      "name": "svr-main"
    },
    {
      "name": "www-main"
    }
  ]
}
```

### `GET /capture?session=<name>`

Captures the current pane output for one allowed session.

Example response:

```json
{
  "ok": true,
  "session": "svr-main",
  "output": "user@host:~$ pwd\n/var/www/html/server\n"
}
```

### `POST /send-text`

Sends literal text to a session. Optionally appends Enter.

Request body:

```json
{
  "session": "svr-main",
  "text": "pwd",
  "append_enter": true
}
```

Example response:

```json
{
  "ok": true,
  "session": "svr-main"
}
```

### `POST /send-key`

Sends a special key from a narrow allowlist.

Request body:

```json
{
  "session": "svr-main",
  "key": "Enter"
}
```

Recommended key allowlist:

- `Enter`
- `C-c`
- `Escape`
- `Tab`
- `Up`
- `Down`
- `Left`
- `Right`
- `PageUp`
- `PageDown`

Example response:

```json
{
  "ok": true,
  "session": "svr-main",
  "key": "Enter"
}
```

## Error Format

Use a stable JSON error body:

```json
{
  "ok": false,
  "error": "forbidden",
  "message": "Unknown or disallowed session."
}
```

Suggested error codes:

- `unauthorized`
- `forbidden`
- `bad_request`
- `not_found`
- `bridge_error`

## Elevated Commands

The bridge should not be responsible for OTP or approval workflows.

That logic should stay in the web app:

1. operator submits a command
2. web app classifies the command as normal or elevated
3. if elevated, web app completes OTP or approval
4. web app sends the exact approved command to the bridge

This keeps the bridge narrow and easier to reason about.

## Compatibility Notes

The public demo plugin and PHP demo in this repository do not call a real bridge yet.

They are meant to show the operator flow and UI shape first.

The `python-bridge/` and `node-bridge/` examples are minimal runtime-facing bridges included in this repository.
