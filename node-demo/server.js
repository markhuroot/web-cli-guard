#!/usr/bin/env node
'use strict';

const http = require('http');
const { URL } = require('url');

const HOST = process.env.WCG_NODE_HOST || '127.0.0.1';
const PORT = Number(process.env.WCG_NODE_PORT || 8090);
const BRIDGE_URL = (process.env.WCG_BRIDGE_URL || '').replace(/\/$/, '');
const BRIDGE_TOKEN = (process.env.WCG_BRIDGE_TOKEN || '').trim();
const BRIDGE_MODE = BRIDGE_URL !== '' && BRIDGE_TOKEN !== '';

const DEMO_SESSIONS = {
  'agent-main': 'AI agent workflow demo',
  'repo-main': 'Repository operations demo',
};

const demoState = new Map();

function json(res, statusCode, payload) {
  const body = Buffer.from(JSON.stringify(payload));
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': String(body.length),
  });
  res.end(body);
}

function text(res, statusCode, payload) {
  const body = Buffer.from(payload);
  res.writeHead(statusCode, {
    'Content-Type': 'text/html; charset=utf-8',
    'Content-Length': String(body.length),
  });
  res.end(body);
}

function promptFor(session) {
  return session === 'repo-main'
    ? 'tmuxsvc-demo@repo:/srv/web-cli-guard/repo$ '
    : 'tmuxsvc-demo@agent:/srv/web-cli-guard/agent$ ';
}

function getDemoState(session) {
  if (demoState.has(session)) {
    return demoState.get(session);
  }
  const output = [
    'Web CLI Guard Node.js demo',
    `Session: ${session}`,
    'Mode: safe demo, no real shell execution',
    '',
    'Try: help, pwd, ls, whoami, status, clear',
    '',
    promptFor(session),
  ].join('\n');
  const state = { output };
  demoState.set(session, state);
  return state;
}

function runDemoCommand(session, command) {
  const normalized = String(command || '').trim().toLowerCase();
  if (normalized === 'help') {
    return ['Available demo commands:', '- help', '- pwd', '- ls', '- whoami', '- status', '- clear'].join('\n');
  }
  if (normalized === 'pwd') {
    return session === 'repo-main' ? '/srv/web-cli-guard/repo' : '/srv/web-cli-guard/agent';
  }
  if (normalized === 'ls') {
    return session === 'repo-main' ? 'README.md\nsrc\ndocs\nscripts' : 'sessions\nlogs\ncache\nprovider-adapter';
  }
  if (normalized === 'whoami') {
    return 'tmuxsvc-demo';
  }
  if (normalized === 'status') {
    return [
      `bridge_mode=${BRIDGE_MODE ? 'bridge' : 'demo'}`,
      'runtime_user=tmuxsvc-demo',
      'audit=enabled (simulated)',
      'sandbox_boundary=os_user_permissions',
      `current_session=${session}`,
    ].join('\n');
  }
  if (normalized === 'clear') {
    return 'Screen cleared by demo command.\n\nType help to list example commands.';
  }
  return `Demo mode only: command recorded but not executed -> ${command}`;
}

function applyDemoInput(session, textInput, keyInput, appendEnter) {
  const state = getDemoState(session);
  const prompt = promptFor(session);
  let output = String(state.output || '').replace(/\n+$/, '');

  if (keyInput) {
    if (keyInput === 'C-c') {
      output += '\n^C\n' + prompt;
    } else if (keyInput === 'Enter') {
      output += '\n' + prompt;
    } else {
      output += `\n[key ${keyInput} recorded in demo mode]\n${prompt}`;
    }
    state.output = output;
    demoState.set(session, state);
    return state.output;
  }

  const command = String(textInput || '').trim();
  if (!command) {
    state.output = output + '\n' + prompt;
    demoState.set(session, state);
    return state.output;
  }

  output += '\n' + prompt + command;
  if (appendEnter) {
    output += '\n' + runDemoCommand(session, command);
    output = output.replace(/\n+$/, '') + '\n' + prompt;
  }

  state.output = output;
  demoState.set(session, state);
  return state.output;
}

