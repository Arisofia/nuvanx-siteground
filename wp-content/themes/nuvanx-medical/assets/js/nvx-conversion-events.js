(function () {
	'use strict';

	var config = window.nvxConversionEvents || {};
	var signalName = 'nvx_conversion_signal';
	var submissionWindowMs = 8000;
	var recentSubmissions = new Map();

	function cleanToken(value, fallback) {
		var token = String(value || '')
			.toLowerCase()
			.replace(/[^a-z0-9_-]+/g, '_')
			.replace(/^_+|_+$/g, '')
			.slice(0, 80);
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
	var ENDPOINT = String(attributionConfig.googleAttributionEndpoint || 'https://ssvvuuysgxyqvmovrlvk.supabase.co/functions/v1/google-click-attribution');
	var sent = false;
	var inFlight = false;
	var clickValues = { gclid: '', gbraid: '', wbraid: '', gclsrc: '', landing_url: '' };

	function cleanClickValue(value, maxLength) {
		var normalized = String(value || '').trim();
		if (!normalized || normalized.length > maxLength) return '';
		return /^[A-Za-z0-9._~:+\-*%/=]+$/.test(normalized) ? normalized : '';
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

	function persistClickValues() {
		if (!hasMarketingConsent()) {
			try {
				window.sessionStorage.removeItem('nvx_google_click_ids');
			} catch (_error) {}
			return;
		}
		if (!hasGoogleClickIdentifier(clickValues)) return;
		try {
			window.sessionStorage.setItem('nvx_google_click_ids', JSON.stringify(clickValues));
		} catch (_error) {}
	}

	function collectClickValues() {
		try {
			if (!hasMarketingConsent()) {
				window.sessionStorage.removeItem('nvx_google_click_ids');
			}
		} catch (_error) {}

		var params = new URLSearchParams(window.location.search || '');
		var current = {
			gclid: cleanClickValue(params.get('gclid'), 512),
			gbraid: cleanClickValue(params.get('gbraid'), 512),
			wbraid: cleanClickValue(params.get('wbraid'), 512),
			gclsrc: cleanClickValue(params.get('gclsrc'), 128),
			landing_url: canonicalLandingUrl(),
		};

		if (hasGoogleClickIdentifier(current)) {
			clickValues = current;
			persistClickValues();
			return;
		}

		try {
			if (!hasMarketingConsent()) {
				window.sessionStorage.removeItem('nvx_google_click_ids');
				return;
			}
			var stored = window.sessionStorage.getItem('nvx_google_click_ids');
			var parsed = stored ? JSON.parse(stored) : null;
			if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
				clickValues = {
				clickValues = {
					gclid: cleanClickValue(parsed.gclid, 512),
					gbraid: cleanClickValue(parsed.gbraid, 512),
					wbraid: cleanClickValue(parsed.wbraid, 512),
					gclsrc: cleanClickValue(parsed.gclsrc, 128),
					landing_url: typeof parsed.landing_url === 'string' ? parsed.landing_url : '',
				};
			}
		} catch (_error) {}
	}

	collectClickValues();

	window.NUVANXGoogleAttributionQA = Object.freeze({
		get hasClickId() { return hasGoogleClickIdentifier(clickValues); },
		get clickTypes() { return ['gclid', 'gbraid', 'wbraid'].filter(function (key) { return Boolean(clickValues[key]); }); },
		get marketingConsent() { return hasMarketingConsent(); }
	});

	if (!hasGoogleClickIdentifier(clickValues)) return;
	
	document.addEventListener('wp_listen_for_consent_change', persistClickValues);
	document.addEventListener('wp_consent_type_defined', persistClickValues);

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

	function getFieldValue(fields, propertyName) {
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
			if (!form || typeof form.getFormFieldValues !== 'function') return null;

			try {
				fields = await form.getFormFieldValues();
			} catch (_error) {
				return null;
			}
		}
		if (!Array.isArray(fields)) return null;

		var email = normalizeEmail(getFieldValue(fields, 'email'));
		if (!email || email.length > 320 || email.indexOf('@') <= 0) return null;

		var emailHash = await sha256(email);
		if (!/^[0-9a-f]{64}$/.test(emailHash)) return null;

		return {
			email_hash: emailHash,
			gclid: clickValues.gclid || null,
			gbraid: clickValues.gbraid || null,
			wbraid: clickValues.wbraid || null,
			gclsrc: clickValues.gclsrc || null,
			form_id: FORM_ID,
			landing_url: clickValues.landing_url || canonicalLandingUrl(),
		};
	}

	async function transmit(payload) {
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
			if (response.ok) sent = true;
		} catch (_error) {
			// Attribution failure must never interfere with the patient form flow.
		} finally {
			inFlight = false;
		}
	}

	async function handleSuccessfulSubmission(event) {
		if (sent || inFlight || !hasMarketingConsent()) return;
		var detail = event && event.detail ? event.detail : {};
		if (String(detail.formId || '').toLowerCase() !== FORM_ID) return;

		// Privacy fail-closed: do not even read/hash the email without marketing consent.
		var payload = await buildPayload(event);
		if (!payload || !hasMarketingConsent()) return;
		transmit(payload); // Sent without awaiting to reduce window before navigation
	}

	window.addEventListener('hs-form-event:on-submission:success', handleSuccessfulSubmission);
}());
