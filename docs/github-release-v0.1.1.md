# GitHub Release Draft: v0.1.1

## Suggested Title

`v0.1.1 - README, screenshots, and GitHub release polish`

## Release Body

`Web CLI Guard` is a starter project for exposing existing CLI tools through a controlled web interface while keeping the real execution boundary inside a low-privilege OS runtime.

This `v0.1.1` release is a small follow-up to `v0.1.0` focused on public presentation and GitHub readiness.

### Highlights

- clearer project positioning around:
  - viewing ongoing AI CLI work from the web
  - safer remote operations with a narrow control surface
  - OS-level least privilege, tmux session control, and verification layers
- a README screenshot gallery wired to public assets
- screenshot planning and capture guidance
- public demo images for:
  - WordPress demo console
  - WordPress settings page
  - plain PHP demo
  - approval or OTP flow

### Included

- improved README messaging for operator visibility, remote review, and approval-gated actions
- GitHub About guidance for repository setup
- screenshot guide, checklist, and shot plan docs
- public PNG screenshots for the repo landing page

### Commit Range Since v0.1.0

- `106b81b` Add screenshot placeholders and gallery guidance
- `6db5e4b` Add screenshot capture plan
- `2f8acd4` Add first public screenshots
- `26a6e0a` Add public settings page screenshot
- `25497ef` Prepare v0.1.1 release notes

### Positioning

This project is still a starter and reference implementation, not a production-hardened sandbox product.

It is intended for teams that want a browser-based way to inspect or lightly operate an existing AI CLI session while keeping the real trust boundary in:

- a restricted OS user
- a named `tmux` session
- a narrow bridge
- approval or OTP layers for elevated actions

### Not Included

- a production bridge package
- unrestricted shell execution in the demos
- provider-specific adapters
- a full multi-tenant runtime model

### Suggested Next Step

If you are publishing this release on GitHub, also update the repository About text and topics using:

- [github-publish-notes.md](./github-publish-notes.md)
