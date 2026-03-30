<?php
/**
 * Plugin Name: Web CLI Guard
 * Description: Example WordPress wrapper for a restricted tmux-backed CLI console.
 * Version: 0.2.0
 * Author: Example Maintainer
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class WebCliGuardPlugin
{
    private const SHORTCODE = 'web_cli_guard_console';
    private const CAPABILITY = 'manage_options';
    private const NONCE_ACTION = 'wcg_demo_console';
    private const STATE_PREFIX = 'wcg_demo_state_';
    private const STATE_TTL = 3600;
    private const OPTION_BRIDGE_URL = 'wcg_demo_bridge_url';
    private const OPTION_BRIDGE_TOKEN = 'wcg_demo_bridge_token';
    private const OPTION_RUNTIME_USER = 'wcg_demo_runtime_user';
    private const OPTION_ALLOWED_SESSIONS = 'wcg_demo_allowed_sessions';

    public static function init(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render_console_shortcode']);
        add_action('wp_ajax_wcg_demo_console', [self::class, 'handle_demo_console_ajax']);
        add_action('admin_menu', [self::class, 'register_admin_page']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function render_console_shortcode(): string
    {
        if (!current_user_can(self::CAPABILITY)) {
            return '<div class="wcg-console-denied">You do not have access to this console.</div>';
        }

        $sessions = self::get_available_sessions();
        $defaultSession = (string) ($sessions[0]['name'] ?? '');
        $bridgeMode = self::is_bridge_enabled() ? 'bridge' : 'demo';

        ob_start();
        ?>
        <div class="wcg-console" data-wcg-console="1" data-wcg-default-session="<?php echo esc_attr($defaultSession); ?>" data-wcg-runtime-mode="<?php echo esc_attr($bridgeMode); ?>">
            <style>
                .wcg-console { max-width: 1180px; margin: 24px auto; padding: 24px; border: 1px solid #d7dee8; border-radius: 24px; background: #fff; box-shadow: 0 16px 36px rgba(15,23,42,.06); color: #17212f; }
                .wcg-console h2 { margin: 0 0 8px; font-size: 28px; }
                .wcg-console p { color: #516072; line-height: 1.7; }
                .wcg-grid { display: grid; grid-template-columns: 280px minmax(0,1fr); gap: 16px; margin-top: 18px; }
                .wcg-card { border: 1px solid #d7dee8; border-radius: 20px; background: #fbfdff; padding: 16px; }
                .wcg-card h3 { margin: 0 0 12px; font-size: 16px; }
                .wcg-session { display: grid; gap: 10px; }
                .wcg-session button { min-height: 46px; border-radius: 18px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; text-align: left; padding: 10px 14px; }
                .wcg-session button.is-active { border-color: #0f4c81; background: #eff6ff; box-shadow: 0 0 0 2px rgba(15,76,129,.12); }
                .wcg-session-meta { display: block; margin-top: 4px; color: #64748b; font-size: 12px; font-weight: 500; }
                .wcg-status { color: #64748b; font-size: 13px; margin-bottom: 12px; }
                .wcg-screen { min-height: 340px; max-height: 56vh; overflow: auto; border-radius: 18px; padding: 16px; background: #0b1220; color: #d7e3f4; font: 13px/1.55 Consolas, Monaco, monospace; white-space: pre-wrap; word-break: break-word; }
                .wcg-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
                .wcg-actions button { min-height: 38px; padding: 0 14px; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; }
                .wcg-quick { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
                .wcg-quick button { min-height: 38px; padding: 0 14px; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; }
                .wcg-form { display: grid; gap: 10px; margin-top: 14px; }
                .wcg-form textarea { width: 100%; min-height: 92px; border-radius: 18px; border: 1px solid #cbd5e1; background: #fff; padding: 12px 14px; font: 13px/1.55 Consolas, Monaco, monospace; }
                .wcg-form-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
                .wcg-form-row label { color: #475569; font-size: 13px; }
                .wcg-form-row button { min-height: 40px; padding: 0 16px; border-radius: 999px; border: 1px solid #0f4c81; background: #0f4c81; color: #fff; font-weight: 700; cursor: pointer; }
                .wcg-note { margin-top: 16px; padding: 14px 16px; border-radius: 16px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
                .wcg-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700; }
                .wcg-badge::before { content: ''; width: 8px; height: 8px; border-radius: 999px; background: #3b82f6; }
                @media (max-width: 860px) { .wcg-grid { grid-template-columns: 1fr; } }
            </style>
            <h2>Web CLI Guard</h2>
            <p>This interactive demo shows the operator flow for a tmux-backed web CLI console without executing a real shell. Use it to validate UI patterns before wiring a real bridge.</p>
            <div class="wcg-grid">
                <div class="wcg-card">
                    <h3>Sessions</h3>
                    <div class="wcg-session" data-wcg-session-list="1">
                        <?php foreach ($sessions as $index => $session): ?>
                            <button type="button" data-wcg-session="<?php echo esc_attr((string) $session['name']); ?>" class="<?php echo $index === 0 ? 'is-active' : ''; ?>">
                                <?php echo esc_html((string) $session['name']); ?>
                                <span class="wcg-session-meta"><?php echo esc_html((string) ($session['label'] ?? 'Demo session')); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="wcg-card">
                    <div class="wcg-status" data-wcg-status="1">Loading demo session...</div>
                    <div class="wcg-badge" data-wcg-badge="1"><?php echo $bridgeMode === 'bridge' ? 'Bridge Mode' : 'Demo Mode'; ?></div>
                    <div class="wcg-screen" data-wcg-screen="1">Loading...</div>
                    <div class="wcg-actions">
                        <button type="button" data-wcg-key="Enter">Enter</button>
                        <button type="button" data-wcg-key="C-c">Ctrl+C</button>
                        <button type="button" data-wcg-refresh="1">Refresh</button>
                    </div>
                    <div class="wcg-quick">
                        <button type="button" data-wcg-command="help">help</button>
                        <button type="button" data-wcg-command="pwd">pwd</button>
                        <button type="button" data-wcg-command="ls">ls</button>
                        <button type="button" data-wcg-command="whoami">whoami</button>
                        <button type="button" data-wcg-command="status">status</button>
                        <button type="button" data-wcg-command="clear">clear</button>
                    </div>
                    <form class="wcg-form" data-wcg-form="1">
                        <textarea name="wcg_input" placeholder="Type a command such as help, pwd, ls, or status"></textarea>
                        <div class="wcg-form-row">
                            <label><input type="checkbox" name="append_enter" value="1" checked> Send Enter after the command</label>
                            <button type="submit">Send</button>
                        </div>
                    </form>
                    <div class="wcg-note">
                        This UI stays in local demo mode by default. If a bridge URL, token, and allowed sessions are configured, it can switch to a real tmux-backed bridge while keeping the same operator flow.
                    </div>
                </div>
            </div>
            <script>
                (function () {
                    var root = document.querySelector('[data-wcg-console="1"]');
                    if (!root) {
                        return;
                    }

                    var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                    var nonce = <?php echo wp_json_encode(wp_create_nonce(self::NONCE_ACTION)); ?>;
                    var currentSession = root.getAttribute('data-wcg-default-session') || '';
                    var runtimeMode = root.getAttribute('data-wcg-runtime-mode') || 'demo';
                    var statusNode = root.querySelector('[data-wcg-status="1"]');
                    var badgeNode = root.querySelector('[data-wcg-badge="1"]');
                    var screenNode = root.querySelector('[data-wcg-screen="1"]');
                    var form = root.querySelector('[data-wcg-form="1"]');
                    var isBusy = false;

                    function setStatus(message) {
                        if (statusNode) {
                            statusNode.textContent = message;
                        }
                    }

                    function setActiveSession(sessionName) {
                        currentSession = String(sessionName || '');
                        root.querySelectorAll('[data-wcg-session]').forEach(function (button) {
                            button.classList.toggle('is-active', button.getAttribute('data-wcg-session') === currentSession);
                        });
                    }

                    function applyRuntimeMode(mode) {
                        runtimeMode = String(mode || 'demo');
                        if (!badgeNode) {
                            return;
                        }
                        badgeNode.textContent = runtimeMode === 'bridge' ? 'Bridge Mode' : 'Demo Mode';
                    }

                    function postAction(mode, extra) {
                        var body = new URLSearchParams(Object.assign({
                            action: 'wcg_demo_console',
                            _ajax_nonce: nonce,
                            mode: mode,
                            session: currentSession
                        }, extra || {}));
                        return fetch(ajaxUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                            body: body.toString()
                        }).then(function (response) {
                            return response.json();
                        });
                    }

                    function refreshOutput() {
                        if (!currentSession || isBusy) {
                            return;
                        }
                        setStatus('Refreshing ' + currentSession + '...');
                        postAction('capture').then(function (json) {
                            if (!(json && json.success && json.data)) {
                                throw new Error(json && json.data && json.data.message ? json.data.message : 'Capture failed');
                            }
                            applyRuntimeMode(json.data.runtime_mode || runtimeMode);
                            if (screenNode) {
                                screenNode.textContent = String(json.data.output || '');
                                screenNode.scrollTop = screenNode.scrollHeight;
                            }
                            setStatus('Ready: ' + currentSession);
                        }).catch(function (error) {
                            setStatus(error && error.message ? error.message : 'Capture failed');
                        });
                    }

                    function sendCommand(payload, pendingLabel, successLabel) {
                        if (!currentSession) {
                            setStatus('Select a session first.');
                            return;
                        }
                        isBusy = true;
                        setStatus(pendingLabel);
                        postAction('send', payload).then(function (json) {
                            if (!(json && json.success)) {
                                throw new Error(json && json.data && json.data.message ? json.data.message : 'Send failed');
                            }
                            applyRuntimeMode(json.data && json.data.runtime_mode ? json.data.runtime_mode : runtimeMode);
                            setStatus(successLabel + (runtimeMode === 'bridge' ? ' (bridge)' : ' (demo)'));
                            isBusy = false;
                            refreshOutput();
                        }).catch(function (error) {
                            setStatus(error && error.message ? error.message : 'Send failed');
                        }).finally(function () {
                            isBusy = false;
                        });
                    }

                    root.querySelectorAll('[data-wcg-session]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            setActiveSession(button.getAttribute('data-wcg-session') || '');
                            refreshOutput();
                        });
                    });

                    root.querySelectorAll('[data-wcg-key]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            var key = button.getAttribute('data-wcg-key') || '';
                            sendCommand({key: key}, 'Sending ' + key + '...', 'Sent ' + key + '.');
                        });
                    });

                    root.querySelectorAll('[data-wcg-command]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            var command = button.getAttribute('data-wcg-command') || '';
                            var label = (button.textContent || command || 'command').trim();
                            sendCommand({text: command, append_enter: '1'}, 'Sending ' + label + '...', 'Sent ' + label + '.');
                        });
                    });

                    var refreshButton = root.querySelector('[data-wcg-refresh="1"]');
                    if (refreshButton) {
                        refreshButton.addEventListener('click', refreshOutput);
                    }

                    if (form) {
                        form.addEventListener('submit', function (event) {
                            event.preventDefault();
                            var textarea = form.querySelector('textarea[name="wcg_input"]');
                            var appendEnter = form.querySelector('input[name="append_enter"]');
                            var text = textarea ? String(textarea.value || '') : '';
                            if (!text.trim()) {
                                setStatus('Type a command first.');
                                return;
                            }
                            sendCommand(
                                {text: text, append_enter: appendEnter && appendEnter.checked ? '1' : '0'},
                                'Sending command...',
                                'Command recorded in demo session.'
                            );
                            if (textarea) {
                                textarea.value = '';
                            }
                        });
                    }

                    refreshOutput();
                }());
            </script>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function handle_demo_console_ajax(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
        if (!check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        $mode = sanitize_key((string) ($_POST['mode'] ?? 'capture'));
        $session = self::sanitize_session((string) ($_POST['session'] ?? ''));
        if ($session === '') {
            wp_send_json_error(['message' => 'Invalid session.'], 400);
        }

        if ($mode === 'capture') {
            if (self::is_bridge_enabled()) {
                $capture = self::bridge_request('GET', '/capture?session=' . rawurlencode($session));
                wp_send_json_success([
                    'session' => $session,
                    'output' => (string) ($capture['output'] ?? ''),
                    'runtime_mode' => 'bridge',
                ]);
            }
            wp_send_json_success([
                'session' => $session,
                'output' => self::get_demo_output($session),
                'runtime_mode' => 'demo',
            ]);
        }

        if ($mode === 'send') {
            $text = (string) wp_unslash((string) ($_POST['text'] ?? ''));
            $key = sanitize_text_field((string) ($_POST['key'] ?? ''));
            $appendEnter = (string) ($_POST['append_enter'] ?? '') === '1';
            if ($text === '' && $key === '') {
                wp_send_json_error(['message' => 'Nothing to send.'], 400);
            }
            if (self::is_bridge_enabled()) {
                if ($text !== '') {
                    self::bridge_request('POST', '/send-text', [
                        'session' => $session,
                        'text' => $text,
                        'append_enter' => $appendEnter,
                    ]);
                } else {
                    self::bridge_request('POST', '/send-key', [
                        'session' => $session,
                        'key' => $key,
                    ]);
                }
                $capture = self::bridge_request('GET', '/capture?session=' . rawurlencode($session));
                wp_send_json_success([
                    'session' => $session,
                    'output' => (string) ($capture['output'] ?? ''),
                    'runtime_mode' => 'bridge',
                ]);
            }
            self::apply_demo_input($session, $text, $key, $appendEnter);
            wp_send_json_success([
                'session' => $session,
                'output' => self::get_demo_output($session),
                'runtime_mode' => 'demo',
            ]);
        }

        wp_send_json_error(['message' => 'Unsupported mode.'], 400);
    }

    public static function register_admin_page(): void
    {
        add_options_page(
            'Web CLI Guard',
            'Web CLI Guard',
            self::CAPABILITY,
            'web-cli-guard',
            [self::class, 'render_admin_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting('wcg_demo_settings', self::OPTION_BRIDGE_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);
        register_setting('wcg_demo_settings', self::OPTION_BRIDGE_TOKEN, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_token'],
            'default' => '',
        ]);
        register_setting('wcg_demo_settings', self::OPTION_RUNTIME_USER, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_runtime_user'],
            'default' => 'tmuxsvc',
        ]);
        register_setting('wcg_demo_settings', self::OPTION_ALLOWED_SESSIONS, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_sessions_csv'],
            'default' => 'agent-main,repo-main',
        ]);
    }

    public static function render_admin_page(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die('You do not have permission to access this page.');
        }

        $bridgeUrl = (string) get_option(self::OPTION_BRIDGE_URL, '');
        $bridgeToken = (string) get_option(self::OPTION_BRIDGE_TOKEN, '');
        $runtimeUser = (string) get_option(self::OPTION_RUNTIME_USER, 'tmuxsvc');
        $allowedSessions = (string) get_option(self::OPTION_ALLOWED_SESSIONS, 'agent-main,repo-main');
        ?>
        <div class="wrap">
            <h1>Web CLI Guard</h1>
            <p>This settings page is part of the public demo. It shows the configuration shape for a real bridge-backed deployment without shipping a production bridge in the plugin itself.</p>
            <form method="post" action="options.php">
                <?php settings_fields('wcg_demo_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wcg-demo-bridge-url">Bridge URL</label></th>
                        <td>
                            <input id="wcg-demo-bridge-url" name="<?php echo esc_attr(self::OPTION_BRIDGE_URL); ?>" type="url" class="regular-text code" value="<?php echo esc_attr($bridgeUrl); ?>">
                            <p class="description">Example: `http://127.0.0.1:8765`</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcg-demo-bridge-token">Bridge Token</label></th>
                        <td>
                            <input id="wcg-demo-bridge-token" name="<?php echo esc_attr(self::OPTION_BRIDGE_TOKEN); ?>" type="text" class="regular-text code" value="<?php echo esc_attr($bridgeToken); ?>">
                            <p class="description">Use an environment-specific secret outside version control in real deployments.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcg-demo-runtime-user">Runtime User</label></th>
                        <td>
                            <input id="wcg-demo-runtime-user" name="<?php echo esc_attr(self::OPTION_RUNTIME_USER); ?>" type="text" class="regular-text code" value="<?php echo esc_attr($runtimeUser); ?>">
                            <p class="description">Recommended: a dedicated low-privilege account such as `tmuxsvc`.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcg-demo-allowed-sessions">Allowed Sessions</label></th>
                        <td>
                            <input id="wcg-demo-allowed-sessions" name="<?php echo esc_attr(self::OPTION_ALLOWED_SESSIONS); ?>" type="text" class="regular-text code" value="<?php echo esc_attr($allowedSessions); ?>">
                            <p class="description">Comma-separated allowlist such as `agent-main,repo-main`.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Demo Settings'); ?>
            </form>
            <div style="margin-top:18px;padding:14px 16px;border:1px solid #fed7aa;border-radius:14px;background:#fff7ed;color:#9a3412;max-width:860px;">
                If bridge settings are valid, the public demo console can switch from local simulation to real bridge-backed session capture and send actions. OTP, approval, and command classification should still stay in the web layer.
            </div>
        </div>
        <?php
    }

    private static function get_demo_sessions(): array
    {
        return [
            ['name' => 'agent-main', 'label' => 'AI agent workflow demo'],
            ['name' => 'repo-main', 'label' => 'Repository operations demo'],
        ];
    }

    private static function get_available_sessions(): array
    {
        if (!self::is_bridge_enabled()) {
            return self::get_demo_sessions();
        }
        $json = self::bridge_try_request('GET', '/sessions');
        if (!is_array($json)) {
            return self::get_demo_sessions();
        }
        $items = [];
        foreach ((array) ($json['sessions'] ?? []) as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $items[] = [
                'name' => $name,
                'label' => 'Bridge-backed tmux session',
            ];
        }
        return $items !== [] ? $items : self::get_demo_sessions();
    }

    private static function sanitize_session(string $session): string
    {
        $session = trim($session);
        if ($session === '' || preg_match('/^[a-z0-9._:-]+$/i', $session) !== 1) {
            return '';
        }
        foreach (self::get_available_sessions() as $item) {
            if ((string) $item['name'] === $session) {
                return $session;
            }
        }
        return '';
    }

    private static function get_state_key(string $session): string
    {
        return self::STATE_PREFIX . get_current_user_id() . '_' . md5($session);
    }

    private static function get_demo_state(string $session): array
    {
        $state = get_transient(self::get_state_key($session));
        if (is_array($state) && isset($state['output'])) {
            return $state;
        }

        $prompt = self::get_prompt($session);
        $output = "Web CLI Guard interactive demo\n"
            . "Session: {$session}\n"
            . "Mode: safe demo, no real shell execution\n\n"
            . "Try: help, pwd, ls, whoami, status, clear\n\n"
            . $prompt;

        $state = [
            'output' => $output,
        ];
        set_transient(self::get_state_key($session), $state, self::STATE_TTL);
        return $state;
    }

    private static function set_demo_state(string $session, array $state): void
    {
        set_transient(self::get_state_key($session), $state, self::STATE_TTL);
    }

    private static function get_demo_output(string $session): string
    {
        $state = self::get_demo_state($session);
        return (string) ($state['output'] ?? '');
    }

    private static function apply_demo_input(string $session, string $text, string $key, bool $appendEnter): void
    {
        $state = self::get_demo_state($session);
        $output = rtrim((string) ($state['output'] ?? ''), "\n");
        $prompt = self::get_prompt($session);

        if ($key !== '') {
            if ($key === 'C-c') {
                $output .= "\n^C\n" . $prompt;
            } elseif ($key === 'Enter') {
                $output .= "\n" . $prompt;
            } else {
                $output .= "\n[key " . $key . " recorded in demo mode]\n" . $prompt;
            }
            $state['output'] = $output;
            self::set_demo_state($session, $state);
            return;
        }

        $command = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($command === '') {
            $state['output'] = $output . "\n" . $prompt;
            self::set_demo_state($session, $state);
            return;
        }

        $output .= "\n" . $prompt . $command;
        if ($appendEnter) {
            $output .= "\n" . self::run_demo_command($session, $command);
            $output = rtrim($output, "\n") . "\n" . $prompt;
        }

        $state['output'] = $output;
        self::set_demo_state($session, $state);
    }

    private static function run_demo_command(string $session, string $command): string
    {
        $normalized = strtolower(trim($command));
        if ($normalized === 'help') {
            return implode("\n", [
                'Available demo commands:',
                '- help',
                '- pwd',
                '- ls',
                '- whoami',
                '- status',
                '- clear',
            ]);
        }
        if ($normalized === 'pwd') {
            return $session === 'repo-main' ? '/srv/web-cli-guard/repo' : '/srv/web-cli-guard/agent';
        }
        if ($normalized === 'ls') {
            return $session === 'repo-main'
                ? "README.md\nsrc\ndocs\nscripts"
                : "sessions\nlogs\ncache\nprovider-adapter";
        }
        if ($normalized === 'whoami') {
            return 'tmuxsvc-demo';
        }
        if ($normalized === 'status') {
            return implode("\n", [
                'bridge_mode=demo',
                'runtime_user=tmuxsvc-demo',
                'audit=enabled (simulated)',
                'elevated_verification=not wired in demo mode',
                'current_session=' . $session,
            ]);
        }
        if ($normalized === 'clear') {
            return "Screen cleared by demo command.\n\nType help to list example commands.";
        }

        return 'Demo mode only: command recorded but not executed -> ' . $command;
    }

    private static function get_prompt(string $session): string
    {
        return $session === 'repo-main'
            ? 'tmuxsvc-demo@repo:/srv/web-cli-guard/repo$ '
            : 'tmuxsvc-demo@agent:/srv/web-cli-guard/agent$ ';
    }

    public static function sanitize_token(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9._:-]/', '', $value) ?? '');
    }

    public static function sanitize_runtime_user(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9._-]/', '', $value) ?? '');
    }

    public static function sanitize_sessions_csv(string $value): string
    {
        $parts = array_filter(array_map('trim', explode(',', $value)), static function (string $item): bool {
            return $item !== '';
        });
        $parts = array_map(static function (string $item): string {
            return preg_replace('/[^A-Za-z0-9._:-]/', '', $item) ?? '';
        }, $parts);
        $parts = array_values(array_filter($parts, static function (string $item): bool {
            return $item !== '';
        }));
        return implode(',', array_unique($parts));
    }

    private static function is_bridge_enabled(): bool
    {
        return self::get_bridge_url() !== '' && self::get_bridge_token() !== '';
    }

    private static function get_bridge_url(): string
    {
        return rtrim((string) get_option(self::OPTION_BRIDGE_URL, ''), '/');
    }

    private static function get_bridge_token(): string
    {
        return (string) get_option(self::OPTION_BRIDGE_TOKEN, '');
    }

    private static function bridge_request(string $method, string $path, array $payload = []): array
    {
        $json = self::bridge_try_request($method, $path, $payload);
        if (!is_array($json)) {
            wp_send_json_error(['message' => 'Bridge request failed.'], 502);
        }
        return $json;
    }

    private static function bridge_try_request(string $method, string $path, array $payload = []): ?array
    {
        $args = [
            'method' => $method,
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . self::get_bridge_token(),
                'Accept' => 'application/json',
            ],
        ];
        if ($method === 'POST') {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($payload);
        }

        $response = wp_remote_request(self::get_bridge_url() . $path, $args);
        if (is_wp_error($response)) {
            return null;
        }

        $json = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($json)) {
            return null;
        }
        if (!($json['ok'] ?? false)) {
            return null;
        }
        return $json;
    }
}

WebCliGuardPlugin::init();
