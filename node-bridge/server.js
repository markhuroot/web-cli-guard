#!/usr/bin/env node
'use strict';

const http = require('http');
const { spawnSync } = require('child_process');
const { URL } = require('url');

const ALLOWED_KEYS = new Map([
  ['Enter', 'Enter'],
  ['C-c', 'C-c'],
  ['Escape', 'Escape'],
  ['Tab', 'Tab'],
  ['Up', 'Up'],
  ['Down', 'Down'],
  ['Left', 'Left'],
  ['Right', 'Right'],
  ['PageUp', 'PageUp'],
  ['PageDown', 'PageDown'],
]);

function parseArgs(argv) {
  const options = {
    host: '127.0.0.1',
    port: 8766,
    token: '',
    socket: '',
    allowedSessions: [],
  };

  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    const next = argv[index + 1];
    if (arg === '--host' && next) {
      options.host = next;
      index += 1;
      continue;
    }
    if (arg === '--port' && next) {
      options.port = Number(next);
      index += 1;
      continue;
    }
    if (arg === '--token' && next) {
      options.token = next;
      index += 1;
      continue;
    }
    if (arg === '--socket' && next) {
      options.socket = next;
      index += 1;
      continue;
    }
    if (arg === '--allow-session' && next) {
      options.allowedSessions.push(next);
      index += 1;
      continue;
    }
    if (arg === '--help' || arg === '-h') {
      printHelp();
      process.exit(0);
    }
    throw new Error(`Unknown or incomplete argument: ${arg}`);
  }

  if (!options.token) {
    throw new Error('Missing required --token.');
  }
  if (!options.socket) {
    throw new Error('Missing required --socket.');
  }
  if (!Number.isInteger(options.port) || options.port <= 0) {
    throw new Error('Invalid --port.');
  }
  if (!options.allowedSessions.length) {
    throw new Error('At least one --allow-session is required.');
  }
  return options;
}

function printHelp() {
  const lines = [
    'Minimal tmux bridge for Web CLI Guard',
    '',
    'Usage:',
    '  node server.js --token TOKEN --socket /path/to.sock --allow-session NAME [options]',
    '',
    'Options:',
    '  --host 127.0.0.1',
    '  --port 8766',
    '  --token <shared bearer token>',
    '  --socket <tmux socket path>',
    '  --allow-session <session name>   Repeat for multiple sessions',
  ];
  process.stdout.write(lines.join('\n') + '\n');
}

function json(response, statusCode, payload) {
  const body = Buffer.from(JSON.stringify(payload));
  response.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': String(body.length),
  });
  response.end(body);
}

function readBody(request) {
  return new Promise((resolve, reject) => {
    let raw = '';
    request.on('data', (chunk) => {
      raw += chunk;
      if (raw.length > 1024 * 1024) {
        reject(new Error('Body too large'));
      }
    });
    request.on('end', () => {
      try {
        const parsed = raw ? JSON.parse(raw) : {};
        if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') {
          return reject(new Error('JSON body must be an object.'));
        }
        resolve(parsed);
      } catch (_error) {
        reject(new Error('Invalid JSON body.'));
      }
    });
    request.on('error', reject);
  });
}

class TmuxBridge {
  constructor(socketPath, allowedSessions) {
    this.socketPath = socketPath;
    this.allowedSessions = new Set(allowedSessions);
  }

  tmux(args) {
    const result = spawnSync('tmux', ['-S', this.socketPath, ...args], {
      encoding: 'utf8',
    });
    if (result.error) {
      throw new Error(result.error.message || 'tmux invocation failed');
    }
    return result;
  }

  requireAllowedSession(session) {
    if (!session || !this.allowedSessions.has(session)) {
      throw new Error('Unknown or disallowed session.');
    }
  }

  listSessions() {
    const result = this.tmux(['list-sessions', '-F', '#{session_name}']);
    if (result.status !== 0) {
      throw new Error((result.stderr || '').trim() || 'tmux list-sessions failed');
    }
    return String(result.stdout || '')
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter((line) => line && this.allowedSessions.has(line))
      .map((name) => ({ name }));
  }

