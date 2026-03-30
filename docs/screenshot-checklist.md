# Screenshot Checklist

Store public screenshots in `assets/screenshots/` using these filenames:

- `wp-demo-console.svg` or `wp-demo-console.png`
- `wp-settings-page.svg` or `wp-settings-page.png`
- `php-demo-console.svg` or `php-demo-console.png`
- `approval-flow.svg` or `approval-flow.png`

Recommended capture order:

1. WordPress demo console
2. WordPress settings page
3. Plain PHP demo console
4. Approval or OTP flow

Recommended image style:

- desktop browser width
- crop tightly around the relevant operator UI
- keep text readable at GitHub README scale
- prefer PNG for UI captures

Before exporting screenshots:

- replace any real hostnames with demo values
- replace any real emails with placeholders
- avoid prompts or logs that reveal private filesystem details
- avoid content that suggests unrestricted root shell access

After exporting screenshots:

1. copy them into `assets/screenshots/`
2. either replace the matching `.svg` placeholder or switch the README links to `.png`
3. confirm they render in the README on GitHub

Suggested alt-text themes:

- active AI CLI session viewed from a web console
- runtime configuration for restricted operator access
- plain PHP operator console demo
- elevated command approval flow
