# Security Policy

## SSH Key Rotation Policy

### Staging2 Deployment SSH Keys

**Rotation Schedule:**
- Primary SSH key for staging2 deployment must be rotated every 90 days
- Rotation should be scheduled during low-traffic periods
- Alert via GitHub Issues 7 days before scheduled rotation

**Rotation Process:**
1. Generate new SSH key pair: `ssh-keygen -t ed25519 -a 100 -f staging2_new_key`
2. Add new public key to SiteGround staging2 server
3. Update GitHub Secret `STAGING2_SSH_PRIVATE_KEY` with new private key
4. Update GitHub Secret `STAGING2_SSH_KNOWN_HOSTS` if host key changed
5. Test deployment with new credentials
6. Remove old SSH key from SiteGround server
7. Document key rotation in the private infrastructure password manager / DevOps vault.

**Emergency Rotation:**
- If key compromise is suspected, rotate immediately
- Revolve all related credentials (SiteGround, any services using same key)
- Document incident in SECURITY.md

## Security Incident Log

### July 2026 Incident & Remediation

- **Incident Summary:** Detection of exposed credentials in historical git commits (`refs/pull/*/head` refs).
- **Remediation Actions Taken:**
  1. ⏳ **PENDIENTE** — Rotación de credenciales DB y claves SSH en SiteGround.
  2. ✅ Externalized all production configurations and secrets from codebase tracking.
  3. ✅ Purged sensitive refs from `origin` repository.
  4. ⏳ **PENDIENTE** — Submitted GitHub Support Ticket to purge cached `refs/pull/*/head` objects on GitHub servers.

### Secret Management

**GitHub Secrets (staging2):**
- `STAGING2_SSH_HOST` - SiteGround staging server hostname
- `STAGING2_SSH_PORT` - SSH port (default: 18765)
- `STAGING2_SSH_USER` - SSH username
- `STAGING2_SSH_PRIVATE_KEY` - Private SSH key for deployment
- `STAGING2_SSH_KNOWN_HOSTS` - Known hosts fingerprint

**Application Secrets:**
- HubSpot Form ID and Portal ID stored in WordPress constants
- Clinic contact/WhatsApp numbers centrally defined in `inc/nvx-business-config.php` and normalized by `inc/nvx-config-helpers.php`
- Medical colegiado numbers externalized to `inc/data/config.json`

**Local Development:**
- Never commit `wp-config.php` with real database credentials
- Use environment variables for local development secrets
- Add `wp-config.php` to `.gitignore` if it contains sensitive data

### Dependency Security

**PHP Dependencies:**
- Run `composer audit` regularly to check for vulnerable packages
- Update dependencies monthly: `composer update`
- Review security advisories for WordPress plugins used

**Node.js Dependencies:**
- Run `npm audit` regularly
- Fix critical vulnerabilities immediately
- Keep Playwright and testing dependencies updated

### Code Security Practices

**Input Validation:**
- All user input must be escaped using WordPress functions
- Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Never trust $_GET, $_POST, or $_REQUEST directly

**Database Queries:**
- Use WordPress $wpdb prepare() for all database queries
- Never concatenate user input into SQL queries
- Sanitize all data before database insertion

**File Access:**
- Validate all file paths before including files
- Use `is_readable()` and `realpath()` for file operations
- Restrict file access to theme directory only

### Reporting Security Issues

**To report a security vulnerability:**
1. Do not create a public GitHub issue
2. Send details to: security@nuvanx.com
3. Include steps to reproduce the vulnerability
4. Allow 7 days for remediation before public disclosure

**Response Timeline:**
- Initial response within 48 hours
- Fix timeline based on severity (Critical: 48h, High: 7 days, Medium: 14 days)
- Security advisory published after fix deployment

## Monitoring and Logging

**Security Monitoring:**
- Monitor GitHub Actions for failed deployments
- Review error logs for suspicious activity
- Track failed login attempts to admin areas

**Audit Logging:**
- All deployments logged via GitHub Actions
- SSH access logged by SiteGround
- WordPress admin actions logged via security plugins

## Compliance

**Data Protection:**
- GDPR compliance for user data handling
- Patient data handled according to medical data regulations
- Regular privacy policy reviews

**Medical Standards:**
- Colegiado numbers accurately displayed
- Medical claims evidence-based
- Clear disclaimer about individual results variation