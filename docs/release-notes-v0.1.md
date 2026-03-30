# Release Notes: v0.1.0

## Summary

`Web CLI Guard` is a starter project for exposing existing CLI tools through a controlled web interface while keeping the real execution inside a low-privilege OS runtime.

This first public version focuses on:

- architecture and security documentation
- `tmux`-based runtime patterns
- a WordPress demo plugin
- a framework-agnostic plain PHP demo

## Included

- docs for architecture, threat model, release prep, and GitHub publishing
- example `systemd` units
- example bootstrap and helper scripts
- a WordPress demo console with:
  - session switching
  - line-console style output
  - simulated command send flow
  - a settings-page demo for bridge/runtime values
- a plain PHP demo with:
  - session switching
  - simulated capture/send flow
  - no WordPress dependency

## Positioning

This is a starter and reference implementation, not a production-ready hardened product.

It is meant to help teams explore a pattern built around:

- `tmux` persistence
- narrow bridge actions
- low-privilege runtime users
- auditability
- elevated-command verification

## Not Included

- a production bridge package
- real shell execution in the demos
- provider-specific adapters
- a full multi-tenant or root-oriented security model

## Recommended Next Steps

- connect the demos to a real narrow bridge
- add environment-based configuration
- add provider adapters for specific AI CLIs
- formalize approval or OTP flows for elevated commands
