#!/usr/bin/env node

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/+$/, '');
const expectedSha = String(
  process.env.EXPECTED_SHA || process.env.CANDIDATE_SHA || process.env.GITHUB_SHA || ''
).trim();

const route = '/matriz-diagnostico-facial-estructura-piel-musculo-grasa/';
const url = `${baseUrl}${route}`;
const expectedCanonical = url;
const expectedOgUrl = baseUrl === 'https://nuvanx.com'
  ? expectedCanonical
  : `https://nuvanx.com${route}`;
const expectedTitle = 'Matriz de diagnóstico facial | NUVANX Madrid';
const expectedH1 = 'Matriz de diagnóstico facial: estructura, músculo, piel y grasa';
const expectedRuntimeContract = '20260815-db-authoritative-wp-bootstrap-v2';
const neighbouringSlug = 'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico';

function attr(tag, name) {
  const match = tag.match(new RegExp(`${name}\\s*=\\s*["']([^"']*)["']`, 'i'));
  return match ? match[1].trim() : '';
}

function tags(html, name) {
  return html.match(new RegExp(`<${name}\\b[^>]*>`, 'gi')) || [];
}

function firstText(html, name) {
  const match = html.match(new RegExp(`<${name}\\b([^>]*)>([\\s\\S]*?)<\\/${name}>`, 'i'));
  if (!match) return { attrs: '', text: '' };
  return {
    attrs: match[1] || '',
    text: match[2].replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim(),
  };
}

function canonicalFrom(html) {
  for (const tag of tags(html, 'link')) {
    if (attr(tag, 'rel').toLowerCase() === 'canonical') return attr(tag, 'href');
  }
  return '';
}

function metaContent(html, key, value) {
  for (const tag of tags(html, 'meta')) {
    if (attr(tag, key).toLowerCase() === value.toLowerCase()) return attr(tag, 'content');
  }
  return '';
}

let response;
try {
  response = await fetch(url, {
    redirect: 'manual',
    headers: {
      'cache-control': 'no-cache',
      pragma: 'no-cache',
      'user-agent': 'NUVANX-Staging-QA governed-blog-runtime-contract',
      accept: 'text/html,application/xhtml+xml',
    },
  });
} catch (error) {
  console.error(`GOVERNED_BLOG_RUNTIME=TRANSIENT url=${url} error=${error?.message || error}`);
  process.exit(75);
}

if (response.status === 408 || response.status === 429 || response.status >= 500) {
  console.error(`GOVERNED_BLOG_RUNTIME=TRANSIENT url=${url} status=${response.status}`);
  process.exit(75);
}

let html = '';
try {
  html = await response.text();
} catch (error) {
  console.error(`GOVERNED_BLOG_RUNTIME=TRANSIENT url=${url} body_error=${error?.message || error}`);
  process.exit(75);
}

const title = firstText(html, 'title').text;
const canonical = canonicalFrom(html);
const ogUrl = metaContent(html, 'property', 'og:url');
const deploySha = metaContent(html, 'name', 'nvx-deploy-sha');
const runtimeContract = metaContent(html, 'name', 'nvx-governed-blog-runtime-contract');
const h1 = firstText(html, 'h1');
const h1IdMatch = h1.attrs.match(/\bid\s*=\s*["']([^"']+)["']/i);
const h1Id = h1IdMatch ? h1IdMatch[1] : '';

const issues = [];
if (response.status !== 200) issues.push(`status:${response.status}`);
if (title !== expectedTitle) issues.push(`title:${title}`);
if (canonical !== expectedCanonical) issues.push(`canonical:${canonical}`);
if (ogUrl !== expectedOgUrl) issues.push(`og_url:${ogUrl}`);
if (h1.text !== expectedH1) issues.push(`h1:${h1.text}`);
if (!h1Id.includes('3334')) issues.push(`h1_id:${h1Id}`);
if (expectedSha && deploySha !== expectedSha) issues.push(`deploy_sha:${deploySha}`);
if (runtimeContract !== expectedRuntimeContract) issues.push(`runtime_contract:${runtimeContract || 'missing'}`);
if (
  title.includes('Tratamientos faciales sin cirugía') ||
  canonical.includes(neighbouringSlug) ||
  ogUrl.includes(neighbouringSlug) ||
  h1Id.includes('3310')
) {
  issues.push('neighbouring_post_leak:3310');
}

if (issues.length > 0) {
  console.error(
    `GOVERNED_BLOG_RUNTIME=FAIL url=${url} ` +
    `detail=${JSON.stringify({ status: response.status, title, canonical, ogUrl, h1: h1.text, h1Id, deploySha, runtimeContract, issues })}`
  );
  process.exit(1);
}

console.log(
  `GOVERNED_BLOG_RUNTIME=PASS url=${url} post_id=3334 canonical=${canonical} og_url=${ogUrl} sha=${deploySha} runtime_contract=${runtimeContract}`
);
