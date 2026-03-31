# Quickstart

This is the shortest path from repository clone to a real tmux-backed bridge.

## Goal

At the end of this quickstart, you should have:

- a real tmux session
- a local Python bridge bound to `127.0.0.1`
- an allowlist for one or two session names
- either the plain PHP demo, the Node.js demo, or the WordPress plugin talking to that bridge

## 1. Create a Low-Privilege Runtime User

Example:

```bash
sudo useradd --create-home --shell /bin/bash tmuxsvc
```

Do not grant `sudo`.

## 2. Prepare a Work Directory

Example:

```bash
sudo mkdir -p /srv/web-cli-guard/workdir
sudo chown -R tmuxsvc:tmuxsvc /srv/web-cli-guard
```

## 3. Start a Shared tmux Session

Use the example bootstrap script from this repository:

```bash
sudo cp examples/scripts/tmuxsvc-bootstrap.example.sh /usr/local/bin/web-cli-guard-bootstrap
sudo chmod +x /usr/local/bin/web-cli-guard-bootstrap
sudo -u tmuxsvc TMUX_SESSION_NAME=agent-main TMUX_SESSION_WORKDIR=/srv/web-cli-guard/workdir /usr/local/bin/web-cli-guard-bootstrap
```

This creates a tmux session and starts an interactive shell inside it.

## 4. Start the Python Bridge

From this repository:

```bash
python3 python-bridge/server.py \
  --host 127.0.0.1 \
  --port 8765 \
  --token change-me \
  --socket /var/lib/web-cli-guard-tmux/default.sock \
  --allow-session agent-main
```

If you want two sessions:

```bash
python3 python-bridge/server.py \
  --host 127.0.0.1 \
  --port 8765 \
  --token change-me \
  --socket /var/lib/web-cli-guard-tmux/default.sock \
  --allow-session agent-main \
  --allow-session repo-main
```

If you prefer Node.js instead, the repository also includes:

```bash
node node-bridge/server.js \
  --host 127.0.0.1 \
  --port 8766 \
  --token change-me \
  --socket /var/lib/web-cli-guard-tmux/default.sock \
  --allow-session agent-main
```

## 5. Verify the Bridge

Check health:

```bash
curl -H 'Authorization: Bearer change-me' http://127.0.0.1:8765/health
```

List sessions:

```bash
curl -H 'Authorization: Bearer change-me' http://127.0.0.1:8765/sessions
```

Capture pane output:

```bash
curl -H 'Authorization: Bearer change-me' 'http://127.0.0.1:8765/capture?session=agent-main'
```

## 6. Connect the Plain PHP Demo

Export:

```bash
export WCG_BRIDGE_URL=http://127.0.0.1:8765
export WCG_BRIDGE_TOKEN=change-me
```

Then run:

```bash
cd php-demo
php -S 127.0.0.1:8080
```

Open:

`http://127.0.0.1:8080/index.php`

The UI badge should switch from demo mode to bridge mode.

## 7. Connect the WordPress Plugin

In the plugin settings page:

- set `Bridge URL` to `http://127.0.0.1:8765`
- set `Bridge Token` to your shared token
- set `Allowed Sessions` to the same tmux allowlist you want to expose

Then use:

- `Test Bridge Connection`
- the console shortcode UI

If the bridge is unavailable, the public plugin falls back to demo mode.

## 8. Connect the Node.js Demo

Create a local config file:

```bash
cd node-demo
cp .env.example .env
```

Edit `.env` with your bridge values, for example:

```dotenv
WCG_BRIDGE_URL=http://127.0.0.1:8765
WCG_BRIDGE_TOKEN=change-me
```

Then run:

```bash
cd node-demo
node server.js
```

Open:

`http://127.0.0.1:8090`

This gives you the same operator flow in a small Node.js runtime.

If you prefer shell exports instead of `.env`, those still work and override file-based settings.

## Optional: Use systemd

The repo already includes a service example:

- `examples/systemd/dreamj-tmuxsvc.service.example`

This is useful when you want tmux sessions to survive reboots.

## Operational Notes

- Keep the bridge on localhost unless you have a strong reason not to.
- The bridge is not the main sandbox boundary.
- The runtime OS user should remain low privilege.
- OTP or approval should still happen in the web layer before sending elevated commands.
