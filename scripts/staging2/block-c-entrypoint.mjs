import fs from 'node:fs/promises';

const inventoryFile = (process.env.WORDPRESS_PAGES_FILE || '').trim();

if (inventoryFile) {
  const rawInventory = JSON.parse(await fs.readFile(inventoryFile, 'utf8'));
  if (!Array.isArray(rawInventory) || rawInventory.length !== 52) {
    throw new Error(`Trusted WordPress page inventory must contain 52 pages; got ${Array.isArray(rawInventory) ? rawInventory.length : 'non-array'}`);
  }

  const normalizedInventory = rawInventory.map((page) => {
    const link = String(page.link || '').trim();
    if (!link) {
      throw new Error(`Trusted WordPress page inventory entry ${page.id ?? 'unknown'} is missing link`);
    }

    return {
      id: Number(page.id),
      slug: String(page.slug || ''),
      link,
      title: {
        rendered: String(page.title || ''),
      },
    };
  });

  const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
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

    if (requestUrl.startsWith(pagesEndpoint)) {
      return new Response(JSON.stringify(normalizedInventory), {
        status: 200,
        headers: {
          'content-type': 'application/json; charset=utf-8',
          'x-nvx-page-inventory-source': 'trusted-wp-cli',
        },
      });
    }

    return originalFetch(input, init);
  };

  console.log(`BLOCK_C_PAGE_INVENTORY=trusted-wp-cli count=${normalizedInventory.length}`);
} else {
  console.log('BLOCK_C_PAGE_INVENTORY=live-rest-fallback');
}

await import('./block-c-52x3.mjs');
