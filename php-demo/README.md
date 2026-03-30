# Plain PHP Demo

This folder contains a framework-agnostic PHP demo for Web CLI Guard.

It mirrors the same public-safe concept as the WordPress demo:

- session switching
- line-console style output
- simulated command sending
- restricted key buttons
- no real shell execution

## Files

- `index.php`
  The browser UI
- `api.php`
  A small JSON endpoint for capture/send actions

## Purpose

This demo is for people who want to understand the operator flow without installing WordPress.

It is useful for:

- local prototyping
- internal demos
- adapting the pattern to another PHP stack

## Run

From this directory:

```bash
php -S 127.0.0.1:8080
```

Then open:

`http://127.0.0.1:8080/index.php`

## Safety

This demo does not execute shell commands.

It simulates command output in PHP session state so the UI behavior can be evaluated without exposing the host.
