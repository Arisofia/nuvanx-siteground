#!/usr/bin/env node
/**
 * setup-gtm-conversion-trigger.js
 *
 * Creates the canonical nvx_conversion_signal → generate_lead Google Ads
 * conversion tag in GTM-W55RGVF2 and publishes a new container version.
 *
 * Idempotent: skips creation if trigger/tag with matching name already exists.
 *
 * Usage:
 *   source .env.local && node scripts/seo/setup-gtm-conversion-trigger.js
 *
 * Required environment variables:
 *   GTM_REFRESH_TOKEN       — OAuth2 refresh token with scope tagmanager.edit.containers
 *   GTM_CLIENT_ID           — OAuth2 client ID (can reuse GOOGLE_ADS_CLIENT_ID if same project)
 *   GTM_CLIENT_SECRET       — OAuth2 client secret
 *   GTM_ACCOUNT_ID          — Tag Manager account ID (6362896218)
 *   GTM_CONTAINER_ID        — Tag Manager container ID (256599823)
 *
 * Constants below are hardcoded based on the audited live configuration:
 *   Container:     GTM-W55RGVF2
 *   Conversion ID: AW-18182220789
 *   Label:         86RgCI2dht4cEPXX-t1D   (same label currently used by Snippet #7)
 */

'use strict';

const { google } = require('googleapis');

// ── Constants ────────────────────────────────────────────────────────────────

const ACCOUNT_ID    = process.env.GTM_ACCOUNT_ID    || '6362896218';
const CONTAINER_ID  = process.env.GTM_CONTAINER_ID  || '256599823';
const CONVERSION_ID = 'AW-18182220789';
const NUMERIC_CONVERSION_ID = CONVERSION_ID.replace(/^AW-/, '');
const CONV_LABEL    = '86RgCI2dht4cEPXX-t1D';

const TRIGGER_NAME  = 'CE - nvx_conversion_signal - generate_lead';
const TAG_NAME      = 'Google Ads - Formulario Valoración - nvx_signal';
const VERSION_NAME  = 'v4 - Canonical generate_lead via nvx_conversion_signal';
const VERSION_NOTES = [
  'Sets up CE - nvx_conversion_signal custom event trigger for generate_lead.',
  'Fires Google Ads conversion tag with canonical ID AW-18182220789 / label 86RgCI2dht4cEPXX-t1D.',
  'Replaces redundant inline GTM injection from WP Code Snippet #7.',
  'Deploys full consent-mode v2 and conversion linker support.',
].join('\n');

// ── Auth ─────────────────────────────────────────────────────────────────────

async function buildAuth() {
  const clientId     = process.env.GTM_CLIENT_ID     || process.env.GOOGLE_ADS_CLIENT_ID;
  const clientSecret = process.env.GTM_CLIENT_SECRET || process.env.GOOGLE_ADS_CLIENT_SECRET;
  const refreshToken = process.env.GTM_REFRESH_TOKEN;

  if (refreshToken) {
    console.log('  Using provided GTM_REFRESH_TOKEN...');
    const oauth2 = new google.auth.OAuth2(clientId, clientSecret);
    oauth2.setCredentials({ refresh_token: refreshToken });
    return oauth2;
  }
  
  console.log('  No GTM_REFRESH_TOKEN found. Falling back to Application Default Credentials...');
  console.log('  (Run: gcloud auth application-default login --scopes=https://www.googleapis.com/auth/tagmanager.edit.containers,https://www.googleapis.com/auth/tagmanager.publish)');
  const auth = new google.auth.GoogleAuth({
    scopes: [
      'https://www.googleapis.com/auth/tagmanager.edit.containers',
      'https://www.googleapis.com/auth/tagmanager.publish'
    ]
  });
  return await auth.getClient();
}

// ── API helpers ───────────────────────────────────────────────────────────────

async function resolveOrCreateWorkspace(tagmanager, containerPath) {
  const res = await tagmanager.accounts.containers.workspaces.list({ parent: containerPath });
  const workspaces = res.data.workspace || [];
  const dedicatedName = 'NVX Conversion Signal Setup';
  let ws = workspaces.find(w => w.name === dedicatedName);
  if (ws) {
    console.log(`  Found existing dedicated workspace: "${ws.name}" (${ws.workspaceId})`);
    return ws;
  }

  try {
    const createRes = await tagmanager.accounts.containers.workspaces.create({
      parent: containerPath,
      requestBody: {
        name: dedicatedName,
        description: 'Isolated workspace for canonical nvx_conversion_signal tag and trigger'
      }
    });
    ws = createRes.data;
    console.log(`  Created isolated workspace: "${ws.name}" (${ws.workspaceId})`);
    return ws;
  } catch (err) {
    const status = err.code || err.response?.status;
    if (status === 400 || status === 409 || err.message?.includes('limit') || err.message?.includes('quota') || err.message?.includes('workspace')) {
      console.log('  Could not create dedicated workspace (workspace limit or conflict). Using Default Workspace...');
    } else {
      throw err;
    }

    ws = workspaces.find(w => w.name === 'Default Workspace') || workspaces[0];
    if (!ws) throw new Error('No workspace found in container ' + containerPath);

    const statusRes = await tagmanager.accounts.containers.workspaces.getStatus({ path: ws.path });
    const changes = statusRes.data.workspaceChange || [];
    const nonOurs = changes.filter(c => {
      const entityName = c.tag?.name || c.trigger?.name || c.variable?.name || '';
      return entityName !== TRIGGER_NAME && entityName !== TAG_NAME && entityName !== VARIABLE_NAME;
    });
    if (nonOurs.length > 0) {
      throw new Error(`Default workspace contains ${nonOurs.length} uncommitted external change(s). Please commit or discard them in GTM before running automated publish.`);
    }
    return ws;
  }
}

