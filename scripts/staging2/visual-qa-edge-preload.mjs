const nativeFetch = globalThis.fetch;

if (typeof nativeFetch !== 'function') {
  throw new Error('Node.js native fetch is required for the visual QA preload.');
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
