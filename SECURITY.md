# Security Notes

`Web CLI Guard` is meant to reduce operational risk, not eliminate it.

## Core Principle

The web layer must not be your main trust boundary.

The real boundary should be:

- a low-privilege Linux user
- restricted filesystem permissions
- limited network reach
- narrow `tmux` bridge commands

## Minimum Safe Defaults

- Use a dedicated OS account such as `tmuxsvc`
- Do not add that account to `sudo`, `wheel`, or container admin groups
- Keep writable paths limited to:
  - session logs
  - cache/state
  - explicit work directories
- Allow only known `tmux` sessions
- Audit every send action
- Add lock ownership to prevent conflicting operators
- Require OTP/approval for dangerous commands

## Commands That Usually Need Extra Verification

- `sudo`
- `su`
- `systemctl`
- `service`
- `reboot`
- `shutdown`
- `mount`
- `umount`
- `passwd`
- `useradd`
- `usermod`
- `userdel`
- `iptables`
- `ufw`
- `killall`
- `pkill`
- `rm -rf`

## What This Project Does Not Guarantee

- perfect shell isolation
- protection against every dangerous command variant
- protection from a compromised WordPress admin account
- protection from a privileged local attacker

## Secret Handling

Do not commit:

- API keys
- internal bridge tokens
- production SMTP credentials
- LDAP credentials
- private endpoints

Use environment variables or local config files outside the repository.

## Recommended Review Before Production

- validate Linux user/group permissions
- validate `sudo -l` for the runtime account
- validate session allowlists
- test audit logs
- test OTP or approval bypass resistance
- test command injection boundaries
- test failure mode when `tmux` crashes or is missing