async function bridgeRequest(method, path, payload) {
  const headers = {
    'Authorization': `Bearer ${BRIDGE_TOKEN}`,
    'Accept': 'application/json',
  };
  const init = { method, headers };
  if (payload) {
    headers['Content-Type'] = 'application/json';
    init.body = JSON.stringify(payload);
  }
  const response = await fetch(BRIDGE_URL + path, init);
  const data = await response.json();
  if (!response.ok || !data || data.ok === false) {
    throw new Error((data && data.message) || `Bridge request failed (${response.status})`);
  }
  return data;
}

async function collectSessions() {
  if (!BRIDGE_MODE) {
    return Object.entries(DEMO_SESSIONS).map(([name, label]) => ({ name, label }));
  }
  const data = await bridgeRequest('GET', '/sessions');
  return (data.sessions || []).map((item) => ({
    name: item.name,
    label: 'Bridge-backed tmux session',
  }));
}

function sanitizeSession(session, allowedNames) {
  const value = String(session || '').trim();
  if (!/^[A-Za-z0-9._:-]+$/.test(value)) {
    return '';
  }
  return allowedNames.includes(value) ? value : '';
}

function readBody(req) {
  return new Promise((resolve, reject) => {
    let raw = '';
    req.on('data', (chunk) => {
      raw += chunk;
      if (raw.length > 1024 * 1024) {
        reject(new Error('Body too large'));
      }
    });
    req.on('end', () => {
      try {
        resolve(raw ? JSON.parse(raw) : {});
      } catch (error) {
        reject(error);
      }
    });
    req.on('error', reject);
  });
}

