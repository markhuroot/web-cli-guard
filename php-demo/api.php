<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

const WCG_DEFAULT_BRIDGE_URL = '';
const WCG_DEFAULT_BRIDGE_TOKEN = '';
const WCG_DEMO_SESSIONS = [
    'agent-main' => 'AI agent workflow demo',
    'repo-main' => 'Repository operations demo',
];

function wcg_demo_respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function wcg_demo_sanitize_session(string $session): string
{
    $session = trim($session);
    if ($session === '' || preg_match('/^[A-Za-z0-9._:-]+$/', $session) !== 1) {
        return '';
    }
    return array_key_exists($session, WCG_DEMO_SESSIONS) ? $session : '';
}

function wcg_demo_prompt(string $session): string
{
    return $session === 'repo-main'
        ? 'tmuxsvc-demo@repo:/srv/web-cli-guard/repo$ '
        : 'tmuxsvc-demo@agent:/srv/web-cli-guard/agent$ ';
}

function wcg_bridge_url(): string
{
    $value = getenv('WCG_BRIDGE_URL');
    if (!is_string($value) || trim($value) === '') {
        return WCG_DEFAULT_BRIDGE_URL;
    }
    return rtrim(trim($value), '/');
}

function wcg_bridge_token(): string
{
    $value = getenv('WCG_BRIDGE_TOKEN');
    if (!is_string($value) || trim($value) === '') {
        return WCG_DEFAULT_BRIDGE_TOKEN;
    }
    return trim($value);
}

function wcg_bridge_enabled(): bool
{
    return wcg_bridge_url() !== '' && wcg_bridge_token() !== '';
}

function wcg_http_json(string $method, string $url, array $payload = null): array
{
    $headers = [
        'Authorization: Bearer ' . wcg_bridge_token(),
        'Accept: application/json',
    ];
    $context = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $context['http']['header'] = implode("\r\n", $headers);
        $context['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $result = @file_get_contents($url, false, stream_context_create($context));
    if ($result === false) {
        $error = error_get_last();
        wcg_demo_respond(502, [
            'ok' => false,
            'message' => 'Bridge request failed: ' . (($error['message'] ?? 'unknown error')),
        ]);
    }

    $json = json_decode($result, true);
    if (!is_array($json)) {
        wcg_demo_respond(502, ['ok' => false, 'message' => 'Bridge returned invalid JSON.']);
    }

    return $json;
}

function wcg_bridge_session_labels(array $names): array
{
    $labels = [];
    foreach ($names as $name) {
        $name = trim((string) $name);
        if ($name === '') {
            continue;
        }
        $labels[$name] = 'Bridge-backed tmux session';
    }
    return $labels;
}

function wcg_available_sessions(): array
{
    if (!wcg_bridge_enabled()) {
        return WCG_DEMO_SESSIONS;
    }

    $json = wcg_http_json('GET', wcg_bridge_url() . '/sessions');
    if (!($json['ok'] ?? false) || !isset($json['sessions']) || !is_array($json['sessions'])) {
        wcg_demo_respond(502, ['ok' => false, 'message' => 'Failed to load bridge sessions.']);
    }

    $items = [];
    foreach ($json['sessions'] as $item) {
        $name = trim((string) ($item['name'] ?? ''));
        if ($name !== '') {
            $items[$name] = 'Bridge-backed tmux session';
        }
    }
    if ($items === []) {
        wcg_demo_respond(502, ['ok' => false, 'message' => 'Bridge returned no allowed sessions.']);
    }
    return $items;
}

function wcg_sanitize_session(string $session): string
{
    $session = trim($session);
    if ($session === '' || preg_match('/^[A-Za-z0-9._:-]+$/', $session) !== 1) {
        return '';
    }
    $sessions = wcg_available_sessions();
    return array_key_exists($session, $sessions) ? $session : '';
}

function wcg_demo_state_key(string $session): string
{
    return 'wcg_demo_' . md5($session);
}

function wcg_demo_get_state(string $session): array
{
    $key = wcg_demo_state_key($session);
    if (isset($_SESSION[$key]) && is_array($_SESSION[$key]) && isset($_SESSION[$key]['output'])) {
        return $_SESSION[$key];
    }

    $prompt = wcg_demo_prompt($session);
    $state = [
        'output' => "Web CLI Guard plain PHP demo\n"
            . "Session: {$session}\n"
            . "Mode: safe demo, no real shell execution\n\n"
            . "Try: help, pwd, ls, whoami, status, clear\n\n"
            . $prompt,
    ];
    $_SESSION[$key] = $state;
    return $state;
}

function wcg_demo_set_state(string $session, array $state): void
{
    $_SESSION[wcg_demo_state_key($session)] = $state;
}

function wcg_demo_run_command(string $session, string $command): string
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
            'sandbox_boundary=os_user_permissions',
            'current_session=' . $session,
        ]);
    }
    if ($normalized === 'clear') {
        return "Screen cleared by demo command.\n\nType help to list example commands.";
    }
    return 'Demo mode only: command recorded but not executed -> ' . $command;
}

