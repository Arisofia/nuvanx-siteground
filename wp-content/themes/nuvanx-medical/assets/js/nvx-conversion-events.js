(function () {
	'use strict';

	var config = window.nvxConversionEvents || {};
	var signalName = 'nvx_conversion_signal';
	var submissionWindowMs = 8000;
	var recentSubmissions = new Map();

	/**
	 * Normalizes a value for use as a tracking token.
	 * @param {*} value - The value to normalize.
	 * @param {string} fallback - The value to use when normalization produces an empty token.
	 * @return {string} The lowercase normalized token, fallback, or `unknown`.
	 */
	function cleanToken(value, fallback) {
		var token = String(value || '')
			.toLowerCase()
			.replace(/[^a-z0-9_-]+/g, '_');
		while (token.startsWith('_')) token = token.slice(1);
		while (token.endsWith('_')) token = token.slice(0, -1);
		token = token.slice(0, 80);
		return token || fallback || 'unknown';
	}

	function pagePath() {
		return window.location && window.location.pathname ? window.location.pathname : '/';
	}

	function regionFor(element) {
		if (!element || typeof element.closest !== 'function') return 'document';
		if (element.closest('[role="dialog"], .nvx-modal, .nvx-valoracion-modal')) return 'modal';
		if (element.closest('header, .site-header, .nvx-header')) return 'header';
		if (element.closest('footer, .site-footer, .nvx-footer')) return 'footer';
		if (element.closest('.nvx-brand-hero, .nvx-page-hero, [class*="hero"]')) return 'hero';
		if (element.closest('nav')) return 'navigation';
		return 'content';
	}

	function formContext(formId) {
		var normalized = String(formId || '').toLowerCase();
		var forms = config.forms || {};
		if (normalized && normalized === String(forms.valoracion || '').toLowerCase()) return 'valoracion';
		if (pagePath().indexOf('/madrid/valoracion/') === 0) return 'valoracion';
		return 'embedded_form';
	}

	function allowedParameters(parameters) {
		var output = {
			page_path: pagePath(),
			event_source: 'nuvanx_theme',
		};
		Object.keys(parameters || {}).forEach(function (key) {
			var value = parameters[key];
			if (value === undefined || value === null || value === '') return;
			output[cleanToken(key)] = typeof value === 'number' ? value : cleanToken(value);
		});
		return output;
	}

	function emit(eventName, parameters) {
		var normalizedName = cleanToken(eventName);
		var params = allowedParameters(parameters);

		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push(Object.assign({
			event: signalName,
			nvx_event_name: normalizedName,
		}, params));

		window.gtag = window.gtag || function () {
			window.dataLayer.push(arguments);
		};
		window.gtag('event', normalizedName, params);

		document.dispatchEvent(new CustomEvent('nvx:conversion-event', {
			detail: Object.assign({ event_name: normalizedName }, params),
		}));
	}

	function trackClick(event) {
		var target = event.target && typeof event.target.closest === 'function'
			? event.target.closest('a, button')
			: null;
		if (!target) return;

		var href = target.getAttribute('href') || '';
		var dataEvent = target.dataset.gtag || '';
		var common = {
			cta_region: regionFor(target),
			cta_marker: dataEvent || 'selector',
		};

		if (
			target.matches('[data-gtag="click-reserve"], .nvx-open-valoracion-modal')
			|| href.indexOf('/madrid/valoracion/') !== -1
		) {
			emit('reserve_click', Object.assign({ contact_method: 'reservation' }, common));
			return;
		}

		if (
			target.matches('[data-gtag="click-whatsapp"]')
			|| /(?:wa\.me|api\.whatsapp\.com|web\.whatsapp\.com)/i.test(href)
		) {
			emit('whatsapp_click', Object.assign({ contact_method: 'whatsapp' }, common));
			return;
		}

		if (/^tel:/i.test(href)) {
			emit('phone_click', {
				contact_method: 'phone',
				cta_region: regionFor(target),
				cta_marker: dataEvent || 'tel_link',
			});
		}
	}

	function submissionKey(formId) {
		return cleanToken(formId, 'unknown_form') + '|' + pagePath();
	}

	function trackSuccessfulSubmission(formId, eventSource) {
		var key = submissionKey(formId);
		var now = Date.now();
		var previous = recentSubmissions.get(key) || 0;
		if (now - previous < submissionWindowMs) return;
		recentSubmissions.set(key, now);

		emit('generate_lead', {
			form_id: formId || 'unknown_form',
			form_context: formContext(formId),
			lead_source: 'hubspot_form',
			form_event_source: eventSource,
		});
	}

	function isAllowedHubSpotOrigin(origin) {
		if (!origin || origin === 'null') return false;
		try {
			var host = new URL(origin).hostname.toLowerCase();
			return /(^|\.)(hubspot\.com|hsforms\.com|hsforms\.net)$/.test(host);
		} catch (_error) {
			return false;
		}
	}

	document.addEventListener('click', trackClick, true);

	window.addEventListener('hs-form-event:on-submission:success', function (event) {
		var detail = event && event.detail ? event.detail : {};
		trackSuccessfulSubmission(detail.formId || '', 'hubspot_form_event');
	});

	window.addEventListener('message', function (event) {
		if (!isAllowedHubSpotOrigin(event.origin)) return;
		var data = event.data || {};
		if (data.type !== 'hsFormCallback' || data.eventName !== 'onFormSubmitted') return;
		trackSuccessfulSubmission(data.id || '', 'hubspot_post_message');
	});

	window.NUVANXConversionEvents = Object.freeze({
		emit: emit,
		trackSuccessfulSubmission: trackSuccessfulSubmission,
	});
}());

