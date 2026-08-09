import fs from 'node:fs/promises';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const inventoryFile = (process.env.WORDPRESS_PAGES_FILE || '').trim();
const manifestUrl = new URL('./published-pages-manifest.json', import.meta.url);

let source = 'governed-manifest';
let rawInventory;

if (inventoryFile) {
  rawInventory = JSON.parse(await fs.readFile(inventoryFile, 'utf8'));
  source = 'trusted-wp-cli';
} else {
  rawInventory = JSON.parse(await fs.readFile(manifestUrl, 'utf8'));
}

if (!Array.isArray(rawInventory) || rawInventory.length !== 52) {
  throw new Error(`Block C page inventory must contain 52 pages; got ${Array.isArray(rawInventory) ? rawInventory.length : 'non-array'}`);
}

const baseOrigin = new URL(baseUrl).origin;

const normalizedInventory = rawInventory.map((page) => {
  const id = Number(page.id);
  if (!Number.isInteger(id) || id <= 0) {
    throw new Error(`Block C page inventory contains invalid ID: ${page.id}`);
  }

  const manifestPath = String(page.path || '').trim();
  const link = String(page.link || (manifestPath ? `${baseUrl}${manifestPath === '/' ? '/' : manifestPath.replace(/^\/+/, '/')}` : '')).trim();
  if (!link) {
    throw new Error(`Block C page inventory entry ${id} is missing link/path`);
  }

  const pageUrl = new URL(link, baseUrl);
  if (pageUrl.origin !== baseOrigin) {
    throw new Error(`Block C page inventory entry ${id} targets a different origin: ${pageUrl.origin}`);
  }

  return {
    id,
    slug: String(page.slug || ''),
    link: pageUrl.href,
    title: {
      rendered: String(page.title || page.slug || (manifestPath === '/' ? 'Inicio' : `Página ${id}`)),
    },
  };
});

const ids = normalizedInventory.map((page) => page.id);
if (new Set(ids).size !== 52) {
  throw new Error('Block C page inventory contains duplicate IDs');
}

const links = normalizedInventory.map((page) => page.link);
if (new Set(links).size !== normalizedInventory.length) {
  throw new Error('Block C page inventory contains duplicate links');
}

const pagesEndpoint = `${baseUrl}/wp-json/wp/v2/pages`;
const originalFetch = globalThis.fetch.bind(globalThis);

function requestMethod(input, init) {
  if (typeof init?.method === 'string') {
    return init.method.toUpperCase();
  }
  if (input && typeof input === 'object' && typeof input.method === 'string') {
    return input.method.toUpperCase();
  }
  return 'GET';
}

globalThis.fetch = async (input, init) => {
  const requestUrl =
    typeof input === 'string'
      ? input
      : input instanceof URL
        ? input.href
        : typeof input?.url === 'string'
          ? input.url
          : '';

  // Only replace the initial publication-inventory REST call (the collection
  // endpoint, optionally with a query string) issued as a GET. Single-page
  // requests such as `${pagesEndpoint}/2645`, non-GET methods, page navigation,
  // image requests, boundary probes and rendered assertions still target the
  // live Staging2 site.
  const isPagesCollection =
    requestMethod(input, init) === 'GET' &&
    (requestUrl === pagesEndpoint || requestUrl.startsWith(`${pagesEndpoint}?`));
  if (isPagesCollection) {
    return new Response(JSON.stringify(normalizedInventory), {
      status: 200,
      headers: {
        'content-type': 'application/json; charset=utf-8',
        'x-nvx-page-inventory-source': source,
      },
    });
  }

  return originalFetch(input, init);
};

console.log(`BLOCK_C_PAGE_INVENTORY=${source} count=${normalizedInventory.length}`);
await import('./block-c-52x3.mjs');
