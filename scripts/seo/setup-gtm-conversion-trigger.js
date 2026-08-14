#!/usr/bin/env node
/**
 * setup-gtm-conversion-trigger.js
 *
 * Private local manual publisher for the canonical
 * nvx_conversion_signal -> generate_lead Google Ads conversion in GTM.
 *
 * Safety contract:
 * - refuses CI/non-TTY execution;
 * - requires GTM_CONFIRM_PUBLISH=yes;
 * - requires all target IDs through environment variables (no live defaults);
 * - uses only an isolated dedicated workspace and never falls back to Default Workspace;
 * - syncs a reused dedicated workspace before any mutation;
 * - refuses any pre-existing pending workspace changes;
 * - verifies existing variable/trigger/tag contracts without replacing them;
 * - publishes only changes whose entity IDs were created by this invocation.
 *
 * Usage:
 *   source .env.local
 *   GTM_CONFIRM_PUBLISH=yes node scripts/seo/setup-gtm-conversion-trigger.js
 *
 * Required environment variables:
 *   GTM_REFRESH_TOKEN
 *   GTM_CLIENT_ID (or GOOGLE_ADS_CLIENT_ID)
 *   GTM_CLIENT_SECRET (or GOOGLE_ADS_CLIENT_SECRET)
 *   GTM_ACCOUNT_ID
 *   GTM_CONTAINER_ID
 *   GOOGLE_ADS_CONVERSION_ID     (AW-XXXXXXXXXXX)
 *   GOOGLE_ADS_CONVERSION_LABEL
 */

'use strict';

const { google } = require('googleapis');

const TRIGGER_NAME = 'CE - nvx_conversion_signal - generate_lead';
const TAG_NAME = 'Google Ads - Formulario Valoración - nvx_signal';
const VARIABLE_NAME = 'nvx_event_name';
const WORKSPACE_NAME = 'NVX Conversion Signal Setup';

function safeFail(message) {
  const error = new Error(message);
  error.nvxSafe = true;
  throw error;
}

function cleanToken(value, fallback = 'UNKNOWN') {
  const token = String(value || '').replace(/[^a-zA-Z0-9_.-]/g, '').slice(0, 80);
  return token || fallback;
}

function sanitizeGtmError(err) {
  const status = cleanToken(err?.code || err?.response?.status, 'UNKNOWN');
  const reason = cleanToken(
    err?.response?.data?.error?.status || err?.errors?.[0]?.reason || err?.name,
    'GTM_API_ERROR'
  );
  return `status=${status} reason=${reason}`;
}

function requireManualContext() {
  if (
    process.env.CI === 'true'
    || process.env.GITHUB_ACTIONS === 'true'
    || !process.stdin.isTTY
    || !process.stdout.isTTY
  ) {
    console.error('GTM_SETUP=REFUSED: this live GTM publisher may only run in a private local TTY.');
    process.exit(2);
  }
  if (process.env.GTM_CONFIRM_PUBLISH !== 'yes') {
    console.error('GTM_SETUP=REFUSED: set GTM_CONFIRM_PUBLISH=yes for an intentional live GTM mutation/publish.');
    process.exit(2);
  }
}

function requiredEnv(name) {
  const value = String(process.env[name] || '').trim();
  if (!value) safeFail(`Missing required environment variable: ${name}`);
  return value;
}

function loadConfiguration() {
  const accountId = requiredEnv('GTM_ACCOUNT_ID');
  const containerId = requiredEnv('GTM_CONTAINER_ID');
  const conversionId = requiredEnv('GOOGLE_ADS_CONVERSION_ID');
  const conversionLabel = requiredEnv('GOOGLE_ADS_CONVERSION_LABEL');

  if (!/^AW-\d+$/.test(conversionId)) {
    safeFail('GOOGLE_ADS_CONVERSION_ID must use the AW-XXXXXXXX numeric format.');
  }
  if (!/^[A-Za-z0-9_-]+$/.test(conversionLabel)) {
    safeFail('GOOGLE_ADS_CONVERSION_LABEL contains unsupported characters.');
  }

  return {
    accountId,
    containerId,
    conversionId,
    numericConversionId: conversionId.replace(/^AW-/, ''),
    conversionLabel,
  };
}

