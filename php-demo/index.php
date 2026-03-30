<?php
declare(strict_types=1);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web CLI Guard Plain PHP Demo</title>
    <style>
        :root {
            --bg: #f5f8fc;
            --card: #ffffff;
            --line: #d7dee8;
            --text: #17212f;
            --muted: #516072;
            --console-bg: #0b1220;
            --console-fg: #d7e3f4;
            --accent: #0f4c81;
            --warn-bg: #fff7ed;
            --warn-line: #fed7aa;
            --warn-text: #9a3412;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(14,165,233,.10), transparent 28%),
                radial-gradient(circle at right 20%, rgba(15,76,129,.10), transparent 22%),
                var(--bg);
            color: var(--text);
        }
        .shell {
            width: min(1180px, calc(100vw - 24px));
            margin: 24px auto;
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: var(--card);
            box-shadow: 0 16px 36px rgba(15,23,42,.06);
        }
        h1 { margin: 0 0 8px; font-size: 30px; }
        p { color: var(--muted); line-height: 1.7; }
        .grid {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 16px;
            margin-top: 18px;
        }
        .card {
            border: 1px solid var(--line);
            border-radius: 20px;
            background: #fbfdff;
            padding: 16px;
        }
        .card h2 {
            margin: 0 0 12px;
            font-size: 16px;
        }
        .sessions {
            display: grid;
            gap: 10px;
        }
        .sessions button {
            min-height: 46px;
            border-radius: 18px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-weight: 700;
            cursor: pointer;
            text-align: left;
            padding: 10px 14px;
        }
        .sessions button.active {
            border-color: var(--accent);
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(15,76,129,.12);
        }
        .session-meta {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
        }
        .status {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }
        .badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #3b82f6;
        }
        .screen {
            min-height: 340px;
            max-height: 56vh;
            overflow: auto;
            border-radius: 18px;
            padding: 16px;
            margin-top: 12px;
            background: var(--console-bg);
            color: var(--console-fg);
            font: 13px/1.55 Consolas, Monaco, monospace;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .row button {
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        form {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }
        textarea {
            width: 100%;
            min-height: 92px;
            border-radius: 18px;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 12px 14px;
            font: 13px/1.55 Consolas, Monaco, monospace;
        }
        .form-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .form-row label {
            color: #475569;
            font-size: 13px;
        }
        .form-row button {
            min-height: 40px;
            padding: 0 16px;
            border-radius: 999px;
            border: 1px solid var(--accent);
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .note {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--warn-bg);
            border: 1px solid var(--warn-line);
            color: var(--warn-text);
        }
        @media (max-width: 860px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell" data-demo-root="1">
        <h1>Web CLI Guard</h1>
        <p>This plain PHP demo shows how a web UI can inspect ongoing AI CLI work, simulate remote operations, and preserve a safer operational model by keeping real execution behind an OS-level sandbox boundary.</p>
        <div class="grid">
            <div class="card">
                <h2>Sessions</h2>
                <div class="sessions" id="demo-sessions"></div>
            </div>
            <div class="card">
                <div class="status" id="demo-status">Loading demo session...</div>
                <div class="badge">Plain PHP Demo</div>
                <div class="screen" id="demo-screen">Loading...</div>
                <div class="row">
                    <button type="button" data-key="Enter">Enter</button>
                    <button type="button" data-key="C-c">Ctrl+C</button>
                    <button type="button" id="demo-refresh">Refresh</button>
                </div>
                <div class="row">
                    <button type="button" data-command="help">help</button>
                    <button type="button" data-command="pwd">pwd</button>
                    <button type="button" data-command="ls">ls</button>
                    <button type="button" data-command="whoami">whoami</button>
                    <button type="button" data-command="status">status</button>
                    <button type="button" data-command="clear">clear</button>
                </div>
                <form id="demo-form">
                    <textarea name="command" placeholder="Type a command such as help, pwd, ls, or status"></textarea>
                    <div class="form-row">
                        <label><input type="checkbox" name="append_enter" value="1" checked> Send Enter after the command</label>
                        <button type="submit">Send</button>
                    </div>
                </form>
                <div class="note">
                    This demo does not execute shell commands. It simulates session output so you can evaluate the web interaction model before wiring a real bridge.
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var sessionContainer = document.getElementById('demo-sessions');
            var statusNode = document.getElementById('demo-status');
            var screenNode = document.getElementById('demo-screen');
            var form = document.getElementById('demo-form');
            var refreshButton = document.getElementById('demo-refresh');
            var currentSession = 'agent-main';
            var isBusy = false;

            function setStatus(message) {
                statusNode.textContent = message;
            }

            function postForm(body) {
                return fetch('api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: new URLSearchParams(body).toString()
                }).then(function (response) {
                    return response.json();
                });
            }

            function renderSessions(items) {
                sessionContainer.innerHTML = '';
                items.forEach(function (item) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.session = item.name;
                    button.className = item.name === currentSession ? 'active' : '';
                    button.innerHTML = item.name + '<span class="session-meta">' + item.label + '</span>';
                    button.addEventListener('click', function () {
                        currentSession = item.name;
                        renderSessions(items);
                        refreshOutput();
                    });
                    sessionContainer.appendChild(button);
                });
            }

            function loadSessions() {
                return fetch('api.php?mode=sessions&session=' + encodeURIComponent(currentSession))
                    .then(function (response) { return response.json(); })
                    .then(function (json) {
                        if (!(json && json.ok && Array.isArray(json.items))) {
                            throw new Error('Failed to load sessions');
                        }
                        renderSessions(json.items);
                    });
            }

            function refreshOutput() {
                if (isBusy) {
                    return;
                }
                setStatus('Refreshing ' + currentSession + '...');
                postForm({mode: 'capture', session: currentSession}).then(function (json) {
                    if (!(json && json.ok)) {
                        throw new Error(json && json.message ? json.message : 'Capture failed');
                    }
                    screenNode.textContent = String(json.output || '');
                    screenNode.scrollTop = screenNode.scrollHeight;
                    setStatus('Ready: ' + currentSession);
                }).catch(function (error) {
                    setStatus(error && error.message ? error.message : 'Capture failed');
                });
            }

            function sendPayload(payload, pendingLabel, successLabel) {
                isBusy = true;
                setStatus(pendingLabel);
                payload.mode = 'send';
                payload.session = currentSession;
                postForm(payload).then(function (json) {
                    if (!(json && json.ok)) {
                        throw new Error(json && json.message ? json.message : 'Send failed');
                    }
                    screenNode.textContent = String(json.output || '');
                    screenNode.scrollTop = screenNode.scrollHeight;
                    setStatus(successLabel);
                }).catch(function (error) {
                    setStatus(error && error.message ? error.message : 'Send failed');
                }).finally(function () {
                    isBusy = false;
                });
            }

            document.querySelectorAll('[data-key]').forEach(function (button) {
                button.addEventListener('click', function () {
                    sendPayload({key: button.getAttribute('data-key') || ''}, 'Sending key...', 'Key recorded.');
                });
            });

            document.querySelectorAll('[data-command]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var command = button.getAttribute('data-command') || '';
                    sendPayload({text: command, append_enter: '1'}, 'Sending command...', 'Command recorded.');
                });
            });

            refreshButton.addEventListener('click', refreshOutput);

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var textarea = form.querySelector('textarea[name="command"]');
                var appendEnter = form.querySelector('input[name="append_enter"]');
                var text = textarea ? String(textarea.value || '') : '';
                if (!text.trim()) {
                    setStatus('Type a command first.');
                    return;
                }
                sendPayload({text: text, append_enter: appendEnter && appendEnter.checked ? '1' : '0'}, 'Sending command...', 'Command recorded.');
                textarea.value = '';
            });

            loadSessions().then(refreshOutput).catch(function (error) {
                setStatus(error && error.message ? error.message : 'Failed to load demo');
            });
        }());
    </script>
</body>
</html>
