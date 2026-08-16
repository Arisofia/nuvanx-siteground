const DEPLOY_TIMESTAMP_PATTERN = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/;
const RUN_ID_PATTERN = /^\d+$/;
const RELEASE_ID_PATTERN = /^[A-Za-z0-9_-]+$/;

export function validDeployTimestamp(value) {
  if (!DEPLOY_TIMESTAMP_PATTERN.test(value)) return false;
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return false;
  return parsed.toISOString() === value.replace(/Z$/, '.000Z');
}

export function validGitHubRunId(value) {
  return RUN_ID_PATTERN.test(value);
}

export function validReleaseId(value) {
  return RELEASE_ID_PATTERN.test(value);
}

export function extractMetaContent(html, name) {
  const tags = html.match(/<meta\b[^>]*>/gi) || [];
  for (const tag of tags) {
    const nameMatch = tag.match(/\bname\s*=\s*["']([^"']+)["']/i);
    if (!nameMatch || nameMatch[1].toLowerCase() !== name.toLowerCase()) continue;
    const contentMatch = tag.match(/\bcontent\s*=\s*["']([^"']*)["']/i);
    return contentMatch ? contentMatch[1].trim() : '';
  }
  return '';
}

export function validateDeployIdentity(identity, { expectedSha, expectedRunId = '' } = {}) {
  const issues = [];
  if (!/^[0-9a-f]{40}$/.test(identity.DEPLOY_SHA || '')) {
    issues.push(`DEPLOY_SHA missing or invalid: ${identity.DEPLOY_SHA || '(missing)'}`);
  } else if (expectedSha && identity.DEPLOY_SHA !== expectedSha) {
    issues.push(`DEPLOY_SHA mismatch: expected ${expectedSha}, found ${identity.DEPLOY_SHA}`);
  }

  if (!validGitHubRunId(identity.DEPLOY_RUN_ID || '')) {
    issues.push(`DEPLOY_RUN_ID missing or non-numeric: ${identity.DEPLOY_RUN_ID || '(missing)'}`);
  } else if (expectedRunId && identity.DEPLOY_RUN_ID !== expectedRunId) {
    issues.push(`DEPLOY_RUN_ID mismatch: expected ${expectedRunId}, found ${identity.DEPLOY_RUN_ID}`);
  }

  if (!validDeployTimestamp(identity.DEPLOY_TIMESTAMP || '')) {
    issues.push(`DEPLOY_TIMESTAMP must be UTC whole-second YYYY-MM-DDTHH:mm:ssZ: ${identity.DEPLOY_TIMESTAMP || '(missing)'}`);
  }

  if (!validReleaseId(identity.RELEASE_ID || '')) {
    issues.push(`RELEASE_ID missing or invalid: ${identity.RELEASE_ID || '(missing)'}`);
  }
  return issues;
}
