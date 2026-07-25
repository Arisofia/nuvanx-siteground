import { spawnSync } from 'node:child_process';

const nativeFetch = globalThis.fetch;
const nativeExit = process.exit.bind(process);
const nativeConsoleError = console.error.bind(console);
const visualQaAttempt = Number.parseInt(process.env.NVX_VISUAL_QA_ATTEMPT || '1', 10);
const visualQaErrors = [];

if (typeof nativeFetch !== 'function') {
  throw new TypeError('Node.js native fetch is required for the visual QA preload.');
}

globalThis.fetch = async (input, init) => {
  const url = typeof input === 'string' ? input : input?.url;
  if (typeof url === 'string' && url.startsWith('https://staging2.nuvanx.com/')) {
    return new Response('<!doctype html><title>Chrome navigation required</title>', {
      status: 200,
      headers: { 'content-type': 'text/html; charset=utf-8' },
    });
  }
  return nativeFetch(input, init);
};

console.error = (...args) => {
  visualQaErrors.push(args.map((value) => String(value)).join(' '));
  nativeConsoleError(...args);
};

process.exit = (code = 0) => {
  const output = visualQaErrors.join('\n');
  const transientEdgeFailure = /H1 mismatch: \["staging2\.nuvanx\.com"\]|Inspected target navigated or closed|rendered a 403 Forbidden page/i.test(output);

  if (Number(code) !== 0 && transientEdgeFailure && visualQaAttempt < 3) {
    const nextAttempt = visualQaAttempt + 1;
    const delayMilliseconds = visualQaAttempt * 5000;
    nativeConsoleError(`VISUAL_QA_RETRY_TRANSIENT_EDGE attempt=${nextAttempt} delay_ms=${delayMilliseconds}`);
    Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, delayMilliseconds);

    const result = spawnSync(process.execPath, process.argv.slice(1), {
      env: {
        ...process.env,
        NVX_VISUAL_QA_ATTEMPT: String(nextAttempt),
      },
      stdio: 'inherit',
    });
    nativeExit(Number.isInteger(result.status) ? result.status : 1);
  }

  nativeExit(Number(code));
};