(function () {
	'use strict';

	var attributionConfig = window.nvxConversionEvents || {};
	var forms = attributionConfig.forms || {};
	var FORM_ID = String(forms.valoracion || '5042522a-0bc5-4381-ac3e-5aee8649b69c').toLowerCase();
	var configEndpoint = String(attributionConfig.googleAttributionEndpoint || '');
	var ENDPOINT = /^https:\/\/[a-z0-9]+\.supabase\.co\/functions\/v1\//.test(configEndpoint)
		? configEndpoint
		: 'https://ssvvuuysgxyqvmovrlvk.supabase.co/functions/v1/google-click-attribution';
	var normalizedPath = String(window.location.pathname || '/').replace(/\/+$/, '') || '/';
	var eligiblePath = normalizedPath === '/madrid/valoracion';
	var sent = false;
	var inFlight = false;
	var clickValues = collectClickValues();
	var FIELD_MAP = {
		gclid: ['nvx_google_click_id', 'hs_google_click_id'],
		gbraid: ['nvx_google_braid'],
		wbraid: ['nvx_google_wbraid'],
		gclsrc: ['nvx_google_gclsrc'],
	};

	function cleanClickValue(value, maxLength) {
		var normalized = String(value || '').trim();
		if (!normalized || normalized.length > maxLength) return '';
		return /^[A-Za-z0-9._~:+*%/=\-]+$/.test(normalized) ? normalized : '';
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

	function hasGoogleClickIdentifier(values) {
		return Boolean(values && (values.gclid || values.gbraid || values.wbraid));
	}

	function hasMarketingConsent() {
		try {
			return typeof window.wp_has_consent === 'function' && window.wp_has_consent('marketing') === true;
		} catch (_error) {
			return false;
		}
	}

	window.NUVANXGoogleAttributionQA = Object.freeze({
		eligiblePath: eligiblePath,
		hasClickId: hasGoogleClickIdentifier(clickValues),
		clickTypes: ['gclid', 'gbraid', 'wbraid'].filter(function (key) { return Boolean(clickValues[key]); }),
		marketingConsent: hasMarketingConsent,
	});

	if (!eligiblePath || !hasGoogleClickIdentifier(clickValues)) return;

	function isCanonicalForm(form) {
		if (!form || typeof form.getFormId !== 'function') return false;
		try {
			return String(form.getFormId() || '').toLowerCase() === FORM_ID;
		} catch (_error) {
			return false;
		}
	}

	function fieldCandidates(propertyName) {
		return ['0-1/' + propertyName, propertyName];
	}

	async function populateHubSpotClickFields(form) {
		if (!isCanonicalForm(form)) return false;
		if (typeof form.getFormFieldValues !== 'function' || typeof form.setFieldValue !== 'function') return false;

		var consent = hasMarketingConsent();
		var fields;
		try {
			fields = await form.getFormFieldValues();
		} catch (_error) {
			return false;
		}
		if (!Array.isArray(fields)) return false;

		var availableNames = new Set(fields.map(function (field) {
			return field && typeof field.name === 'string' ? field.name : '';
		}).filter(Boolean));
		var modified = false;

		Object.keys(FIELD_MAP).forEach(function (param) {
			var value = consent ? clickValues[param] : '';
			if (!value && consent) return;
			FIELD_MAP[param].forEach(function (propertyName) {
				// Privacy fail-closed: clear mapped nvx_ variables, but do not overwrite native HubSpot fields when consent is denied
				if (!consent && propertyName.indexOf('nvx_') !== 0) return;
				
				fieldCandidates(propertyName).forEach(function (fieldName) {
					if (!availableNames.has(fieldName)) return;
					try {
						form.setFieldValue(fieldName, value); // HubSpot v4 expects scalar for text inputs
						modified = true;
					} catch (_error) {}
				});
			});
		});

		return modified;
	}

	function populateExistingForms() {
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getForms !== 'function') return;
		try {
			(window.HubSpotFormsV4.getForms() || []).forEach(function (form) {
				populateHubSpotClickFields(form);
			});
		} catch (_error) {}
	}

	window.addEventListener('hs-form-event:on-ready', function (event) {
		var detail = event && event.detail ? event.detail : {};
		if (String(detail.formId || '').toLowerCase() !== FORM_ID) return;
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getFormFromEvent !== 'function') return;
		try {
			populateHubSpotClickFields(window.HubSpotFormsV4.getFormFromEvent(event));
		} catch (_error) {}
	});

	document.addEventListener('wp_listen_for_consent_change', populateExistingForms);
	document.addEventListener('wp_consent_type_defined', populateExistingForms);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', populateExistingForms, { once: true });
	} else {
		populateExistingForms();
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
		try {
			var digest = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(value));
			return bytesToHex(digest);
		} catch (_error) {
			return '';
		}
	}

	function getFieldValue(fields, propertyName) {
		var names = fieldCandidates(propertyName);
		for (var index = 0; index < fields.length; index += 1) {
			var field = fields[index] || {};
			if (names.indexOf(String(field.name || '')) === -1) continue;
			if (Array.isArray(field.value)) return String(field.value[0] || '');
			return String(field.value || '');
		}
		return '';
	}

	async function buildAuditPayload(event) {
		var fields = null;
		var detail = event && event.detail ? event.detail : {};
		
		if (Array.isArray(detail.data)) {
			fields = detail.data;
		} else if (Array.isArray(detail.submissionValues)) {
			fields = detail.submissionValues;
		}

		if (!fields) {
			if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getFormFromEvent !== 'function') return null;
			var form;
			try {
				form = window.HubSpotFormsV4.getFormFromEvent(event);
			} catch (_error) {
				return null;
			}
			if (!isCanonicalForm(form) || typeof form.getFormFieldValues !== 'function') return null;
			try {
				fields = await form.getFormFieldValues();
			} catch (_error) {
				return null;
			}
		}
		if (!Array.isArray(fields) || !hasMarketingConsent()) return null;

		var email = normalizeEmail(getFieldValue(fields, 'email'));
		if (!email || email.length > 320 || email.indexOf('@') <= 0) return null;

		var emailHash = await sha256(email);
		if (!/^[0-9a-f]{64}$/.test(emailHash) || !hasMarketingConsent()) return null;

		return {
			email_hash: emailHash,
			gclid: clickValues.gclid || null,
			gbraid: clickValues.gbraid || null,
			wbraid: clickValues.wbraid || null,
			gclsrc: clickValues.gclsrc || null,
			form_id: FORM_ID,
			landing_url: canonicalLandingUrl(),
		};
	}

	async function transmitAudit(payload) {
		if (sent || inFlight || !payload || !hasMarketingConsent()) return;
		inFlight = true;
		try {
			var response = await window.fetch(ENDPOINT, {
				method: 'POST',
				mode: 'cors',
				credentials: 'omit',
				cache: 'no-store',
				referrerPolicy: 'strict-origin-when-cross-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
				keepalive: true,
			});
			// Treat client errors (4xx) as terminal to prevent silent retry loops
			if (response.ok || (response.status >= 400 && response.status < 500 && response.status !== 429)) {
				sent = true;
			}
		} catch (_error) {
			// Attribution audit failure must never interfere with the patient form flow.
		} finally {
			inFlight = false;
		}
	}

	window.addEventListener('hs-form-event:on-submission:success', async function (event) {
		if (sent || inFlight || !hasMarketingConsent()) return;
		var detail = event && event.detail ? event.detail : {};
		if (String(detail.formId || '').toLowerCase() !== FORM_ID) return;

		// Privacy fail-closed: no email access or hashing occurs without marketing consent.
		var payload = await buildAuditPayload(event);
		if (!payload || !hasMarketingConsent()) return;
		transmitAudit(payload); // Dispatched without awaiting to reduce window before navigation
	});
}());
