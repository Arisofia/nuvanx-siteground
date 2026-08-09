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

  return {
    id,
    slug: String(page.slug || ''),
    link,
    title: {
      rendered: String(page.title || page.slug || (manifestPath === '/' ? 'Inicio' : `Página ${id}`)),
    },
  };
});

const ids = normalizedInventory.map((page) => page.id);
if (new Set(ids).size !== 52) {
  throw new Error('Block C page inventory contains duplicate IDs');
}

const pagesEndpoint = `${baseUrl}/wp-json/wp/v2/pages`;
const originalFetch = globalThis.fetch.bind(globalThis);

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
  // endpoint, optionally with a query string). Single-page requests such as
  // `${pagesEndpoint}/2645`, page navigation, image requests, boundary probes
  // and rendered assertions still target the live Staging2 site.
  const isPagesCollection =
    requestUrl === pagesEndpoint || requestUrl.startsWith(`${pagesEndpoint}?`);
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