async function buildAuth() {
  const clientId = process.env.GTM_CLIENT_ID || process.env.GOOGLE_ADS_CLIENT_ID;
  const clientSecret = process.env.GTM_CLIENT_SECRET || process.env.GOOGLE_ADS_CLIENT_SECRET;
  const refreshToken = process.env.GTM_REFRESH_TOKEN;

  if (!refreshToken || !clientId || !clientSecret) {
    safeFail('GTM_REFRESH_TOKEN plus GTM_CLIENT_ID/GTM_CLIENT_SECRET (or GOOGLE_ADS fallbacks) are required.');
  }

  const oauth2 = new google.auth.OAuth2(clientId, clientSecret);
  oauth2.setCredentials({ refresh_token: refreshToken });
  return oauth2;
}

async function getWorkspaceStatus(tagmanager, path) {
  const res = await tagmanager.accounts.containers.workspaces.getStatus({ path });
  return res.data.workspaceChange || [];
}

async function resolveIsolatedWorkspace(tagmanager, containerPath) {
  const res = await tagmanager.accounts.containers.workspaces.list({ parent: containerPath });
  const workspaces = res.data.workspace || [];
  let workspace = workspaces.find((item) => item.name === WORKSPACE_NAME);

  if (!workspace) {
    try {
      const created = await tagmanager.accounts.containers.workspaces.create({
        parent: containerPath,
        requestBody: {
          name: WORKSPACE_NAME,
          description: 'Isolated workspace for canonical nvx_conversion_signal conversion setup',
        },
      });
      workspace = created.data;
      console.log(`  Created isolated workspace: ${workspace.workspaceId}`);
      return workspace;
    } catch (err) {
      safeFail(`Unable to create isolated GTM workspace (${sanitizeGtmError(err)}). Default Workspace fallback is disabled.`);
    }
  }

  console.log(`  Reusing isolated workspace: ${workspace.workspaceId}`);
  const beforeSync = await getWorkspaceStatus(tagmanager, workspace.path);
  if (beforeSync.length > 0) {
    safeFail(`Dedicated workspace already contains ${beforeSync.length} pending change(s). Resolve them manually before running this publisher.`);
  }

  const syncRes = await tagmanager.accounts.containers.workspaces.sync({ path: workspace.path });
  const syncStatus = syncRes.data.syncStatus || {};
  const conflicts = syncRes.data.mergeConflict || [];
  if (syncStatus.syncError || syncStatus.mergeConflict || conflicts.length > 0) {
    safeFail('Dedicated workspace could not be synchronized cleanly to the latest container version. Resolve GTM conflicts manually.');
  }

  const afterSync = await getWorkspaceStatus(tagmanager, workspace.path);
  if (afterSync.length > 0) {
    safeFail(`Dedicated workspace is not clean after synchronization (${afterSync.length} pending change(s)).`);
  }

  return workspace;
}

async function listTriggers(tagmanager, workspacePath) {
  const res = await tagmanager.accounts.containers.workspaces.triggers.list({ parent: workspacePath });
  return res.data.trigger || [];
}

async function listTags(tagmanager, workspacePath) {
  const res = await tagmanager.accounts.containers.workspaces.tags.list({ parent: workspacePath });
  return res.data.tag || [];
}

async function listVariables(tagmanager, workspacePath) {
  const res = await tagmanager.accounts.containers.workspaces.variables.list({ parent: workspacePath });
  return res.data.variable || [];
}

function parameterMap(parameters) {
  const map = new Map();
  for (const parameter of Array.isArray(parameters) ? parameters : []) {
    if (parameter?.key) map.set(String(parameter.key), String(parameter.value ?? ''));
  }
  return map;
}

function conditionMatches(condition, left, right) {
  if (condition?.type !== 'equals') return false;
  const params = parameterMap(condition.parameter);
  return params.get('arg0') === left && params.get('arg1') === right;
}

function assertVariableContract(variable) {
  const params = parameterMap(variable.parameter);
  if (
    variable.type !== 'v'
    || params.get('name') !== VARIABLE_NAME
    || params.get('dataLayerVersion') !== '2'
  ) {
    safeFail(`Existing GTM variable "${VARIABLE_NAME}" does not match the canonical Data Layer Variable contract. Refusing to overwrite it.`);
  }
}

