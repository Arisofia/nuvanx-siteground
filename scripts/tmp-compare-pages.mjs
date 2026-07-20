import https from 'node:https';
import fs from 'node:fs';

function fetch(url) {
  return new Promise((resolve, reject) => {
    https.get(url, {headers: {'User-Agent': 'Mozilla/5.0'}}, (res) => {
      let data = '';
      res.on('data', d => data += d);
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });
}

const [p1, p2] = await Promise.all([
  fetch('https://staging2.nuvanx.com/por-que-nuvanx/'),
  fetch('https://staging2.nuvanx.com/inversion-medicina-estetica/')
]);

// Extract the main content region: from <article id="post- to </article>
function extractContent(html, label) {
  const start = html.indexOf('<article id="post-');
  const end = html.indexOf('</article>', start) + 10;
  const content = html.substring(start, end);
  // Strip script tags for clarity
  const clean = content.replace(/<script[^>]*>[\s\S]*?<\/script>/g, '[script]');
  console.log(`\n${'='.repeat(60)}`);
  console.log(`${label} — ARTICLE CONTENT`);
  console.log('='.repeat(60));
  console.log(clean.replace(/>\s+</g, '>\n<').substring(0, 3000));
}

extractContent(p1, 'POR-QUE-NUVANX');
extractContent(p2, 'INVERSION-MEDICINA-ESTETICA');

// Also check if the page-shell PHP adds the h1 header
// Check for nvx-page__header
function checkPageHeader(html, label) {
  const idx = html.indexOf('nvx-page__header');
  if (idx > -1) {
    console.log(`\n${label} page__header:`, html.substring(idx - 5, idx + 200).replace(/\s+/g, ' '));
  }
}
checkPageHeader(p1, 'PQ');
checkPageHeader(p2, 'INV');

// Check all section classes used
function sectionClasses(html, label) {
  const sections = html.match(/<(?:header|section|div|article)[^>]*class="[^"]*nvx-[^"]*"[^>]*>/g) || [];
  console.log(`\n${label} structural elements (${sections.length}):`);
  sections.slice(0, 20).forEach(s => console.log(' ', s.substring(0, 120)));
}
sectionClasses(p1, 'PQ');
sectionClasses(p2, 'INV');
