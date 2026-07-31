import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

import {
  safeName,
  locateChrome,
  CDPSession,
  waitForChrome,
  createTarget,
  closeSession,
} from '../../scripts/staging2/visual-qa-common.mjs';

test('safeName normalizes root and route names', () => {
  assert.equal(safeName('/'), 'home');
  assert.equal(safeName('/equipo-medico/'), 'equipo-medico');
  assert.equal(safeName('/a/b/'), 'a__b');
  assert.equal(safeName(null), 'home');
});

test('locateChrome accepts an explicit existing executable path', () => {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'nvx-chrome-'));
  const executable = path.join(directory, 'chrome');
  fs.writeFileSync(executable, '#!/bin/sh\nexit 0\n');
  const previousBin = process.env.CHROME_BIN;
  const previousPath = process.env.PATH;
  try {
    process.env.CHROME_BIN = executable;
    process.env.PATH = '';
    assert.equal(locateChrome(), executable);
  } finally {
    if (previousBin === undefined) delete process.env.CHROME_BIN;
    else process.env.CHROME_BIN = previousBin;
    if (previousPath === undefined) delete process.env.PATH;
    else process.env.PATH = previousPath;
    fs.rmSync(directory, { recursive: true, force: true });
  }
});

test('CDPSession resolves successful command responses', async () => {
  const session = new CDPSession('ws://example.test');
  let outbound;
  session.webSocket = {
    readyState: 1,
    send(payload) { outbound = JSON.parse(payload); },
    close() {},
  };
  const pending = session.send('Runtime.enable', { enabled: true }, 1000);
  assert.equal(outbound.method, 'Runtime.enable');
  assert.deepEqual(outbound.params, { enabled: true });
  session.handleMessage({ data: JSON.stringify({ id: outbound.id, result: { accepted: true } }) });
  assert.deepEqual(await pending, { accepted: true });
});

test('CDPSession rejects protocol errors', async () => {
  const session = new CDPSession('ws://example.test');
  let outbound;
  session.webSocket = {
    readyState: 1,
    send(payload) { outbound = JSON.parse(payload); },
    close() {},
  };
  const pending = session.send('Page.fail', {}, 1000);
  session.handleMessage({ data: JSON.stringify({ id: outbound.id, error: { code: -1, message: 'Rejected' } }) });
  await assert.rejects(pending, /Rejected \(-1\)/);
});

test('CDPSession dispatches and unsubscribes one-shot waiters', async () => {
  const session = new CDPSession('ws://example.test');
  const pending = session.waitFor('Page.loadEventFired', 1000);
  session.dispatchEvent('Page.loadEventFired', { timestamp: 42 });
  assert.deepEqual(await pending, { timestamp: 42 });
  assert.equal(session.eventHandlers.get('Page.loadEventFired')?.size || 0, 0);
});

test('CDPSession rejects event waiters when the session closes', async () => {
  const session = new CDPSession('ws://example.test');
  const pending = session.waitFor('Page.loadEventFired', 1000);
  const rejection = assert.rejects(pending, /CDP session closed before command completed/);
  session.closeSocket();
  await rejection;
  assert.equal(session.pendingWaiters?.size || 0, 0);
  assert.equal(session.eventHandlers.size, 0);
});

test('CDPSession.evaluate forwards awaitPromise parameter', async () => {
  const session = new CDPSession('ws://example.test');
  let outbound;
  session.webSocket = {
    readyState: 1,
    send(payload) { outbound = JSON.parse(payload); },
    close() {},
  };
  const pending = session.evaluate('Promise.resolve(1)', false);
  assert.equal(outbound.params.awaitPromise, false);
  session.handleMessage({ data: JSON.stringify({ id: outbound.id, result: { result: { value: 1 } } }) });
  assert.equal(await pending, 1);
});

test('CDPSession.call serializes arguments into browser evaluation', async () => {
  const session = new CDPSession('ws://example.test');
  let expression = '';
  session.evaluate = async (value) => {
    expression = value;
    return 'ok';
  };
  const result = await session.call((first, second) => `${first}:${second}`, 'A', 2);
  assert.equal(result, 'ok');
  assert.match(expression, /\("A",2\)$/);
});

test('waitForChrome returns when the DevTools endpoint responds', async () => {
  const previousFetch = globalThis.fetch;
  let requested = '';
  try {
    globalThis.fetch = async (url) => {
      requested = String(url);
      return { ok: true };
    };
    await waitForChrome(9333);
    assert.equal(requested, 'http://127.0.0.1:9333/json/version');
  } finally {
    globalThis.fetch = previousFetch;
  }
});

test('createTarget sends PUT and returns target metadata', async () => {
  const previousFetch = globalThis.fetch;
  let invocation;
  try {
    globalThis.fetch = async (url, options) => {
      invocation = { url: String(url), options };
      return {
        ok: true,
        async json() { return { webSocketDebuggerUrl: 'ws://target' }; },
      };
    };
    assert.deepEqual(await createTarget(9444), { webSocketDebuggerUrl: 'ws://target' });
    assert.match(invocation.url, /^http:\/\/127\.0\.0\.1:9444\/json\/new\?/);
    assert.equal(invocation.options.method, 'PUT');
  } finally {
    globalThis.fetch = previousFetch;
  }
});

test('closeSession closes the page before the socket', async () => {
  const calls = [];
  await closeSession({
    async send(method, params, timeout) { calls.push(['send', method, params, timeout]); },
    closeSocket() { calls.push(['close']); },
  });
  assert.deepEqual(calls, [
    ['send', 'Page.close', {}, 3000],
    ['close'],
  ]);
  await closeSession(null);
});
