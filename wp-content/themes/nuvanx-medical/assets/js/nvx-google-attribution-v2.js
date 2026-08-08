(function () {
	'use strict';

	var config = window.nvxGoogleClickAttribution || {};
	var canonicalFormId = String(config.formId || '').toLowerCase();
	var endpoint = String(config.endpoint || '');
	var clickKeys = ['gclid', 'gbraid', 'wbraid', 'gclsrc'];
	var clickValues = collectClickValues();
	var pendingPayload = null;
	var sent = false;

	if (!canonicalFormId || !endpoint || !hasAttributionIdentifier(clickValues)) return;

	function cleanClickValue(value, maxLength) {
		var normalized = String(value || '').trim();
		if (!normalized || normalized.length > maxLength) return '';
		return /^[A-Za-z0-9._~:+-]+$/.test(normalized) ? normalized : '';
	}

	function collectClickValues() {
		var params = new URLSearchParams(window.location.search || '');
		return {
			gclid: cleanClickValue(params.get('gclid'), 512),
			gbraid: cleanClickValue(params.get('gbraid'), 512),
			wbraid: cleanClickValue(params.get('wbraid'), 512),
			gclsrc: cleanClickValue(params.get('gclsrc'), 128),
		};
	}

	function hasAttributionIdentifier(values) {
		return Boolean(values.gclid || values.gbraid || values.wbraid);
	}

	function hasMarketingConsent() {
		try {
			return typeof window.wp_has_consent === 'function' && window.wp_has_consent('marketing') === true;
		} catch (_error) {
			return false;
		}
	}

	function canonicalLandingUrl() {
		try {
			var current = new URL(window.location.href);
			return current.origin + current.pathname;
		} catch (_error) {
			return '';
		}
	}

	function normalizeEmail(value) {
		return String(value || '').trim().toLowerCase();
	}

	function bytesToHex(buffer) {
		return Array.from(new Uint8Array(buffer))
			.map(function (byte) { return byte.toString(16).padStart(2, '0'); })
			.join('');
	}

	async function sha256(value) {
		if (!window.crypto || !window.crypto.subtle || typeof TextEncoder === 'undefined') return '';
		var digest = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(value));
		return bytesToHex(digest);
	}

	function fieldValue(fields, propertyName) {
		var names = [propertyName, '0-1/' + propertyName];
		for (var index = 0; index < fields.length; index += 1) {
			var field = fields[index] || {};
			if (names.indexOf(String(field.name || '')) === -1) continue;
			if (Array.isArray(field.value)) return String(field.value[0] || '');
			return String(field.value || '');
		}
		return '';
	}

	async function buildPayload(event) {
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getFormFromEvent !== 'function') return null;

		var form;
		try {
			form = window.HubSpotFormsV4.getFormFromEvent(event);
		} catch (_error) {
			return null;
		}
		if (!form || typeof form.getFormFieldValues !== 'function') return null;

		var fields;
		try {
			fields = await form.getFormFieldValues();
		} catch (_error) {
			return null;
		}
		if (!Array.isArray(fields)) return null;

		var email = normalizeEmail(fieldValue(fields, 'email'));
		if (!email || email.length > 320 || email.indexOf('@') <= 0) return null;

		var emailHash = await sha256(email);
		if (!/^[0-9a-f]{64}$/.test(emailHash)) return null;

		return {
			email_hash: emailHash,
			gclid: clickValues.gclid || null,
			gbraid: clickValues.gbraid || null,
			wbraid: clickValues.wbraid || null,
			gclsrc: clickValues.gclsrc || null,
			form_id: canonicalFormId,
			landing_url: canonicalLandingUrl(),
		};
	}

	async function transmit(payload) {
		if (sent || !payload || !hasMarketingConsent()) return;

		try {
			var response = await window.fetch(endpoint, {
				method: 'POST',
				mode: 'cors',
				credentials: 'omit',
				cache: 'no-store',
				referrerPolicy: 'strict-origin-when-cross-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
				keepalive: true,
			});
			if (response.ok) {
				sent = true;
				pendingPayload = null;
			}
		} catch (_error) {
			// Attribution failure must never interfere with the patient form flow.
		}
	}

	async function handleSuccessfulSubmission(event) {
		if (sent) return;
		var detail = event && event.detail ? event.detail : {};
		if (String(detail.formId || '').toLowerCase() !== canonicalFormId) return;

		var payload = await buildPayload(event);
		if (!payload) return;

		pendingPayload = payload;
		if (hasMarketingConsent()) await transmit(payload);
	}

	function handleConsentChange(event) {
		if (sent || !pendingPayload) return;
		var changed = event && event.detail ? event.detail : {};
		if (changed.marketing !== 'allow') return;
		transmit(pendingPayload);
	}

	window.addEventListener('hs-form-event:on-submission:success', handleSuccessfulSubmission);
	document.addEventListener('wp_listen_for_consent_change', handleConsentChange);
	document.addEventListener('wp_consent_type_defined', function () {
		if (pendingPayload && hasMarketingConsent()) transmit(pendingPayload);
	});

	// Deliberately expose only non-sensitive QA state. No email or hashes are exposed.
	window.NUVANXGoogleAttributionQA = Object.freeze({
		hasClickId: hasAttributionIdentifier(clickValues),
		clickTypes: clickKeys.filter(function (key) { return Boolean(clickValues[key]); }),
		marketingConsent: hasMarketingConsent,
	});
}());
