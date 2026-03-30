<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

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
$session = wcg_demo_sanitize_session((string) ($_POST['session'] ?? $_GET['session'] ?? 'agent-main'));
if ($session === '') {
    wcg_demo_respond(400, ['ok' => false, 'message' => 'Invalid session.']);
}

if ($mode === 'sessions') {
    $items = [];
    foreach (WCG_DEMO_SESSIONS as $name => $label) {
        $items[] = ['name' => $name, 'label' => $label];
    }
    wcg_demo_respond(200, ['ok' => true, 'items' => $items]);
}

if ($mode === 'capture') {
    $state = wcg_demo_get_state($session);
    wcg_demo_respond(200, ['ok' => true, 'session' => $session, 'output' => (string) $state['output']]);
}

if ($mode === 'send') {
    $text = (string) ($_POST['text'] ?? '');
    $key = trim((string) ($_POST['key'] ?? ''));
    $appendEnter = (string) ($_POST['append_enter'] ?? '') === '1';
    if ($text === '' && $key === '') {
        wcg_demo_respond(400, ['ok' => false, 'message' => 'Nothing to send.']);
    }
    wcg_demo_apply_input($session, $text, $key, $appendEnter);
    $state = wcg_demo_get_state($session);
    wcg_demo_respond(200, ['ok' => true, 'session' => $session, 'output' => (string) $state['output']]);
}

wcg_demo_respond(400, ['ok' => false, 'message' => 'Unsupported mode.']);
