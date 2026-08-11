/**
 * Return every URL in a Playwright navigation chain that leaves the expected host.
 *
 * @param {import('playwright').Response|null} response Final navigation response.
 * @param {string} finalUrl Browser URL after navigation.
 * @param {string} expectedHost Allowed hostname.
 * @returns {string[]}
 */
export function crossHostNavigationUrls(response, finalUrl, expectedHost) {
  const urls = [finalUrl];
  let request = response?.request();
  while (request) {
    urls.push(request.url());
    request = request.redirectedFrom();
  }

  return [...new Set(urls)].filter((url) => {
    try {
      return new URL(url).hostname !== expectedHost;
    } catch {
      return true;
    }
  });
}
