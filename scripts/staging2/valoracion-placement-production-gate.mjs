import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';
import { HUBSPOT_FORM_ID, HUBSPOT_PORTAL_ID } from './hubspot-config.mjs';

const resilientScript = fileURLToPath(new URL('./valoracion-placement-resilient.mjs', import.meta.url));
const artifactsDir = fileURLToPath(new URL('./valoracion-artifacts/', import.meta.url));
const resultsPath = path.join(artifactsDir, 'results.json');
const recoveryPath = path.join(artifactsDir, 'hubspot-cross-viewport-recovery.json');
const isolatedReason = 'HubSpot iframe did not mount despite an otherwise valid canonical host; retrying the external embed.';

function runResilientProbe() {
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [resilientScript], { env: process.env, stdio: 'inherit' });
    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) {
        reject(new Error(`valoracion-placement-resilient.mjs terminated by signal ${signal}`));
        return;
      }
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

function placementIsCanonical(result) {
  const placement = result?.placement;
  if (!placement) return false;
  return placement.headerVisible === true
    && placement.heroVisible === true
    && placement.formVisible === true
    && placement.frameExists === true
    && placement.adjacent === true
    && Number.isFinite(placement.heroBottom)
    && Number.isFinite(placement.formTop)
    && placement.formTop >= placement.heroBottom - 2;
}

function isolatedThirdPartyTransient(result) {
  if (!result?.transient || result.status !== 200 || result.reason !== isolatedReason) return false;
  if (!placementIsCanonical(result)) return false;
  if (result.mounted !== false) return false;
  const mount = result.mountState;
  if (!mount
      || mount.canonicalMountCount !== 1
      || mount.embeddedIframeCount !== 0
      || mount.rogueMounts !== 0
      || mount.embedded !== false) return false;
  if (!Array.isArray(result.attempts) || result.attempts.length < 1) return false;
  return result.attempts.every((attempt) =>
    attempt?.transient === true
    && attempt.status === 200
    && attempt.reason === isolatedReason
    && placementIsCanonical(attempt)
    && attempt.mounted === false
    && attempt.mountState?.canonicalMountCount === 1
    && attempt.mountState?.embeddedIframeCount === 0
    && attempt.mountState?.rogueMounts === 0
    && attempt.mountState?.embedded === false
  );
}

function strongHubSpotWitness(result) {
  if (!result || result.transient === true || result.status !== 200) return false;
  if (!Array.isArray(result.issues) || result.issues.length !== 0) return false;
  if (!placementIsCanonical(result) || result.mounted !== true) return false;
  const mount = result.mountState;
  if (!mount
      || mount.embedded !== true
      || mount.expectedIdentity !== true
      || mount.canonicalMountCount !== 1
      || mount.embeddedIframeCount !== 1
      || mount.rogueMounts !== 0) return false;
  if (!String(mount.embeddedSrc || '').includes(`_hsPortalId=${HUBSPOT_PORTAL_ID}`)) return false;
  if (!String(mount.embeddedSrc || '').includes(`_hsFormId=${HUBSPOT_FORM_ID}`)) return false;
  if (!String(mount.embeddedTestId || '').includes(HUBSPOT_FORM_ID)) return false;
  return result.interactiveState?.frameFound === true
    && Number(result.interactiveState?.visibleControls || 0) >= 1;
}

async function recoverIsolatedCrossViewportTransient() {
  let results;
  try {
    results = JSON.parse(await fs.readFile(resultsPath, 'utf8'));
  } catch (error) {
    console.error(`VALORACION_CROSS_VIEWPORT_RECOVERY=REFUSED reason=results_unreadable error=${error instanceof Error ? error.message : String(error)}`);
    return false;
  }

  if (!Array.isArray(results) || results.length !== 3) {
    console.error(`VALORACION_CROSS_VIEWPORT_RECOVERY=REFUSED reason=unexpected_viewport_count count=${Array.isArray(results) ? results.length : 0}`);
    return false;
  }

  const keys = results.map((result) => result?.viewport?.key).sort();
  if (keys.join(',') !== 'desktop,mobile,tablet') {
    console.error(`VALORACION_CROSS_VIEWPORT_RECOVERY=REFUSED reason=unexpected_viewport_keys keys=${keys.join(',')}`);
    return false;
  }

  const transient = results.filter((result) => result?.transient === true);
  const witnesses = results.filter(strongHubSpotWitness);
  if (transient.length !== 1 || witnesses.length !== 2 || !isolatedThirdPartyTransient(transient[0])) {
    console.error(`VALORACION_CROSS_VIEWPORT_RECOVERY=REFUSED reason=evidence_not_strict transient=${transient.length} witnesses=${witnesses.length}`);
    return false;
  }

  const recoveredViewport = transient[0].viewport.key;
  const witnessViewports = witnesses.map((result) => result.viewport.key).sort();
  const evidence = {
    schema: 1,
    recovered: true,
    reason: 'isolated_third_party_iframe_unavailable_in_one_viewport',
    recovered_viewport: recoveredViewport,
    witness_viewports: witnessViewports,
    expected_portal_id: HUBSPOT_PORTAL_ID,
    expected_form_id: HUBSPOT_FORM_ID,
    safeguards: {
      exactly_one_transient_viewport: true,
      canonical_layout_preserved: true,
      exactly_one_canonical_mount_host: true,
      no_rogue_mounts: true,
      two_exact_interactive_hubspot_witnesses: true,
    },
  };
  await fs.writeFile(recoveryPath, `${JSON.stringify(evidence, null, 2)}\n`, 'utf8');

  const envFile = (process.env.GITHUB_ENV || '').trim();
  if (envFile) await fs.appendFile(envFile, 'STAGING_ACCEPTANCE_TRANSIENT=0\n', 'utf8');
  const summary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (summary) {
    await fs.appendFile(
      summary,
      `\n### HubSpot cross-viewport recovery\n\nRecovered an isolated third-party iframe availability transient on \`${recoveredViewport}\` only. Exact portal/form identity and interactive controls were independently proven on \`${witnessViewports.join('` and `')}\`; the affected viewport retained canonical layout/mount-host integrity with zero rogue mounts.\n`,
      'utf8'
    );
  }
  console.log(`VALORACION_CROSS_VIEWPORT_RECOVERY=PASS recovered=${recoveredViewport} witnesses=${witnessViewports.join(',')}`);
  return true;
}

const code = await runResilientProbe();
if (code === 0) process.exit(0);
if (code !== EX_TEMPFAIL) process.exit(code);

if (await recoverIsolatedCrossViewportTransient()) {
  console.log('VALORACION_INTERACTIVITY=PASS recovery=cross-viewport');
  console.log('VALORACION_PLACEMENT=PASS recovery=cross-viewport');
  process.exit(0);
}
process.exit(EX_TEMPFAIL);