function assertTriggerContract(trigger) {
  const customConditions = Array.isArray(trigger.customEventFilter) ? trigger.customEventFilter : [];
  const extraConditions = Array.isArray(trigger.filter) ? trigger.filter : [];
  const allConditions = customConditions.concat(extraConditions);
  const eventMatch = allConditions.some((condition) => conditionMatches(condition, '{{_event}}', 'nvx_conversion_signal'));
  const nameMatch = allConditions.some((condition) => conditionMatches(condition, `{{${VARIABLE_NAME}}}`, 'generate_lead'));

  if (trigger.type !== 'customEvent' || !eventMatch || !nameMatch) {
    safeFail(`Existing GTM trigger "${TRIGGER_NAME}" does not match the canonical event/filter contract. Refusing to overwrite it.`);
  }
}

function assertTagContract(tag, triggerId, config) {
  const params = parameterMap(tag.parameter);
  const firing = Array.isArray(tag.firingTriggerId) ? tag.firingTriggerId.map(String) : [];
  if (
    tag.type !== 'awct'
    || params.get('conversionId') !== config.numericConversionId
    || params.get('conversionLabel') !== config.conversionLabel
    || params.get('enableRemarketing') !== 'false'
    || params.get('enableConversionLinker') !== 'true'
    || !firing.includes(String(triggerId))
  ) {
    safeFail(`Existing GTM tag "${TAG_NAME}" does not match the canonical conversion contract. Refusing to overwrite it.`);
  }
}

function variablePayload() {
  return {
    name: VARIABLE_NAME,
    type: 'v',
    parameter: [
      { type: 'template', key: 'name', value: VARIABLE_NAME },
      { type: 'integer', key: 'dataLayerVersion', value: '2' },
    ],
  };
}

function triggerPayload() {
  return {
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
      {
        type: 'equals',
        parameter: [
          { type: 'template', key: 'arg0', value: `{{${VARIABLE_NAME}}}` },
          { type: 'template', key: 'arg1', value: 'generate_lead' },
        ],
      },
    ],
  };
}

function tagPayload(triggerId, config) {
  return {
    name: TAG_NAME,
    type: 'awct',
    parameter: [
      { type: 'template', key: 'conversionId', value: config.numericConversionId },
      { type: 'template', key: 'conversionLabel', value: config.conversionLabel },
      { type: 'boolean', key: 'enableRemarketing', value: 'false' },
      { type: 'boolean', key: 'enableConversionLinker', value: 'true' },
    ],
    firingTriggerId: [String(triggerId)],
  };
}

async function ensureVariable(tagmanager, workspacePath, createdIds) {
  const variables = await listVariables(tagmanager, workspacePath);
  let variable = variables.find((item) => item.name === VARIABLE_NAME);
  if (variable) {
    assertVariableContract(variable);
    console.log(`  Variable verified: ${variable.variableId}`);
    return variable;
  }

  const res = await tagmanager.accounts.containers.workspaces.variables.create({
    parent: workspacePath,
    requestBody: variablePayload(),
  });
  variable = res.data;
  createdIds.variableId = String(variable.variableId);
  console.log(`  Variable created: ${variable.variableId}`);
  return variable;
}

async function ensureTrigger(tagmanager, workspacePath, createdIds) {
  const triggers = await listTriggers(tagmanager, workspacePath);
  let trigger = triggers.find((item) => item.name === TRIGGER_NAME);
  if (trigger) {
    assertTriggerContract(trigger);
    console.log(`  Trigger verified: ${trigger.triggerId}`);
    return trigger;
  }

  const res = await tagmanager.accounts.containers.workspaces.triggers.create({
    parent: workspacePath,
    requestBody: triggerPayload(),
  });
  trigger = res.data;
  createdIds.triggerId = String(trigger.triggerId);
  console.log(`  Trigger created: ${trigger.triggerId}`);
  return trigger;
}

async function ensureTag(tagmanager, workspacePath, trigger, config, createdIds) {
  const tags = await listTags(tagmanager, workspacePath);
  let tag = tags.find((item) => item.name === TAG_NAME);
  if (tag) {
    assertTagContract(tag, trigger.triggerId, config);
    console.log(`  Tag verified: ${tag.tagId}`);
    return tag;
  }

  const res = await tagmanager.accounts.containers.workspaces.tags.create({
    parent: workspacePath,
    requestBody: tagPayload(trigger.triggerId, config),
  });
  tag = res.data;
  createdIds.tagId = String(tag.tagId);
  console.log(`  Tag created: ${tag.tagId}`);
  return tag;
}

