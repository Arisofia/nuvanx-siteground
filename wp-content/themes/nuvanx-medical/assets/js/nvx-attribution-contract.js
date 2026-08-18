(function () {
	'use strict';

	var config = window.nvxConversionEvents || {};
	var forms = config.forms || {};
	var qa = config.qa || {};
	var FORM_ID = String(forms.valoracion || '5042522a-0bc5-4381-ac3e-5aee8649b69c').toLowerCase();
	var LEAD_STORAGE_KEY = 'nvx_lead_id_v1';
	var FIRST_TOUCH_KEY = 'nvx_first_touch';
	var CONVERSION_TOUCH_KEY = 'nvx_conversion_touch';
	var ATTR_TTL_MS = 90 * 24 * 60 * 60 * 1000;
	var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
	var CLICK_KEYS = ['gclid', 'gbraid', 'wbraid', 'gclsrc'];
	var SEARCH_DOMAINS = /(^|\.)(google|bing|yahoo|duckduckgo|ecosia)\./i;
	var SOCIAL_DOMAINS = /(^|\.)(facebook|instagram|threads|tiktok|linkedin|pinterest|x|twitter)\./i;
	var SOCIAL_SOURCES = /^(facebook|instagram|threads|tiktok|linkedin|pinterest|x|twitter|meta)$/i;
	var PAID_SEARCH_MEDIUM = /^(cpc|ppc|paidsearch|paid_search|search_paid)$/i;
	var PAID_SOCIAL_MEDIUM = /^(paid_social|paidsocial|social_paid|social-paid)$/i;
	var HUBSPOT_FIELD_MAP = {
		nvx_lead_id: 'nvx_lead_id',
		nvx_is_test_lead: 'nvx_is_test_lead',
		nvx_test_run_id: 'nvx_test_run_id',
		utm_source: 'nvx_utm_source',
		utm_medium: 'nvx_utm_medium',
		utm_campaign: 'nvx_utm_campaign',
		utm_content: 'nvx_utm_content',
		utm_term: 'nvx_utm_term',
		gclid: 'nvx_google_click_id',
		gbraid: 'nvx_google_braid',
		wbraid: 'nvx_google_wbraid',
		gclsrc: 'nvx_google_gclsrc',
		landing_url: 'nvx_landing_url',
		captured_at: 'nvx_attribution_captured_at',
		expires_at: 'nvx_attribution_expires_at',
		nvx_first_channel: 'nvx_first_channel',
		nvx_first_source: 'nvx_first_source',
		nvx_first_medium: 'nvx_first_medium',
		nvx_first_campaign_id: 'nvx_first_campaign_id',
		nvx_first_referrer_domain: 'nvx_first_referrer_domain',
		nvx_first_landing_url: 'nvx_first_landing_url',
		nvx_first_timestamp: 'nvx_first_timestamp',
		nvx_conversion_channel: 'nvx_conversion_channel',
		nvx_conversion_source: 'nvx_conversion_source',
		nvx_conversion_medium: 'nvx_conversion_medium',
		nvx_conversion_campaign_id: 'nvx_conversion_campaign_id',
		nvx_conversion_landing_url: 'nvx_conversion_landing_url',
		nvx_conversion_timestamp: 'nvx_conversion_timestamp',
	};

	function hasMarketingConsent() {
		try {
			return typeof window.wp_has_consent === 'function' && window.wp_has_consent('marketing') === true;
		} catch (_error) {
			return false;
		}
	}

	function storageAvailable(storage) {
		if (!storage) return false;
		try {
			var probe = '__nvx_probe__';
			storage.setItem(probe, '1');
			storage.removeItem(probe);
			return true;
		} catch (_error) {
			return false;
		}
	}

	function safeLocalStorage() {
		try {
			return storageAvailable(window.localStorage) ? window.localStorage : null;
		} catch (_error) {
			return null;
		}
	}

	function safeSessionStorage() {
		try {
			return storageAvailable(window.sessionStorage) ? window.sessionStorage : null;
		} catch (_error) {
			return null;
		}
	}

	function isUuid(value) {
		return /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(String(value || ''));
	}

	function createUuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			var generated = window.crypto.randomUUID();
			return isUuid(generated) ? generated.toLowerCase() : '';
		}
		if (!window.crypto || typeof window.crypto.getRandomValues !== 'function') return '';
		var bytes = new Uint8Array(16);
		window.crypto.getRandomValues(bytes);
		bytes[6] = (bytes[6] & 0x0f) | 0x40;
		bytes[8] = (bytes[8] & 0x3f) | 0x80;
		var hex = Array.from(bytes).map(function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
		return (
			hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' +
			hex.slice(16, 20) + '-' + hex.slice(20)
		).toLowerCase();
	}

	function getOrCreateLeadId() {
		var storage = safeSessionStorage();
		if (storage) {
			try {
				var stored = storage.getItem(LEAD_STORAGE_KEY);
				if (isUuid(stored)) return String(stored).toLowerCase();
			} catch (_error) {}
		}
		var id = createUuid();
		if (!id) return '';
		if (storage) {
			try { storage.setItem(LEAD_STORAGE_KEY, id); } catch (_error) {}
		}
		return id;
	}

	function readJson(storage, key) {
		if (!storage) return null;
		try {
			var raw = storage.getItem(key);
			return raw ? JSON.parse(raw) : null;
		} catch (_error) {
			return null;
		}
	}

	function writeJson(storage, key, value) {
		if (!storage) return;
		try { storage.setItem(key, JSON.stringify(value)); } catch (_error) {}
	}

	function cleanCampaignValue(value, maxLength) {
		var normalized = String(value || '').trim();
		if (!normalized || normalized.length > maxLength) return '';
		if (/[^\u0020-\u007E\u00A0-\u024F]/u.test(normalized)) return '';
		return normalized;
	}

	function canonicalLandingUrl() {
		try {
			var current = new URL(window.location.href);
			return current.origin + current.pathname;
		} catch (_error) {
			return '';
		}
	}

	function normalizedHost(host) {
		return String(host || '').toLowerCase().replace(/^www\./, '');
	}

	function currentHost() {
		try { return normalizedHost(window.location.hostname || ''); } catch (_error) { return ''; }
	}

	function referrerDomain() {
		try {
			if (!document.referrer) return '';
			return String(new URL(document.referrer).hostname || '').toLowerCase();
		} catch (_error) {
			return '';
		}
	}

	function sourceFromDomain(domain) {
		var normalized = String(domain || '').toLowerCase();
		if (/google\./i.test(normalized)) return 'google';
		if (/bing\./i.test(normalized)) return 'bing';
		if (/yahoo\./i.test(normalized)) return 'yahoo';
		if (/duckduckgo\./i.test(normalized)) return 'duckduckgo';
		if (/ecosia\./i.test(normalized)) return 'ecosia';
		if (/instagram\./i.test(normalized)) return 'instagram';
		if (/facebook\./i.test(normalized)) return 'facebook';
		if (/threads\./i.test(normalized)) return 'threads';
		if (/tiktok\./i.test(normalized)) return 'tiktok';
		if (/linkedin\./i.test(normalized)) return 'linkedin';
		if (/pinterest\./i.test(normalized)) return 'pinterest';
		if (/(^|\.)x\.com$/i.test(normalized) || /twitter\./i.test(normalized)) return 'x';
		return normalized;
	}

	function currentMarketingParameters() {
		var params = new URLSearchParams(window.location.search || '');
		var snapshot = {};
		UTM_KEYS.forEach(function (key) {
			snapshot[key] = cleanCampaignValue(params.get(key), key === 'utm_content' || key === 'utm_term' ? 300 : 200);
		});
		CLICK_KEYS.forEach(function (key) {
			snapshot[key] = cleanCampaignValue(params.get(key), key === 'gclsrc' ? 128 : 512);
		});
		snapshot.campaign_id = cleanCampaignValue(params.get('utm_id') || params.get('campaign_id'), 200);
		return snapshot;
	}

	function classifyChannel(marketing, referrer) {
		var data = marketing || {};
		var source = String(data.utm_source || '').toLowerCase();
		var medium = String(data.utm_medium || '').toLowerCase();
		var domain = String(referrer || '').toLowerCase();
		var hasGoogleClick = Boolean(data.gclid || data.gbraid || data.wbraid);

		if (hasGoogleClick || PAID_SEARCH_MEDIUM.test(medium)) {
			return { channel: 'paid_search', source: source || 'google', medium: medium || 'cpc' };
		}
		if (PAID_SOCIAL_MEDIUM.test(medium) || (source && SOCIAL_SOURCES.test(source) && /^(cpc|paid|ads?)$/i.test(medium))) {
			return { channel: 'paid_social', source: source || 'social', medium: medium || 'paid_social' };
		}
		if (source || medium) {
			if (/^email$/i.test(medium)) return { channel: 'email', source: source || 'email', medium: medium };
			return { channel: 'other', source: source || 'unknown', medium: medium || 'unknown' };
		}
		if (domain && normalizedHost(domain) === currentHost()) return { channel: 'internal', source: '', medium: '' };
		if (domain && SEARCH_DOMAINS.test(domain)) return { channel: 'organic_search', source: sourceFromDomain(domain), medium: 'organic' };
		if (domain && SOCIAL_DOMAINS.test(domain)) return { channel: 'organic_social', source: sourceFromDomain(domain), medium: 'social' };
		if (domain) return { channel: 'referral', source: sourceFromDomain(domain), medium: 'referral' };
		return { channel: 'direct', source: 'direct', medium: 'none' };
	}

	function isFresh(snapshot, now) {
		return Boolean(snapshot && Number(snapshot.expires_at_ms || 0) > now && snapshot.channel);
	}

	function buildAcquisitionTouch(now) {
		var marketing = currentMarketingParameters();
		var domain = referrerDomain();
		var classified = classifyChannel(marketing, domain);
		if (classified.channel === 'internal') return null;
		var expiresAtMs = now + ATTR_TTL_MS;
		return {
			channel: classified.channel,
			source: classified.source,
			medium: classified.medium,
			campaign_id: marketing.campaign_id || marketing.utm_campaign || '',
			referrer_domain: domain,
			landing_url: canonicalLandingUrl(),
			timestamp: new Date(now).toISOString(),
			expires_at: new Date(expiresAtMs).toISOString(),
			expires_at_ms: expiresAtMs,
			utm_source: marketing.utm_source || '',
			utm_medium: marketing.utm_medium || '',
			utm_campaign: marketing.utm_campaign || '',
			utm_content: marketing.utm_content || '',
			utm_term: marketing.utm_term || '',
			gclid: marketing.gclid || '',
			gbraid: marketing.gbraid || '',
			wbraid: marketing.wbraid || '',
			gclsrc: marketing.gclsrc || '',
		};
	}

	function firstTouchSnapshot() {
		if (!hasMarketingConsent()) return null;
		var storage = safeLocalStorage();
		var now = Date.now();
		var stored = readJson(storage, FIRST_TOUCH_KEY);
		if (isFresh(stored, now)) return stored;
		var current = buildAcquisitionTouch(now);
		if (!current) return null;
		writeJson(storage, FIRST_TOUCH_KEY, current);
		return current;
	}

	function conversionTouchSnapshot() {
		if (!hasMarketingConsent()) return null;
		var storage = safeLocalStorage();
		var now = Date.now();
		var first = firstTouchSnapshot();
		var previous = readJson(storage, CONVERSION_TOUCH_KEY);
		var current = buildAcquisitionTouch(now);
		var acquisition = current || (isFresh(previous, now) ? previous : first);
		if (!acquisition) return null;

		var expiresAtMs = now + ATTR_TTL_MS;
		var snapshot = Object.assign({}, acquisition, {
			landing_url: canonicalLandingUrl(),
			timestamp: new Date(now).toISOString(),
			expires_at: new Date(expiresAtMs).toISOString(),
			expires_at_ms: expiresAtMs,
		});
		writeJson(storage, CONVERSION_TOUCH_KEY, snapshot);
		return snapshot;
	}

	function attributionValues() {
		var first = firstTouchSnapshot();
		var conversion = conversionTouchSnapshot();
		if (!first && !conversion) return null;
		var legacy = conversion || first;
		return {
			utm_source: legacy.utm_source || '',
			utm_medium: legacy.utm_medium || '',
			utm_campaign: legacy.utm_campaign || '',
			utm_content: legacy.utm_content || '',
			utm_term: legacy.utm_term || '',
			gclid: legacy.gclid || '',
			gbraid: legacy.gbraid || '',
			wbraid: legacy.wbraid || '',
			gclsrc: legacy.gclsrc || '',
			landing_url: legacy.landing_url || '',
			captured_at: legacy.timestamp || '',
			expires_at: legacy.expires_at || '',
			nvx_first_channel: first ? first.channel || '' : '',
			nvx_first_source: first ? first.source || '' : '',
			nvx_first_medium: first ? first.medium || '' : '',
			nvx_first_campaign_id: first ? first.campaign_id || '' : '',
			nvx_first_referrer_domain: first ? first.referrer_domain || '' : '',
			nvx_first_landing_url: first ? first.landing_url || '' : '',
			nvx_first_timestamp: first ? first.timestamp || '' : '',
			nvx_conversion_channel: conversion ? conversion.channel || '' : '',
			nvx_conversion_source: conversion ? conversion.source || '' : '',
			nvx_conversion_medium: conversion ? conversion.medium || '' : '',
			nvx_conversion_campaign_id: conversion ? conversion.campaign_id || '' : '',
			nvx_conversion_landing_url: conversion ? conversion.landing_url || '' : '',
			nvx_conversion_timestamp: conversion ? conversion.timestamp || '' : '',
		};
	}

	function qaValues() {
		return {
			nvx_is_test_lead: qa.is_test_lead === true,
			nvx_test_run_id: qa.is_test_lead === true ? cleanCampaignValue(qa.test_run_id, 200) : '',
		};
	}

	function fieldCandidates(propertyName) {
		return ['0-1/' + propertyName, propertyName];
	}

	function isCanonicalForm(form) {
		if (!form || typeof form.getFormId !== 'function') return false;
		try { return String(form.getFormId() || '').toLowerCase() === FORM_ID; } catch (_error) { return false; }
	}

	async function populateHubSpotForm(form) {
		if (!isCanonicalForm(form)) return false;
		if (typeof form.getFormFieldValues !== 'function' || typeof form.setFieldValue !== 'function') return false;
		var fields;
		try { fields = await form.getFormFieldValues(); } catch (_error) { return false; }
		if (!Array.isArray(fields)) return false;
		var available = new Set(fields.map(function (field) {
			return field && typeof field.name === 'string' ? field.name : '';
		}).filter(Boolean));
		var values = Object.assign({ nvx_lead_id: getOrCreateLeadId() }, qaValues());
		var attr = attributionValues();
		if (attr) values = Object.assign(values, attr);
		var modified = false;
		Object.keys(values).forEach(function (key) {
			var propertyName = HUBSPOT_FIELD_MAP[key] || key;
			var rawValue = values[key];
			if ((rawValue === '' || rawValue == null) && key !== 'nvx_is_test_lead') return;
			var value = key === 'nvx_is_test_lead' ? Boolean(rawValue) : String(rawValue || '');
			fieldCandidates(propertyName).forEach(function (fieldName) {
				if (!available.has(fieldName)) return;
				try { form.setFieldValue(fieldName, value); modified = true; } catch (_error) {}
			});
		});
		return modified;
	}

	function ensureDirectHidden(form, name) {
		var input = form.querySelector('input[name="' + name + '"]');
		if (input) return input;
		input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		form.appendChild(input);
		return input;
	}

	function populateDirectForm() {
		var form = document.querySelector('[data-nvx-direct-form]');
		if (!form) return false;
		ensureDirectHidden(form, 'nvx_lead_id').value = getOrCreateLeadId();
		var qaData = qaValues();
		ensureDirectHidden(form, 'nvx_is_test_lead').value = qaData.nvx_is_test_lead ? 'true' : 'false';
		ensureDirectHidden(form, 'nvx_test_run_id').value = qaData.nvx_test_run_id;
		var consent = hasMarketingConsent();
		ensureDirectHidden(form, 'nvx_marketing_consent').value = consent ? '1' : '';
		var attr = attributionValues();
		Object.keys(HUBSPOT_FIELD_MAP).forEach(function (key) {
			if (key === 'nvx_lead_id' || key === 'nvx_is_test_lead' || key === 'nvx_test_run_id') return;
			var postName = key;
			if (key === 'landing_url') postName = 'nvx_landing_url';
			if (key === 'captured_at') postName = 'nvx_attribution_captured_at';
			if (key === 'expires_at') postName = 'nvx_attribution_expires_at';
			ensureDirectHidden(form, postName).value = consent && attr ? String(attr[key] || '') : '';
		});
		return true;
	}

	function populateExistingHubSpotForms() {
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getForms !== 'function') return;
		try { (window.HubSpotFormsV4.getForms() || []).forEach(populateHubSpotForm); } catch (_error) {}
	}

	function refresh() {
		populateDirectForm();
		populateExistingHubSpotForms();
	}

	window.addEventListener('hs-form-event:on-ready', function (event) {
		var detail = event && event.detail ? event.detail : {};
		if (String(detail.formId || '').toLowerCase() !== FORM_ID) return;
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getFormFromEvent !== 'function') return;
		try { populateHubSpotForm(window.HubSpotFormsV4.getFormFromEvent(event)); } catch (_error) {}
	});

	window.addEventListener('hs-form-event:on-submission:started', function (event) {
		var detail = event && event.detail ? event.detail : {};
		if (String(detail.formId || '').toLowerCase() !== FORM_ID) return;
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getFormFromEvent !== 'function') return;
		try { populateHubSpotForm(window.HubSpotFormsV4.getFormFromEvent(event)); } catch (_error) {}
	});

	document.addEventListener('submit', function (event) {
		if (event.target && event.target.matches && event.target.matches('[data-nvx-direct-form]')) populateDirectForm();
	}, true);
	document.addEventListener('wp_listen_for_consent_change', refresh);
	document.addEventListener('wp_consent_type_defined', refresh);
	document.addEventListener('cmplz_status_change', refresh);
	document.addEventListener('cmplz_enable_category', refresh);

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refresh, { once: true });
	else refresh();

	window.NUVANXAttributionContract = Object.freeze({
		version: 2,
		formId: FORM_ID,
		getLeadId: getOrCreateLeadId,
		getFirstTouch: firstTouchSnapshot,
		getConversionTouch: conversionTouchSnapshot,
		getAttribution: attributionValues,
		classifyChannel: classifyChannel,
		hasMarketingConsent: hasMarketingConsent,
		refresh: refresh,
	});
}());
