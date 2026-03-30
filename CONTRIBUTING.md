# Contributing

Thanks for contributing to Web CLI Guard.

## Before Opening a PR

1. keep changes focused
2. avoid committing secrets or environment-specific values
3. document behavior changes in `README.md` or `docs/` when needed
4. validate any PHP files with `php -l`
5. explain whether a change affects:
   - UI only
   - bridge/runtime design
   - security model

## Contribution Priorities

Good contributions for this project:

- safer bridge patterns
- better docs and threat modeling
- cleaner demo UX
- framework-agnostic examples
- provider adapters that do not weaken the security model

Less useful contributions:

- turning the demo into an unrestricted web terminal
- adding root-oriented defaults
- hardcoding vendor-specific secrets or infrastructure values

## Coding Guidance

- keep examples generic
- prefer low-privilege defaults
- make security assumptions explicit
- separate demo mode from real execution mode

## Issues and PRs

When filing an issue or PR, include:

- what you expected
- what happened instead
- whether the change is demo-only or intended for real deployments
- any security tradeoff you are making
