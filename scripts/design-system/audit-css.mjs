import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const CSS_DIR = path.join(ROOT, 'wp-content/themes/nuvanx-medical/assets/css');
const OUT_DIR = path.join(ROOT, 'qa/design-system');

const SOURCE_FILES = fs
	.readdirSync(CSS_DIR)
	.filter((f) => f.endsWith('.css') && !f.endsWith('.min.css'))
	.sort();

const CANONICAL_TOKEN_PREFIXES = ['--nvx-', '--space-'];

function parseSelectors(css) {
	const blocks = [];
	const re = /([^{}]+)\{/g;
	let m;
	while ((m = re.exec(css)) !== null) {
		const raw = m[1].trim();
		if (!raw || raw.startsWith('@')) continue;
		const parts = raw.split(',').map((s) => s.trim()).filter(Boolean);
		blocks.push(...parts);
	}
	return blocks;
}

function extractRootTokens(css) {
	const tokens = {};
	const rootBlocks = [...css.matchAll(/:root\s*\{([^}]*)\}/g)];
	for (const block of rootBlocks) {
		const body = block[1];
		for (const line of body.split('\n')) {
			const t = line.trim();
			if (!t.startsWith('--')) continue;
			const idx = t.indexOf(':');
			if (idx < 0) continue;
			const name = t.slice(0, idx).trim();
			const value = t.slice(idx + 1).replace(/;+$/, '').trim();
			tokens[name] = value;
		}
	}
	return tokens;
}

function extractFontFamilies(css, file) {
	const hits = [];
	const re = /font-family\s*:\s*([^;!]+)/gi;
	let m;
	while ((m = re.exec(css)) !== null) {
		hits.push({ file, value: m[1].trim() });
	}
	return hits;
}

function countImportant(css, file) {
	const count = (css.match(/!important/g) || []).length;
	return count ? [{ file, count }] : [];
}

function extractClasses(css) {
	const classes = new Set();
	const re = /\.([a-zA-Z_][\w-]*)/g;
	let m;
	while ((m = re.exec(css)) !== null) {
		classes.add(m[1]);
	}
	return classes;
}

function lineCount(filePath) {
	return fs.readFileSync(filePath, 'utf8').split('\n').length;
}

const fileStats = {};
const allSelectors = new Map();
const allTokens = {};
const tokenByFile = {};
const fontFamilies = [];
const importantCounts = [];
const legacyMarkers = [];
const classByFile = {};

