# Release Checklist

Use this before pushing the project to a public GitHub repository.

## Secrets

- remove all API keys
- remove internal bridge tokens
- remove private SMTP or LDAP settings
- remove internal-only hostnames and IPs

## Branding

- replace company-specific names
- replace company-specific email addresses
- replace internal paths where needed

## Security Review

- verify the runtime user has no `sudo`
- verify group membership is expected
- verify writable directories are minimal
- verify only allowed `tmux` sessions are exposed
- verify elevated commands require OTP or approval

## Docs

- confirm `README.md` matches actual repo contents
- confirm `SECURITY.md` matches the real threat model
- document what is intentionally not included

## Examples

- make sure example tokens are placeholders
- make sure example service files are clearly marked as examples
- make sure scripts do not include production domains by accident

## Packaging

- validate WordPress plugin PHP syntax
- check shell example scripts for obvious syntax issues
- add screenshots only if they do not reveal private data

## GitHub Setup

- choose a repo name
- choose an OSS license
- add topics and a short description
- decide whether issues and discussions should be enabled