  capture(session) {
    this.requireAllowedSession(session);
    const result = this.tmux(['capture-pane', '-p', '-t', session]);
    if (result.status !== 0) {
      throw new Error((result.stderr || '').trim() || 'tmux capture-pane failed');
    }
    return String(result.stdout || '');
  }

  sendText(session, text, appendEnter) {
    this.requireAllowedSession(session);
    const send = this.tmux(['send-keys', '-t', session, String(text || '')]);
    if (send.status !== 0) {
      throw new Error((send.stderr || '').trim() || 'tmux send-keys failed');
    }
    if (appendEnter) {
      const enter = this.tmux(['send-keys', '-t', session, 'Enter']);
      if (enter.status !== 0) {
        throw new Error((enter.stderr || '').trim() || 'tmux send Enter failed');
      }
    }
  }

  sendKey(session, key) {
    this.requireAllowedSession(session);
    if (!ALLOWED_KEYS.has(key)) {
      throw new Error('Unknown or disallowed key.');
    }
    const result = this.tmux(['send-keys', '-t', session, ALLOWED_KEYS.get(key)]);
    if (result.status !== 0) {
      throw new Error((result.stderr || '').trim() || 'tmux send-keys failed');
    }
  }
}

function isAuthorized(request, token) {
  return request.headers.authorization === `Bearer ${token}`;
}

function handleError(response, statusCode, errorCode, message) {
  json(response, statusCode, { ok: false, error: errorCode, message });
}

function startServer(options) {
  const bridge = new TmuxBridge(options.socket, options.allowedSessions);

  const server = http.createServer(async (request, response) => {
    if (!isAuthorized(request, options.token)) {
      return handleError(response, 401, 'unauthorized', 'Missing or invalid token.');
    }

    const url = new URL(request.url, `http://${request.headers.host || 'localhost'}`);

    try {
      if (request.method === 'GET' && url.pathname === '/health') {
        return json(response, 200, { ok: true, service: 'web-cli-guard-node-bridge' });
      }

      if (request.method === 'GET' && url.pathname === '/sessions') {
        return json(response, 200, { ok: true, sessions: bridge.listSessions() });
      }

      if (request.method === 'GET' && url.pathname === '/capture') {
        const session = String(url.searchParams.get('session') || '');
        return json(response, 200, {
          ok: true,
          session,
          output: bridge.capture(session),
        });
      }

      if (request.method === 'POST' && url.pathname === '/send-text') {
        const payload = await readBody(request);
        const session = String(payload.session || '');
        const text = String(payload.text || '');
        const appendEnter = Object.prototype.hasOwnProperty.call(payload, 'append_enter')
          ? Boolean(payload.append_enter)
          : true;
        bridge.sendText(session, text, appendEnter);
        return json(response, 200, { ok: true, session });
      }

      if (request.method === 'POST' && url.pathname === '/send-key') {
        const payload = await readBody(request);
        const session = String(payload.session || '');
        const key = String(payload.key || '');
        bridge.sendKey(session, key);
        return json(response, 200, { ok: true, session, key });
      }

      return handleError(response, 404, 'not_found', 'Unknown endpoint.');
    } catch (error) {
      const message = error && error.message ? error.message : 'Bridge error.';
      if (message === 'Invalid JSON body.' || message === 'JSON body must be an object.') {
        return handleError(response, 400, 'bad_request', message);
      }
      if (message === 'Unknown or disallowed session.' || message === 'Unknown or disallowed key.') {
        return handleError(response, 403, 'forbidden', message);
      }
      return handleError(response, 502, 'bridge_error', message);
    }
  });

  server.listen(options.port, options.host, () => {
    process.stdout.write(`Listening on http://${options.host}:${options.port}\n`);
  });
}

function main() {
  try {
    const options = parseArgs(process.argv.slice(2));
    startServer(options);
  } catch (error) {
    process.stderr.write(`${error.message}\n`);
    process.exit(1);
  }
}

main();
