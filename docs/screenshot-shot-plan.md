# Screenshot Shot Plan

Use this plan when capturing the first public screenshots for the repository.

## General Rules

- use a desktop browser width around `1400px`
- keep one browser tab and one focused content area
- hide unrelated browser bookmarks or personal profile details
- prefer light theme for the first release unless the product branding changes later
- avoid any real hostnames, emails, tokens, or internal path names

## Screenshot 1

### File

`assets/screenshots/wp-demo-console.png`

### Source

WordPress demo plugin console rendered from:

- shortcode: `[web_cli_guard_console]`

### Goal

Show the main operator experience:

- session switching
- line-output monitoring
- quick command buttons
- text command input

### Suggested State

- active session: `agent-main`
- visible output includes `help` or `status`
- one session chip is clearly selected
- the safety note about simulated commands is visible near the bottom

### Keep In Frame

- page title or plugin title
- session selector
- console output area
- quick command row
- command input area

## Screenshot 2

### File

`assets/screenshots/wp-settings-page.png`

### Source

WordPress admin settings page for the plugin.

### Goal

Show how a real deployment could configure a bridge-backed runtime without exposing sensitive values in the plugin code.

### Suggested State

- fields visible:
  - `Bridge URL`
  - `Bridge Token`
  - `Runtime User`
  - `Allowed Sessions`
- example values should be clearly fake, such as:
  - `https://bridge.example.test/tmux`
  - `demo-token-redacted`
  - `tmuxsvc`
  - `agent-main,repo-main`

### Keep In Frame

- settings heading
- all four fields
- explanatory note that the public plugin remains a demo

## Screenshot 3

### File

`assets/screenshots/php-demo-console.png`

### Source

Plain PHP demo served from:

- [index.php](/var/www/html/server/oss/web-cli-guard/php-demo/index.php)

### Goal

Show that the operator flow is framework-agnostic and does not depend on WordPress.

### Suggested State

- active session: `repo-main`
- visible output includes `status`
- the safety note is visible
- at least one quick command button is visible

### Keep In Frame

- page title
- session selector
- console output
- quick command row
- text command area

## Screenshot 4

### File

`assets/screenshots/approval-flow.png`

### Source

This can be either:

- a mocked design based on the public repo, or
- a redacted capture from your internal implementation

### Goal

Show the step-up verification concept for elevated actions.

### Suggested State

- a sensitive command is pending approval
- a code entry or approval message is visible
- the UI clearly distinguishes normal commands from elevated commands

### Good Example Copy

- `Pending verification for elevated command`
- `Requested action: restart application service`
- `Enter verification code to continue`

### Avoid

- real email addresses
- real service names tied to private infrastructure
- real one-time codes
- anything that implies unrestricted root access

## Final Check

Before committing real screenshots:

1. confirm the README paths still point to the intended filenames
2. zoom out on GitHub and confirm the text remains readable
3. make sure no screenshot contradicts the "safe demo" positioning
4. if you keep the SVG placeholders, replace only after the PNG versions are ready
