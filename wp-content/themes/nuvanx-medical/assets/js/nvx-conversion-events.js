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
		var dataEvent = target.getAttribute('data-gtag') || '';
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
		} catch (error) {
			return false;
		}
	}

	document.addEventListener('click', trackClick, true);

	window.addEventListener('hs-form-event:on-submission:success', function (event) {
		var detail = event && event.detail ? event.detail : {};
		trackSuccessfulSubmission(detail.formId || '', 'hubspot_v4');
	});

	window.addEventListener('message', function (event) {
		if (!isAllowedHubSpotOrigin(event.origin)) return;
		var data = event.data || {};
		if (data.type !== 'hsFormCallback' || data.eventName !== 'onFormSubmitted') return;
		trackSuccessfulSubmission(data.id || '', 'hubspot_legacy');
	});

	window.NUVANXConversionEvents = Object.freeze({
		emit: emit,
		trackSuccessfulSubmission: trackSuccessfulSubmission,
	});
}());
