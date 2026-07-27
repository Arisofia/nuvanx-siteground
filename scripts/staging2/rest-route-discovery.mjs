/**
 * Discover every public WordPress route from inside a browser/CDP context.
 *
 * The function is intentionally self-contained because CDPSession.call()
 * serializes it and evaluates it in Chrome. It can also be executed directly
 * in Node tests with mocked `fetch` and `location` globals.
 *
 * @param {string[]} seed Initial routes that must always be audited.
 * @param {number} perPage WordPress REST page size.
 * @param {number} maxPages Maximum collection pages accepted before probing.
 * @returns {Promise<{routes:string[],counts:{pages:number,posts:number,categories:number,skipped_links:number}}>} Discovery payload.
 */
export async function discoverWordPressRoutes(seed, perPage, maxPages) {
  const discovered = new Set(seed);
  const counts = { pages: 0, posts: 0, categories: 0, skipped_links: 0 };

  function parseTotalPages(header) {
    if (header === null) return null;
    const totalPages = Number(header);
    return Number.isInteger(totalPages) && totalPages >= 1 ? totalPages : null;
  }

  function assertTotalPagesWithinCap(endpoint, totalPages) {
    if (totalPages !== null && totalPages > maxPages) {
      throw new Error(
        `REST collection exceeds pagination cap: endpoint=${endpoint}, totalPages=${totalPages}, maxPages=${maxPages}`,
      );
    }
  }

  async function responseIsInvalidPage(response) {
    if (response.status !== 400) return false;
    try {
      const payload = await response.json();
      return payload?.code === 'rest_post_invalid_page_number';
    } catch {
      return false;
    }
  }

  async function fetchCollectionPage(endpoint, page) {
    const separator = endpoint.includes('?') ? '&' : '?';
    return fetch(
      `${endpoint}${separator}per_page=${perPage}&page=${page}`,
      { credentials: 'same-origin' },
    );
  }

  async function parseCollectionItems(response, endpoint, page) {
    const items = await response.json();
    if (!Array.isArray(items)) {
      throw new TypeError(`REST collection returned non-array payload: ${endpoint}, page=${page}`);
    }
    if (items.length > perPage) {
      throw new Error(
        `REST collection exceeded requested page size: endpoint=${endpoint}, page=${page}, items=${items.length}, perPage=${perPage}`,
      );
    }
    return items;
  }

  async function throwCollectionFailure(response, endpoint, page) {
    throw new Error(
      `REST collection failed: ${endpoint}, page=${page}, status=${response.status}`,
    );
  }

  async function probeCollectionEnd(endpoint, page) {
    const response = await fetchCollectionPage(endpoint, page);
    if (!response.ok) {
      if (await responseIsInvalidPage(response)) return;
      await throwCollectionFailure(response, endpoint, page);
    }

    const totalPages = parseTotalPages(response.headers.get('X-WP-TotalPages'));
    assertTotalPagesWithinCap(endpoint, totalPages);
    const items = await parseCollectionItems(response, endpoint, page);
    if (items.length === 0) return;

    throw new Error(
      `REST collection exceeds inferred pagination cap: endpoint=${endpoint}, probePage=${page}, items=${items.length}, maxPages=${maxPages}`,
    );
  }

  async function fetchWpCollectionBrowser(endpoint) {
    const collected = [];
    let declaredTotalPages = null;

    for (let page = 1; page <= maxPages; page += 1) {
      const response = await fetchCollectionPage(endpoint, page);
      if (!response.ok) {
        const invalidPage = await responseIsInvalidPage(response);
        if (invalidPage && declaredTotalPages === null) return collected;
        if (invalidPage) {
          throw new Error(
            `REST collection ended before declared total: endpoint=${endpoint}, page=${page}, totalPages=${declaredTotalPages}`,
          );
        }
        await throwCollectionFailure(response, endpoint, page);
      }

      const currentTotalPages = parseTotalPages(response.headers.get('X-WP-TotalPages'));
      assertTotalPagesWithinCap(endpoint, currentTotalPages);
      if (currentTotalPages !== null) {
        if (declaredTotalPages !== null && currentTotalPages !== declaredTotalPages) {
          throw new Error(
            `REST pagination header changed: endpoint=${endpoint}, page=${page}, previous=${declaredTotalPages}, current=${currentTotalPages}`,
          );
        }
        declaredTotalPages = currentTotalPages;
        if (page > declaredTotalPages) {
          throw new Error(
            `REST collection returned a page beyond its declared total: endpoint=${endpoint}, page=${page}, totalPages=${declaredTotalPages}`,
          );
        }
      }

      const items = await parseCollectionItems(response, endpoint, page);
      if (items.length === 0) {
        if (declaredTotalPages !== null && page <= declaredTotalPages) {
          throw new Error(
            `REST collection returned an empty page before declared end: endpoint=${endpoint}, page=${page}, totalPages=${declaredTotalPages}`,
          );
        }
        return collected;
      }

      collected.push(...items);

      if (declaredTotalPages !== null) {
        if (page === declaredTotalPages) return collected;
        continue;
      }
      if (items.length < perPage) return collected;
      if (page === maxPages) {
        await probeCollectionEnd(endpoint, maxPages + 1);
        return collected;
      }
    }

    return collected;
  }

  function addDiscoveredLink(link) {
    if (typeof link !== 'string' || link.trim() === '') {
      counts.skipped_links += 1;
      return;
    }
    try {
      const url = new URL(link, location.origin);
      if (url.origin !== location.origin) {
        counts.skipped_links += 1;
        return;
      }
      discovered.add(url.pathname.endsWith('/') ? url.pathname : `${url.pathname}/`);
    } catch {
      counts.skipped_links += 1;
    }
  }

  const collections = [
    ['pages', '/wp-json/wp/v2/pages?status=publish&_fields=link'],
    ['posts', '/wp-json/wp/v2/posts?status=publish&_fields=link'],
    ['categories', '/wp-json/wp/v2/categories?hide_empty=true&_fields=link'],
  ];

  for (const [key, endpoint] of collections) {
    const items = await fetchWpCollectionBrowser(endpoint);
    counts[key] = items.length;
    for (const item of items) addDiscoveredLink(item.link);
  }

  const routes = Array.from(discovered).sort((a, b) => a.localeCompare(b, 'es'));
  return { routes, counts };
}