function isCreatedByThisRun(change, createdIds) {
  if (change.variable && createdIds.variableId) {
    return String(change.variable.variableId || '') === createdIds.variableId;
  }
  if (change.trigger && createdIds.triggerId) {
    return String(change.trigger.triggerId || '') === createdIds.triggerId;
  }
  if (change.tag && createdIds.tagId) {
    return String(change.tag.tagId || '') === createdIds.tagId;
  }
  return false;
}

async function main() {
  requireManualContext();
  const config = loadConfiguration();

  console.log('\n=== NUVANX GTM Conversion Trigger Setup ===');
  console.log(`Target account: ${config.accountId}`);
  console.log(`Target container: ${config.containerId}`);
  console.log(`Conversion: ${config.conversionId}/${config.conversionLabel}\n`);

  const auth = await buildAuth();
  const tagmanager = google.tagmanager({ version: 'v2', auth });
  const containerPath = `accounts/${config.accountId}/containers/${config.containerId}`;

  console.log('1. Resolving isolated workspace...');
  const workspace = await resolveIsolatedWorkspace(tagmanager, containerPath);
  const workspacePath = workspace.path;

  const initialChanges = await getWorkspaceStatus(tagmanager, workspacePath);
  if (initialChanges.length > 0) {
    safeFail(`Workspace must be clean before mutation; found ${initialChanges.length} pending change(s).`);
  }

  const createdIds = {};

  console.log('\n2. Ensuring Data Layer variable...');
  await ensureVariable(tagmanager, workspacePath, createdIds);

  console.log('\n3. Ensuring custom-event trigger...');
  const trigger = await ensureTrigger(tagmanager, workspacePath, createdIds);

  console.log('\n4. Ensuring Google Ads conversion tag...');
  await ensureTag(tagmanager, workspacePath, trigger, config, createdIds);

  console.log('\n5. Verifying isolated changes before versioning...');
  const pendingChanges = await getWorkspaceStatus(tagmanager, workspacePath);
  if (pendingChanges.length === 0) {
    console.log('  No pending changes. Existing GTM configuration already matches the canonical contract.');
    console.log('GTM_SETUP=PASS mode=verify-only');
    return;
  }

  const unexpected = pendingChanges.filter((change) => !isCreatedByThisRun(change, createdIds));
  if (unexpected.length > 0) {
    safeFail(`Workspace contains ${unexpected.length} pending change(s) not created by this invocation. Refusing to publish.`);
  }

  const versionNameBase = process.env.GTM_VERSION_NAME || 'Canonical generate_lead via nvx_conversion_signal';
  const versionName = `${versionNameBase} (${new Date().toISOString().replace(/T/, ' ').replace(/\..+/, '')} UTC)`;
  const versionNotes = [
    'Creates missing canonical nvx_conversion_signal -> generate_lead GTM entities only.',
    `Google Ads conversion: ${config.conversionId}/${config.conversionLabel}.`,
    'Conversion linker is enabled on the Google Ads conversion tag.',
    'Consent Mode ownership/configuration is unchanged and remains outside this helper.',
  ].join('\n');

  const versionRes = await tagmanager.accounts.containers.workspaces.create_version({
    path: workspacePath,
    requestBody: { name: versionName, notes: versionNotes },
  });

  if (versionRes.data.compilerError) {
    safeFail('GTM reported a compiler error while creating the container version; nothing was published.');
  }
  if (versionRes.data.syncStatus?.syncError || versionRes.data.syncStatus?.mergeConflict) {
    safeFail('GTM reported a synchronization error/conflict while creating the container version; nothing was published.');
  }

  const versionId = versionRes.data.containerVersion?.containerVersionId;
  if (!versionId) {
    safeFail('Container version creation returned no containerVersionId despite pending changes; nothing was published.');
  }

  const publishRes = await tagmanager.accounts.containers.versions.publish({
    path: `${containerPath}/versions/${versionId}`,
  });
  const published = publishRes.data.containerVersion;

  if (!published?.containerVersionId) {
    safeFail('GTM publish returned no live containerVersionId. Verify the container manually.');
  }

  console.log(`\nGTM_SETUP=PASS mode=published version=${published.containerVersionId}`);
  console.log('Next: verify the live container in GTM/Site Kit before disabling any legacy WordPress snippet.');
}

main().catch((err) => {
  if (err?.nvxSafe) {
    console.error(`\nGTM_SETUP=FAIL: ${err.message}`);
  } else {
    console.error(`\nGTM_SETUP=FAIL: ${sanitizeGtmError(err)}`);
  }
  process.exit(1);
});