function renderHtml() {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Web CLI Guard Node Demo</title>
  <style>
    :root { --bg:#f5f8fc; --card:#ffffff; --line:#d7dee8; --text:#17212f; --muted:#516072; --console-bg:#0b1220; --console-fg:#d7e3f4; --accent:#0f4c81; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; background:radial-gradient(circle at top left, rgba(14,165,233,.10), transparent 28%), radial-gradient(circle at right 20%, rgba(15,76,129,.10), transparent 22%), var(--bg); color:var(--text); }
    .shell { width:min(1180px, calc(100vw - 24px)); margin:24px auto; padding:24px; border:1px solid var(--line); border-radius:24px; background:var(--card); box-shadow:0 16px 36px rgba(15,23,42,.06); }
    .grid { display:grid; grid-template-columns:280px minmax(0,1fr); gap:16px; margin-top:18px; }
    .card { border:1px solid var(--line); border-radius:20px; background:#fbfdff; padding:16px; }
    .sessions { display:grid; gap:10px; }
    .sessions button, .row button, .send-row button { min-height:40px; border-radius:16px; border:1px solid #cbd5e1; background:#fff; cursor:pointer; font-weight:700; padding:10px 14px; text-align:left; }
    .sessions button.active { border-color:var(--accent); background:#eff6ff; box-shadow:0 0 0 2px rgba(15,76,129,.12); }
    .screen { min-height:340px; max-height:56vh; overflow:auto; border-radius:18px; padding:16px; margin-top:12px; background:var(--console-bg); color:var(--console-fg); font:13px/1.55 Consolas,Monaco,monospace; white-space:pre-wrap; word-break:break-word; }
    .row, .send-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
    textarea { width:100%; min-height:92px; border-radius:18px; border:1px solid #cbd5e1; background:#fff; padding:12px 14px; font:13px/1.55 Consolas,Monaco,monospace; }
    .badge { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:700; }
    .badge.bridge { background:#ecfdf5; color:#047857; }
    .muted { color:var(--muted); line-height:1.7; }
    .status { color:#64748b; font-size:13px; margin-bottom:12px; }
    @media (max-width: 860px) { .grid { grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="shell">
    <h1>Web CLI Guard</h1>
    <p class="muted">This zero-dependency Node.js interface can run as a safe local demo or switch to a real tmux bridge. It is useful when you want a small web UI for reaching home or office CLI workflows without exposing a raw shell.</p>
    <div class="grid">
      <div class="card">
        <h2>Sessions</h2>
        <div class="sessions" id="sessions"></div>
      </div>
      <div class="card">
        <div class="status" id="status">Loading sessions...</div>
        <div class="badge" id="badge">Node Demo</div>
        <div class="screen" id="screen">Loading...</div>
        <div class="row">
          <button data-key="Enter">Enter</button>
          <button data-key="C-c">Ctrl+C</button>
          <button id="refresh">Refresh</button>
        </div>
        <div class="row">
          <button data-command="help">help</button>
          <button data-command="pwd">pwd</button>
          <button data-command="ls">ls</button>
          <button data-command="whoami">whoami</button>
          <button data-command="status">status</button>
          <button data-command="clear">clear</button>
        </div>
        <div class="send-row">
          <textarea id="command" placeholder="Type a command such as help, pwd, ls, or status"></textarea>
        </div>
        <div class="send-row">
          <label><input type="checkbox" id="append-enter" checked> Send Enter after the command</label>
          <button id="send">Send</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    const state = { session: '', mode: 'demo', busy: false, sessions: [] };
    const sessionsNode = document.getElementById('sessions');
    const statusNode = document.getElementById('status');
    const badgeNode = document.getElementById('badge');
    const screenNode = document.getElementById('screen');
    const commandNode = document.getElementById('command');
    const appendEnterNode = document.getElementById('append-enter');

    function setStatus(message) { statusNode.textContent = message; }
    function setMode(mode) {
      state.mode = mode || 'demo';
      badgeNode.textContent = state.mode === 'bridge' ? 'Bridge Mode' : 'Node Demo';
      badgeNode.classList.toggle('bridge', state.mode === 'bridge');
    }
    async function api(path, options) {
      const response = await fetch(path, Object.assign({ headers: { 'Content-Type': 'application/json' } }, options || {}));
      const json = await response.json();
      if (!response.ok || !json.ok) throw new Error(json.message || 'Request failed');
      return json;
    }
    function renderSessions() {
      sessionsNode.innerHTML = '';
      state.sessions.forEach((item) => {
        const button = document.createElement('button');
        button.textContent = item.name;
        button.className = item.name === state.session ? 'active' : '';
        const meta = document.createElement('span');
        meta.style.display = 'block';
        meta.style.marginTop = '4px';
        meta.style.fontSize = '12px';
        meta.style.color = '#64748b';
        meta.textContent = item.label;
        button.appendChild(meta);
        button.addEventListener('click', async () => {
          state.session = item.name;
          renderSessions();
          await refresh();
        });
        sessionsNode.appendChild(button);
      });
    }
    async function loadSessions() {
      const json = await api('/api/sessions');
      state.sessions = json.items || [];
      if (!state.session && state.sessions[0]) state.session = state.sessions[0].name;
      setMode(json.runtime_mode || 'demo');
      renderSessions();
    }
    async function refresh() {
      if (!state.session || state.busy) return;
      setStatus('Refreshing ' + state.session + '...');
      const json = await api('/api/capture?session=' + encodeURIComponent(state.session));
      setMode(json.runtime_mode || state.mode);
      screenNode.textContent = json.output || '';
      screenNode.scrollTop = screenNode.scrollHeight;
      setStatus('Ready: ' + state.session);
    }
    async function send(payload, label) {
      if (!state.session) return;
      state.busy = true;
      setStatus(label);
      try {
        const json = await api('/api/send', { method: 'POST', body: JSON.stringify(Object.assign({ session: state.session }, payload)) });
        setMode(json.runtime_mode || state.mode);
        screenNode.textContent = json.output || '';
        screenNode.scrollTop = screenNode.scrollHeight;
        setStatus((payload.key ? 'Key sent' : 'Command sent') + (state.mode === 'bridge' ? ' (bridge)' : ' (demo)'));
      } finally {
        state.busy = false;
      }
    }
    document.querySelectorAll('[data-key]').forEach((button) => button.addEventListener('click', () => send({ key: button.dataset.key }, 'Sending key...')));
    document.querySelectorAll('[data-command]').forEach((button) => button.addEventListener('click', () => send({ text: button.dataset.command, append_enter: true }, 'Sending command...')));
    document.getElementById('refresh').addEventListener('click', refresh);
    document.getElementById('send').addEventListener('click', () => {
      const text = String(commandNode.value || '').trim();
      if (!text) return setStatus('Type a command first.');
      send({ text, append_enter: !!appendEnterNode.checked }, 'Sending command...');
      commandNode.value = '';
    });
    loadSessions().then(refresh).catch((error) => setStatus(error.message || 'Failed to load'));
  </script>
</body>
</html>`;
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);

  if (req.method === 'GET' && url.pathname === '/') {
    return text(res, 200, renderHtml());
  }

  if (url.pathname === '/api/sessions') {
    try {
      const items = await collectSessions();
      return json(res, 200, { ok: true, items, runtime_mode: BRIDGE_MODE ? 'bridge' : 'demo' });
    } catch (error) {
      return json(res, 502, { ok: false, message: error.message });
    }
  }

  if (url.pathname === '/api/capture') {
    try {
      const sessions = await collectSessions();
      const allowedNames = sessions.map((item) => item.name);
      const session = sanitizeSession(url.searchParams.get('session'), allowedNames);
      if (!session) {
        return json(res, 400, { ok: false, message: 'Invalid session' });
      }
      if (BRIDGE_MODE) {
        const data = await bridgeRequest('GET', `/capture?session=${encodeURIComponent(session)}`);
        return json(res, 200, { ok: true, session, output: data.output || '', runtime_mode: 'bridge' });
      }
      return json(res, 200, { ok: true, session, output: getDemoState(session).output, runtime_mode: 'demo' });
    } catch (error) {
      return json(res, 502, { ok: false, message: error.message });
    }
  }

  if (url.pathname === '/api/send' && req.method === 'POST') {
    try {
      const payload = await readBody(req);
      const sessions = await collectSessions();
      const allowedNames = sessions.map((item) => item.name);
      const session = sanitizeSession(payload.session, allowedNames);
      if (!session) {
        return json(res, 400, { ok: false, message: 'Invalid session' });
      }
      if (!payload.text && !payload.key) {
        return json(res, 400, { ok: false, message: 'Nothing to send' });
      }
      if (BRIDGE_MODE) {
        if (payload.text) {
          await bridgeRequest('POST', '/send-text', { session, text: String(payload.text || ''), append_enter: !!payload.append_enter });
        } else {
          await bridgeRequest('POST', '/send-key', { session, key: String(payload.key || '') });
        }
        const data = await bridgeRequest('GET', `/capture?session=${encodeURIComponent(session)}`);
        return json(res, 200, { ok: true, session, output: data.output || '', runtime_mode: 'bridge' });
      }
      const output = applyDemoInput(session, payload.text, payload.key, !!payload.append_enter);
      return json(res, 200, { ok: true, session, output, runtime_mode: 'demo' });
    } catch (error) {
      return json(res, 502, { ok: false, message: error.message });
    }
  }

  json(res, 404, { ok: false, message: 'Not found' });
});

server.listen(PORT, HOST, () => {
  console.log(`Web CLI Guard node demo listening on http://${HOST}:${PORT}`);
  console.log(`Mode: ${BRIDGE_MODE ? 'bridge' : 'demo'}`);
});
