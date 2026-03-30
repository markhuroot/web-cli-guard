# WordPress Plugin Demo

This folder contains a WordPress demo plugin for a web-based CLI console.

It is intentionally small and generic.

What it demonstrates now:

- shortcode-driven console shell
- session switching
- AJAX-powered line console refresh
- text send and restricted key buttons
- demo session persistence per logged-in user with transients
- a settings page for bridge/runtime configuration placeholders
- security notes in UI

What it does not yet ship:

- a production bridge
- real shell execution
- OTP backend wiring
- role-management UI

The plugin is intentionally safe by default: commands are simulated and appended to demo output, not executed on the host.

Use this as a clean starting point for a public plugin package or a documentation demo.
