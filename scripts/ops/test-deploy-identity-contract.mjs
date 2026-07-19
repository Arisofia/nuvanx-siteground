#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const requireMatch = (source, pattern, message) => {
  if (!pattern.test(source)) throw new Error(message);
};

const staging = read('.github/workflows/deploy-theme-staging2.yml');
const production = read('.github/workflows/deploy-theme-production.yml');
const verifier = read('scripts/seo/verify-p0-deployment.mjs');
const runtime = read('wp-content/themes/nuvanx-medical/inc/nvx-environment-flags.php');

requireMatch(runtime, /\.nvx-deploy-sha/, 'runtime marker file lookup is missing');
requireMatch(runtime, /meta name=\\?"nvx-deploy-sha\\?"/, 'public deploy SHA meta marker is missing');
requireMatch(runtime, /\^\[a-f0-9\]\{40\}\$/, 'runtime must accept only full lowercase SHA values');

requireMatch(staging, /printf '%s\\n' "\$SHA" > "\$\{\{ env\.THEME_REL \}\}\/\.nvx-deploy-sha"/, 'staging does not stamp the checked-out SHA');
requireMatch(staging, /deployed_sha: \$\{\{ steps\.identity\.outputs\.deployed_sha \}\}/, 'staging immutable SHA output is missing');
requireMatch(staging, /EXPECTED_DEPLOY_SHA: \$\{\{ needs\.deploy-and-flush\.outputs\.deployed_sha \}\}/, 'staging rendered SHA verification is missing');
requireMatch(staging, /ref: \$\{\{ needs\.deploy-and-flush\.outputs\.deployed_sha \}\}/, 'staging verifier must check out the immutable SHA');

requireMatch(production, /description: Exact 40-character SHA already validated on staging2/, 'production input is not constrained to an approved SHA');
requireMatch(production, /\[\[ "\$REF" =~ \^\[0-9a-f\]\{40\}\$ \]\]/, 'production full-SHA guard is missing');
requireMatch(production, /Require the same SHA to be live on staging2/, 'production staging identity preflight is missing');
requireMatch(production, /name: Checkout trusted promotion controls[\s\S]*?ref: \$\{\{ github\.sha \}\}[\s\S]*?path: trusted-control/, 'promotion gate must check out trusted workflow controls');
requireMatch(production, /run: node trusted-control\/scripts\/seo\/verify-p0-deployment\.mjs/, 'promotion gate must execute the trusted verifier');
requireMatch(production, /EXPECTED_DEPLOY_SHA: \$\{\{ inputs\.ref \}\}/, 'production staging preflight does not compare the requested SHA');
requireMatch(production, /printf '%s\\n' "\$SHA" > "\$\{\{ env\.THEME_REL \}\}\/\.nvx-deploy-sha"/, 'production does not stamp the approved SHA');
requireMatch(production, /EXPECTED_DEPLOY_SHA: \$\{\{ needs\.deploy-and-flush\.outputs\.deployed_sha \}\}/, 'production rendered SHA verification is missing');
if (/ref:[\s\S]{0,180}default: master/.test(production)) throw new Error('production retains a mutable default ref');

requireMatch(verifier, /EXPECTED_DEPLOY_SHA/, 'browser verifier does not accept an expected SHA');
requireMatch(verifier, /meta\[name="nvx-deploy-sha"\]/, 'browser verifier does not inspect the public marker');
requireMatch(verifier, /deploy SHA mismatch/, 'browser verifier does not fail on identity mismatch');

console.log('PASS: exact deployment identity contract');
