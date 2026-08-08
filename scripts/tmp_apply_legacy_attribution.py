from pathlib import Path
import sys

root = Path(sys.argv[1] if len(sys.argv) > 1 else '.')

conv = root / 'wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js'
text = conv.read_text()
anchor = "\twindow.addEventListener('hs-form-event:on-submission:success', async function (event) {\n"
if text.count(anchor) != 1:
    raise SystemExit(f'Expected one modern success-listener anchor, found {text.count(anchor)}')

legacy = r'''\tvar legacyFormRoots = [];
\tvar legacyEmailForAudit = '';

\tfunction legacyFormRoot(formLike) {
\t\tvar root = formLike;
\t\ttry {
\t\t\tif (root && typeof root.get === 'function') root = root.get(0);
\t\t\telse if (root && root.jquery && root[0]) root = root[0];
\t\t\telse if (root && root[0] && root[0].nodeType === 1) root = root[0];
\t\t} catch (_error) {
\t\t\treturn null;
\t\t}
\t\treturn root && typeof root.querySelector === 'function' ? root : null;
\t}

\tfunction setLegacyField(root, propertyName, value) {
\t\tif (!root) return false;
\t\tvar input = root.querySelector('[name="' + propertyName + '"]');
\t\tif (!input) return false;
\t\ttry {
\t\t\tinput.value = value;
\t\t\tif (value) input.setAttribute('value', value);
\t\t\telse input.removeAttribute('value');
\t\t\tinput.dispatchEvent(new Event('input', { bubbles: true }));
\t\t\tinput.dispatchEvent(new Event('change', { bubbles: true }));
\t\t\treturn true;
\t\t} catch (_error) {
\t\t\treturn false;
\t\t}
\t}

\tfunction populateLegacyClickFields(formLike) {
\t\tvar root = legacyFormRoot(formLike);
\t\tif (!root) return false;
\t\tif (legacyFormRoots.indexOf(root) === -1) legacyFormRoots.push(root);

\t\tvar consent = hasMarketingConsent();
\t\tvar modified = false;
\t\tObject.keys(FIELD_MAP).forEach(function (param) {
\t\t\tvar value = consent ? clickValues[param] : '';
\t\t\tif (!value && consent) return;
\t\t\tFIELD_MAP[param].forEach(function (propertyName) {
\t\t\t\tif (!consent && propertyName.indexOf('nvx_') !== 0) return;
\t\t\t\tmodified = setLegacyField(root, propertyName, value) || modified;
\t\t\t});
\t\t});
\t\treturn modified;
\t}

\tfunction refreshLegacyForms() {
\t\tlegacyFormRoots = legacyFormRoots.filter(function (root) {
\t\t\treturn root && root.isConnected;
\t\t});
\t\tlegacyFormRoots.forEach(function (root) {
\t\t\tpopulateLegacyClickFields(root);
\t\t});
\t}

\tfunction captureLegacyEmail(formLike) {
\t\tlegacyEmailForAudit = '';
\t\tpopulateLegacyClickFields(formLike);
\t\tif (!hasMarketingConsent()) return;
\t\tvar root = legacyFormRoot(formLike);
\t\tif (!root) return;
\t\tvar emailInput = root.querySelector('[name="email"]');
\t\tif (!emailInput) return;
\t\tvar email = normalizeEmail(emailInput.value);
\t\tif (!email || email.length > 320 || email.indexOf('@') <= 0) return;
\t\tlegacyEmailForAudit = email;
\t}

\tasync function transmitLegacySuccess() {
\t\tvar email = legacyEmailForAudit;
\t\tlegacyEmailForAudit = '';
\t\tif (!email || !hasMarketingConsent() || sent || inFlight) return;
\t\tvar emailHash = await sha256(email);
\t\tif (!/^[0-9a-f]{64}$/.test(emailHash) || !hasMarketingConsent()) return;
\t\ttransmitAudit({
\t\t\temail_hash: emailHash,
\t\t\tgclid: clickValues.gclid || null,
\t\t\tgbraid: clickValues.gbraid || null,
\t\t\twbraid: clickValues.wbraid || null,
\t\t\tgclsrc: clickValues.gclsrc || null,
\t\t\tform_id: FORM_ID,
\t\t\tlanding_url: canonicalLandingUrl(),
\t\t});
\t}

\tfunction isTrustedHubSpotOrigin(origin) {
\t\tif (!origin || origin === 'null') return false;
\t\ttry {
\t\t\tvar host = new URL(origin).hostname.toLowerCase();
\t\t\treturn /(^|\\.)(hubspot\\.com|hsforms\\.com|hsforms\\.net)$/.test(host);
\t\t} catch (_error) {
\t\t\treturn false;
\t\t}
\t}

\twindow.NUVANXGoogleAttributionLegacy = Object.freeze({
\t\tonFormReady: function (formLike) {
\t\t\tpopulateLegacyClickFields(formLike);
\t\t},
\t\tonBeforeFormSubmit: function (formLike) {
\t\t\tcaptureLegacyEmail(formLike);
\t\t},
\t});

\tdocument.addEventListener('wp_listen_for_consent_change', refreshLegacyForms);
\tdocument.addEventListener('wp_consent_type_defined', refreshLegacyForms);

\twindow.addEventListener('message', function (event) {
\t\tif (!isTrustedHubSpotOrigin(event.origin)) return;
\t\tvar data = event.data || {};
\t\tif (data.type !== 'hsFormCallback' || data.eventName !== 'onFormSubmitted') return;
\t\tif (String(data.id || '').toLowerCase() !== FORM_ID) return;
\t\ttransmitLegacySuccess();
\t});

'''
text = text.replace(anchor, legacy + anchor)
conv.write_text(text)

runtime = root / 'wp-content/themes/nuvanx-medical/assets/js/nvx-runtime-governance.js'
rt = runtime.read_text()
old = """              formId: frame.dataset.formId || config.hubspotFormId,\n              target: '#' + frame.id\n"""
new = """              formId: frame.dataset.formId || config.hubspotFormId,\n              target: '#' + frame.id,\n              onFormReady: function ($form) {\n                try {\n                  const hooks = window.NUVANXGoogleAttributionLegacy;\n                  if (hooks && typeof hooks.onFormReady === 'function') hooks.onFormReady($form);\n                } catch (_error) {}\n              },\n              onBeforeFormSubmit: function ($form) {\n                try {\n                  const hooks = window.NUVANXGoogleAttributionLegacy;\n                  if (hooks && typeof hooks.onBeforeFormSubmit === 'function') hooks.onBeforeFormSubmit($form);\n                } catch (_error) {}\n              }\n"""
if rt.count(old) != 1:
    raise SystemExit(f'Expected one hbspt.forms.create target anchor, found {rt.count(old)}')
runtime.write_text(rt.replace(old, new))