const VARIABLE_NAME = 'nvx_event_name';

async function listTriggers(tagmanager, wsPath) {
  const res = await tagmanager.accounts.containers.workspaces.triggers.list({ parent: wsPath });
  return res.data.trigger || [];
}

async function listTags(tagmanager, wsPath) {
  const res = await tagmanager.accounts.containers.workspaces.tags.list({ parent: wsPath });
  return res.data.tag || [];
}

async function listVariables(tagmanager, wsPath) {
  const res = await tagmanager.accounts.containers.workspaces.variables.list({ parent: wsPath });
  return res.data.variable || [];
}

async function ensureDataLayerVariable(tagmanager, wsPath) {
  const variables = await listVariables(tagmanager, wsPath);
  let v = variables.find(varItem => varItem.name === VARIABLE_NAME);
  if (v) {
    console.log(`  Data Layer Variable already exists: "${VARIABLE_NAME}" (ID: ${v.variableId})`);
    return v;
  }
  console.log(`  Creating Data Layer variable: "${VARIABLE_NAME}"...`);
  const res = await tagmanager.accounts.containers.workspaces.variables.create({
    parent: wsPath,
    requestBody: {
      name: VARIABLE_NAME,
      type: 'v',
      parameter: [
        { type: 'integer', key: 'dataLayerVersion', value: '2' },
        { type: 'template', key: 'name', value: 'nvx_event_name' }
      ]
    }
  });
  v = res.data;
  console.log(`  Created variable ID: ${v.variableId}`);
  return v;
}

// ── Main ─────────────────────────────────────────────────────────────────────