for (const file of SOURCE_FILES) {
	const abs = path.join(CSS_DIR, file);
	const css = fs.readFileSync(abs, 'utf8');
	fileStats[file] = {
		lines: lineCount(abs),
		bytes: fs.statSync(abs).size,
		rootBlocks: (css.match(/:root\s*\{/g) || []).length,
	};
	tokenByFile[file] = extractRootTokens(css);
	for (const [k, v] of Object.entries(tokenByFile[file])) {
		if (allTokens[k] && allTokens[k].value !== v) {
			if (!allTokens[k].collisions) allTokens[k].collisions = [];
			allTokens[k].collisions.push({ file, value: v });
		} else if (!allTokens[k]) {
			allTokens[k] = { value: v, definedIn: file };
		}
	}
	for (const sel of parseSelectors(css)) {
		const key = sel.replace(/\s+/g, ' ');
		if (!allSelectors.has(key)) allSelectors.set(key, []);
		allSelectors.get(key).push(file);
	}
	fontFamilies.push(...extractFontFamilies(css, file));
	importantCounts.push(...countImportant(css, file));
	classByFile[file] = [...extractClasses(css)].sort();

	const legacy = [
		'TICKET #43',
		'RECONCILIATION CONTRACT',
		'NUVANX HOME CANONICAL',
		'V4.1',
		'V3 FULL-HOME',
		'Phase 3',
		'fluid-organic',
	];
	for (const marker of legacy) {
		if (css.includes(marker)) legacyMarkers.push({ file, marker });
	}
}

const duplicateSelectors = [...allSelectors.entries()]
	.filter(([, files]) => files.length > 1)
	.map(([selector, files]) => ({ selector, files: [...new Set(files)], count: files.length }))
	.sort((a, b) => b.count - a.count);

const tokenCollisions = Object.entries(allTokens)
	.filter(([, meta]) => meta.collisions?.length)
	.map(([name, meta]) => ({
		token: name,
		canonical: { file: meta.definedIn, value: meta.value },
		collisions: meta.collisions,
	}));

const rootTokenFiles = SOURCE_FILES.filter((f) => fileStats[f].rootBlocks > 0);

const loadOrder = {
	global_stack: [
		'nvx-fonts (Google CDN)',
		'nvx-tokens.css',
		'nvx-base.css',
		'nvx-components.css',
		'nvx-site-layout.css',
		'nvx-fluid-organic-2026.css',
		'nvx-header.css',
		'nvx-footer.css',
		'nvx-pages.css',
	],
	conditional: {
		generic_page: ['nvx-gutenberg-pages.css'],
		p0_generic_form_sede: ['nvx-secondary-pages.css'],
		all_pages: ['nvx-visual-system.css', 'nvx-typography-alignment.css'],
		form: ['nvx-forms.css'],
		post: ['nvx-posts.css'],
		sede: ['nvx-sede-page.css'],
		home: ['nvx-brand-home.css', 'nvx-brand-system.js'],
		treatment: ['nvx-brand-treatment-core.css', 'treatment addon'],
		brand_other: ['nvx-brand-system.css', 'nvx-brand-system.js'],
	},
};

fs.mkdirSync(OUT_DIR, { recursive: true });

fs.writeFileSync(
	path.join(OUT_DIR, 'token-collisions.json'),
	JSON.stringify(
		{
			generatedAt: new Date().toISOString(),
			rootTokenFiles,
			totalCanonicalTokens: Object.keys(allTokens).length,
			collisions: tokenCollisions,
			tokenFiles: Object.fromEntries(
				Object.entries(tokenByFile).filter(([, t]) => Object.keys(t).length > 0)
			),
		},
		null,
		2
	)
);

fs.writeFileSync(
	path.join(OUT_DIR, 'duplicate-selectors.json'),
	JSON.stringify(
		{
			generatedAt: new Date().toISOString(),
			totalDuplicateSelectors: duplicateSelectors.length,
			topDuplicates: duplicateSelectors.slice(0, 200),
			byFilePair: duplicateSelectors.reduce((acc, row) => {
				const pair = [...new Set(row.files)].sort().join(' + ');
				acc[pair] = (acc[pair] || 0) + 1;
				return acc;
			}, {}),
		},
		null,
		2
	)
);

const componentPrefixes = [
	'nvx-brand-',
	'nvx-home-',
	'nvx-v3-',
	'nvx-editorial-',
	'nvx-fluid-',
	'nvx-btn',
	'nvx-button',
	'nvx-card',
	'nvx-faq',
	'nvx-cta',
	'nvx-header',
	'nvx-footer',
	'nvx-nav',
	'nvx-shell',
	'nvx-index',
	'nvx-media',
	'nvx-pattern',
];

const allClasses = new Set();
for (const list of Object.values(classByFile)) list.forEach((c) => allClasses.add(c));

const inventory = {};
for (const prefix of componentPrefixes) {
	inventory[prefix] = [...allClasses].filter((c) => c.startsWith(prefix.replace(/\.$/, ''))).sort();
}

const fontSummary = {};
for (const { file, value } of fontFamilies) {
	const key = value.replace(/\s+/g, ' ');
	if (!fontSummary[key]) fontSummary[key] = [];
	fontSummary[key].push(file);
}

const auditSummary = {
	files: fileStats,
	totalSourceLines: Object.values(fileStats).reduce((s, f) => s + f.lines, 0),
	duplicateSelectors: duplicateSelectors.length,
	tokenCollisions: tokenCollisions.length,
	rootTokenFiles,
	importantCounts,
	legacyMarkers,
	fontFamilies: Object.entries(fontSummary).map(([value, files]) => ({ value, files: [...new Set(files)] })),
};

fs.writeFileSync(path.join(OUT_DIR, 'audit-summary.json'), JSON.stringify(auditSummary, null, 2));
fs.writeFileSync(path.join(OUT_DIR, 'component-classes.json'), JSON.stringify(inventory, null, 2));

console.log(JSON.stringify(auditSummary, null, 2));