function wcg_demo_apply_input(string $session, string $text, string $key, bool $appendEnter): void
{
    $state = wcg_demo_get_state($session);
    $output = rtrim((string) ($state['output'] ?? ''), "\n");
    $prompt = wcg_demo_prompt($session);

    if ($key !== '') {
        if ($key === 'C-c') {
            $output .= "\n^C\n" . $prompt;
        } elseif ($key === 'Enter') {
            $output .= "\n" . $prompt;
        } else {
            $output .= "\n[key {$key} recorded in demo mode]\n" . $prompt;
        }
        $state['output'] = $output;
        wcg_demo_set_state($session, $state);
        return;
    }

    $command = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($command === '') {
        $state['output'] = $output . "\n" . $prompt;
        wcg_demo_set_state($session, $state);
        return;
    }

    $output .= "\n" . $prompt . $command;
    if ($appendEnter) {
        $output .= "\n" . wcg_demo_run_command($session, $command);
        $output = rtrim($output, "\n") . "\n" . $prompt;
    }

    $state['output'] = $output;
    wcg_demo_set_state($session, $state);
}

$mode = strtolower(trim((string) ($_POST['mode'] ?? $_GET['mode'] ?? 'capture')));
$defaultSession = array_key_first(wcg_available_sessions()) ?: 'agent-main';
$session = wcg_sanitize_session((string) ($_POST['session'] ?? $_GET['session'] ?? $defaultSession));
if ($session === '') {
    wcg_demo_respond(400, ['ok' => false, 'message' => 'Invalid session.']);
}

if ($mode === 'sessions') {
    $items = [];
    foreach (wcg_available_sessions() as $name => $label) {
        $items[] = ['name' => $name, 'label' => $label];
    }
    wcg_demo_respond(200, ['ok' => true, 'items' => $items, 'runtime_mode' => wcg_bridge_enabled() ? 'bridge' : 'demo']);
}

if ($mode === 'capture') {
    if (wcg_bridge_enabled()) {
        $json = wcg_http_json('GET', wcg_bridge_url() . '/capture?session=' . rawurlencode($session));
        if (!($json['ok'] ?? false)) {
            wcg_demo_respond(502, ['ok' => false, 'message' => (string) ($json['message'] ?? 'Bridge capture failed.')]);
        }
        wcg_demo_respond(200, [
            'ok' => true,
            'session' => $session,
            'output' => (string) ($json['output'] ?? ''),
            'runtime_mode' => 'bridge',
        ]);
    }
    $state = wcg_demo_get_state($session);
    wcg_demo_respond(200, ['ok' => true, 'session' => $session, 'output' => (string) $state['output'], 'runtime_mode' => 'demo']);
}

if ($mode === 'send') {
    $text = (string) ($_POST['text'] ?? '');
    $key = trim((string) ($_POST['key'] ?? ''));
    $appendEnter = (string) ($_POST['append_enter'] ?? '') === '1';
    if ($text === '' && $key === '') {
        wcg_demo_respond(400, ['ok' => false, 'message' => 'Nothing to send.']);
    }

    if (wcg_bridge_enabled()) {
        if ($text !== '') {
            $json = wcg_http_json('POST', wcg_bridge_url() . '/send-text', [
                'session' => $session,
                'text' => $text,
                'append_enter' => $appendEnter,
            ]);
        } else {
            $json = wcg_http_json('POST', wcg_bridge_url() . '/send-key', [
                'session' => $session,
                'key' => $key,
            ]);
        }

        if (!($json['ok'] ?? false)) {
            wcg_demo_respond(502, ['ok' => false, 'message' => (string) ($json['message'] ?? 'Bridge send failed.')]);
        }

        $capture = wcg_http_json('GET', wcg_bridge_url() . '/capture?session=' . rawurlencode($session));
        wcg_demo_respond(200, [
            'ok' => true,
            'session' => $session,
            'output' => (string) ($capture['output'] ?? ''),
            'runtime_mode' => 'bridge',
        ]);
    }

    wcg_demo_apply_input($session, $text, $key, $appendEnter);
    $state = wcg_demo_get_state($session);
    wcg_demo_respond(200, ['ok' => true, 'session' => $session, 'output' => (string) $state['output'], 'runtime_mode' => 'demo']);
}

wcg_demo_respond(400, ['ok' => false, 'message' => 'Unsupported mode.']);