async function main() {
  console.log('\n=== NUVANX GTM Conversion Trigger Setup ===\n');

  const auth       = await buildAuth();
  const tagmanager = google.tagmanager({ version: 'v2', auth });

  const accountPath   = `accounts/${ACCOUNT_ID}`;
  const containerPath = `${accountPath}/containers/${CONTAINER_ID}`;

  // 1. Get workspace
  console.log('1. Resolving workspace...');
  const workspace     = await resolveOrCreateWorkspace(tagmanager, containerPath);
  const workspacePath = workspace.path;

  // 2. Ensure Data Layer variable exists
  console.log('\n2. Ensuring Data Layer variable...');
  await ensureDataLayerVariable(tagmanager, workspacePath);

  // 3. Check / create trigger
  console.log('\n3. Checking for existing trigger...');
  const triggers = await listTriggers(tagmanager, workspacePath);
  let trigger = triggers.find(t => t.name === TRIGGER_NAME);

  if (trigger) {
    console.log(`  Updating existing trigger: "${TRIGGER_NAME}" (ID: ${trigger.triggerId})...`);
    const res = await tagmanager.accounts.containers.workspaces.triggers.update({
      path: trigger.path,
      requestBody: {
        name: TRIGGER_NAME,
        type: 'customEvent',
        customEventFilter: [
          {
            type: 'equals',
            parameter: [
              { type: 'template', key: 'arg0', value: '{{_event}}' },
              { type: 'template', key: 'arg1', value: 'nvx_conversion_signal' },
            ],
          },
        ],
        filter: [
          {
            type: 'equals',
            parameter: [
              { type: 'template', key: 'arg0', value: '{{nvx_event_name}}' },
              { type: 'template', key: 'arg1', value: 'generate_lead' },
            ],
          },
        ],
      },
    });
    trigger = res.data;
    console.log(`  Trigger reconciled (ID: ${trigger.triggerId})`);
  } else {
    console.log(`  Creating trigger: "${TRIGGER_NAME}"...`);
    const res = await tagmanager.accounts.containers.workspaces.triggers.create({
      parent: workspacePath,
      requestBody: {
        name: TRIGGER_NAME,
        type: 'customEvent',
        customEventFilter: [
          {
            type: 'equals',
            parameter: [
              { type: 'template', key: 'arg0', value: '{{_event}}' },
              { type: 'template', key: 'arg1', value: 'nvx_conversion_signal' },
            ],
          },
        ],
        filter: [
          {
            type: 'equals',
            parameter: [
              { type: 'template', key: 'arg0', value: '{{nvx_event_name}}' },
              { type: 'template', key: 'arg1', value: 'generate_lead' },
            ],
          },
        ],
      },
    });
    trigger = res.data;
    console.log(`  Created trigger ID: ${trigger.triggerId}`);
  }

  // 4. Check / create or update Google Ads conversion tag
  console.log('\n4. Checking for existing tag...');
  const tags = await listTags(tagmanager, workspacePath);
  let tag = tags.find(t => t.name === TAG_NAME);

  if (tag) {
    console.log(`  Updating existing tag: "${TAG_NAME}" (ID: ${tag.tagId})...`);
    const res = await tagmanager.accounts.containers.workspaces.tags.update({
      path: tag.path,
      requestBody: {
        name: TAG_NAME,
        type: 'awct',
        parameter: [
          { type: 'template', key: 'conversionId',          value: NUMERIC_CONVERSION_ID },
          { type: 'template', key: 'conversionLabel',       value: CONV_LABEL },
          { type: 'boolean',  key: 'enableRemarketing',     value: 'false' },
          { type: 'boolean',  key: 'enableConversionLinker', value: 'true' },
        ],
        firingTriggerId: [trigger.triggerId],
      },
    });
    tag = res.data;
    console.log(`  Tag reconciled (ID: ${tag.tagId})`);
  } else {
    console.log(`  Creating tag: "${TAG_NAME}"...`);
    const res = await tagmanager.accounts.containers.workspaces.tags.create({
      parent: workspacePath,
      requestBody: {
        name: TAG_NAME,
        type: 'awct',
        parameter: [
          { type: 'template', key: 'conversionId',          value: NUMERIC_CONVERSION_ID },
          { type: 'template', key: 'conversionLabel',       value: CONV_LABEL },
          { type: 'boolean',  key: 'enableRemarketing',     value: 'false' },
          { type: 'boolean',  key: 'enableConversionLinker', value: 'true' },
        ],
        firingTriggerId: [trigger.triggerId],
      },
    });
    tag = res.data;
    console.log(`  Created tag ID: ${tag.tagId}`);
  }

  // 5. Check workspace status before versioning
  console.log('\n5. Checking workspace changes before publishing...');
  const statusRes = await tagmanager.accounts.containers.workspaces.getStatus({ path: workspacePath });
  const pendingChanges = statusRes.data.workspaceChange || [];
  if (pendingChanges.length === 0) {
    console.log('  Workspace has 0 pending changes (tag, trigger and variable are already up to date). Skipping publish.');
    console.log('\n─────────────────────────────────────────────────');
    console.log('Done (idempotent no-op).');
    return;
  }

  console.log(`  Found ${pendingChanges.length} pending change(s). Creating and publishing container version...`);
  const versionRes = await tagmanager.accounts.containers.workspaces.create_version({
    path: workspacePath,
    requestBody: { name: VERSION_NAME, notes: VERSION_NOTES },
  });

  const newVersionId = versionRes.data.containerVersion?.containerVersionId;
  if (!newVersionId) {
    console.log('  No workspace changes to version — container already up to date. Nothing to publish.');
    return;
  }
  const publishRes   = await tagmanager.accounts.containers.versions.publish({
    path: `${containerPath}/versions/${newVersionId}`,
  });

  const published = publishRes.data.containerVersion;
  console.log(`  Published version: ${published?.containerVersionId} — "${published?.name}"`);

  // 5. Summary
  console.log('\n─────────────────────────────────────────────────');
  console.log('Done.');
  console.log(`  Container:  GTM-W55RGVF2 (account ${ACCOUNT_ID})`);
  console.log(`  Version:    ${published?.containerVersionId}`);
  console.log(`  Trigger:    ${TRIGGER_NAME}`);
  console.log(`  Tag:        ${TAG_NAME}`);
  console.log(`  Conversion: ${CONVERSION_ID}/${CONV_LABEL}`);
  console.log('\nNext steps:');
  console.log('  1. Verify via Site Kit that live version = ' + published?.containerVersionId);
  console.log('  2. Deactivate Snippet #7 in WP Code Snippets plugin.');
  console.log('  3. Pause conversion action 4BC2CKSat8YcEPXX-t1D in Google Ads (min 30 days before deleting).');
  console.log('─────────────────────────────────────────────────\n');
}

main().catch(err => {
  const status = err.code || err.response?.status || 'UNKNOWN';
  const msg = err.response?.data?.error?.message || err.message || 'Fatal error';
  console.error(`\n❌ Fatal error [HTTP ${status}]: ${msg}`);
  process.exit(1);
});
