(function() {
	'use strict';

	// Attribution contract schema v2
	var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
	var CLICK_KEYS = ['gclid', 'gbraid', 'wbraid', 'gclsrc'];
	var ATTR_TTL_MS = 90 * 24 * 60 * 60 * 1000; // 90 days

	// Storage keys (canonical — must match test-attribution-contract-v2.mjs)
	var FIRST_TOUCH_KEY = 'nvx_first_touch';
	var CONVERSION_TOUCH_KEY = 'nvx_conversion_touch';
	var LEAD_SESSION_KEY = 'nvx_lead_id'; // session-scoped only

	// Server-injected QA context (populated by nvx_attribution_qa_context() in PHP)
	var qa = (window.nvxConversionEvents && window.nvxConversionEvents.qa) || { is_test_lead: false, test_run_id: '' };

	/**
	 * Get or create a session-scoped lead ID.
	 * Must NOT become a long-lived tracking identifier.
	 */
	function safeSessionStorage() {
		var id = window.sessionStorage.getItem(LEAD_SESSION_KEY);
		if (!id) {
			id = typeof window.crypto !== 'undefined' && typeof window.crypto.randomUUID === 'function'
				? window.crypto.randomUUID()
				: 'lead_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
			window.sessionStorage.setItem(LEAD_SESSION_KEY, id);
		}
		return id;
	}

	/**
	 * Classify traffic channel from UTMs, referrer and URL.
	 */
	function classifyChannel(utm, referrer, url) {
		if (utm.utm_source) {
			if (utm.utm_source === 'google' && utm.utm_medium === 'cpc') return 'paid_search';
			if (utm.utm_source === 'facebook' || utm.utm_source === 'instagram') return 'paid_social';
			if (utm.utm_medium && utm.utm_medium.indexOf('cpc') !== -1) return 'paid_search';
			if (utm.utm_medium && utm.utm_medium.indexOf('cpm') !== -1) return 'paid_display';
			return 'paid_other';
		}
		if (referrer && referrer !== '') {
			if (referrer.indexOf('google') !== -1 || referrer.indexOf('bing') !== -1) return 'organic_search';
			if (referrer.indexOf('facebook') !== -1 || referrer.indexOf('instagram') !== -1) return 'organic_social';
			var curHost = (window.location && window.location.hostname) || '';
			if (curHost && referrer.indexOf(curHost) === -1) return 'referral';
			return 'internal';
		}
		return 'direct';
	}

	/** Marketing consent via canonical WordPress API. */
	function hasMarketingConsent() {
		return typeof window.wp_has_consent === 'function' && window.wp_has_consent('marketing') === true;
	}

	/** Safe localStorage helpers. */
	function lsGet(key) { try { return window.localStorage.getItem(key); } catch (e) { return null; } }
	function lsSet(key, value) { try { window.localStorage.setItem(key, value); return true; } catch (e) { return false; } }

	/** Read a stored touch snapshot; return null if missing or expired. */
	function readTouch(key) {
		var raw = lsGet(key);
		if (!raw) return null;
		try {
			var obj = JSON.parse(raw);
			if (obj && obj.expires_at && Date.now() > obj.expires_at) {
				try { window.localStorage.removeItem(key); } catch (e) {}
				return null;
			}
			return obj;
		} catch (e) { return null; }
	}

	/** Build a touch snapshot from current page context. */
	function buildTouch(existingFirst) {
		var params = new URLSearchParams((window.location && window.location.search) || '');
		var utm = {}, clicks = {}, i;
		for (i = 0; i < UTM_KEYS.length; i++) {
			var v = params.get(UTM_KEYS[i]);
			if (v) utm[UTM_KEYS[i]] = v;
		}
		for (i = 0; i < CLICK_KEYS.length; i++) {
			var cv = params.get(CLICK_KEYS[i]);
			if (cv) clicks[CLICK_KEYS[i]] = cv;
		}

		var href = (window.location && window.location.href) || '';
		var landing = href;
		try { landing = new URL(href).origin + new URL(href).pathname; } catch (e) {}

		var referrer = (document && document.referrer) || '';
		var channel = classifyChannel(utm, referrer, href);

		var now = Date.now();

		// Infer source/medium from referrer when no UTMs are present (organic traffic)
		var inferredSource = utm.utm_source || '';
		var inferredMedium = utm.utm_medium || '';
		if (!inferredSource && referrer) {
			if (referrer.indexOf('google') !== -1) { inferredSource = 'google'; inferredMedium = inferredMedium || 'organic'; }
			else if (referrer.indexOf('bing') !== -1) { inferredSource = 'bing'; inferredMedium = inferredMedium || 'organic'; }
			else if (referrer.indexOf('facebook') !== -1) { inferredSource = 'facebook'; inferredMedium = inferredMedium || 'organic'; }
			else if (referrer.indexOf('instagram') !== -1) { inferredSource = 'instagram'; inferredMedium = inferredMedium || 'organic'; }
		}

		var touch = {
			channel:         channel,
			source:          inferredSource,
			medium:          inferredMedium,
			campaign_id:     utm.utm_campaign || '',
			landing_url:     landing,
			referrer_domain: '',
			timestamp:       new Date(now).toISOString(),
			expires_at:      now + ATTR_TTL_MS,
		};
		if (clicks.gclid)  touch.gclid  = clicks.gclid;
		if (clicks.gbraid) touch.gbraid = clicks.gbraid;
		if (clicks.wbraid) touch.wbraid = clicks.wbraid;
		if (clicks.gclsrc) touch.gclsrc = clicks.gclsrc;
		if (referrer) { try { touch.referrer_domain = new URL(referrer).hostname; } catch (e) {} }
		return touch;
	}

	/** Capture first and conversion touch; requires marketing consent. */
	function captureAttribution() {
		if (!hasMarketingConsent()) return;

		var existing = readTouch(FIRST_TOUCH_KEY);
		var touch = buildTouch(existing);

		// First touch: write once, never overwrite
		if (!existing) {
			lsSet(FIRST_TOUCH_KEY, JSON.stringify(touch));
		}

		// Conversion touch: update on acquisition signals
		if (touch.channel !== 'internal' && touch.channel !== 'direct') {
			lsSet(CONVERSION_TOUCH_KEY, JSON.stringify(touch));
		} else if (!readTouch(CONVERSION_TOUCH_KEY)) {
			lsSet(CONVERSION_TOUCH_KEY, JSON.stringify(touch));
		}
	}

	/**
	 * Build the HubSpot V4 field payload from stored attribution data.
	 * Only populates fields that are already present on the form instance.
	 *
	 * @param {Set} available - Set of fieldName strings present on this form.
	 */
	function buildFormPayload(available) {
		var first      = readTouch(FIRST_TOUCH_KEY)      || {};
		var conversion = readTouch(CONVERSION_TOUCH_KEY) || {};
		var leadId     = safeSessionStorage();

		// CRM field → value map
		// UTM fields use the convention: utm_source: 'nvx_utm_source' (key → property name stored)
		var fieldMap = {
			// Lead lineage
			nvx_lead_id: 'nvx_lead_id',

			// QA gate — value comes from server-injected qa context only
			nvx_is_test_lead: 'nvx_is_test_lead',
			nvx_test_run_id: 'nvx_test_run_id',

			// UTM → CRM properties
			utm_source: 'nvx_utm_source',
			utm_medium: 'nvx_utm_medium',
			utm_campaign: 'nvx_utm_campaign',
			utm_content: 'nvx_utm_content',
			utm_term: 'nvx_utm_term',

			// Click IDs → CRM properties
			gclid: 'nvx_google_click_id',
			gbraid: 'nvx_google_braid',
			wbraid: 'nvx_google_wbraid',
			gclsrc: 'nvx_google_gclsrc',

			// First touch fields
			nvx_first_source:          'nvx_first_source',
			nvx_first_medium:          'nvx_first_medium',
			nvx_first_campaign_id:     'nvx_first_campaign_id',
			nvx_first_referrer_domain: 'nvx_first_referrer_domain',
			nvx_first_landing_url:     'nvx_first_landing_url',
			nvx_first_timestamp:       'nvx_first_timestamp',
			nvx_first_channel:         'nvx_first_channel',

			// Conversion touch fields
			nvx_conversion_channel:     'nvx_conversion_channel',
			nvx_conversion_source:      'nvx_conversion_source',
			nvx_conversion_medium:      'nvx_conversion_medium',
			nvx_conversion_campaign_id: 'nvx_conversion_campaign_id',
			nvx_conversion_landing_url: 'nvx_conversion_landing_url',
			nvx_conversion_timestamp:   'nvx_conversion_timestamp',
		};

		// Resolve actual values for the field map
		var rawValues = {
			nvx_lead_id:               leadId,
			nvx_is_test_lead: qa.is_test_lead === true,
			nvx_test_run_id:           qa.test_run_id || '',
			
			utm_source:                first.source || '',
			utm_medium:                first.medium || '',
			utm_campaign:              first.campaign_id || '',
			utm_content:               '',
			utm_term:                  '',

			gclid:                     first.gclid  || conversion.gclid  || '',
			gbraid:                    first.gbraid || conversion.gbraid || '',
			wbraid:                    first.wbraid || conversion.wbraid || '',
			gclsrc:                    first.gclsrc || conversion.gclsrc || '',

			nvx_first_source:          first.source          || '',
			nvx_first_medium:          first.medium          || '',
			nvx_first_campaign_id:     first.campaign_id     || '',
			nvx_first_referrer_domain: first.referrer_domain || '',
			nvx_first_landing_url:     first.landing_url     || '',
			nvx_first_timestamp:       first.timestamp       || '',
			nvx_first_channel:         first.channel         || '',

			nvx_conversion_channel:     conversion.channel     || '',
			nvx_conversion_source:      conversion.source      || '',
			nvx_conversion_medium:      conversion.medium      || '',
			nvx_conversion_campaign_id: conversion.campaign_id || '',
			nvx_conversion_landing_url: conversion.landing_url || '',
			nvx_conversion_timestamp:   conversion.timestamp   || '',
		};

		var result = {};
		Object.keys(fieldMap).forEach(function(key) {
			var fieldName = fieldMap[key];
			if (!available.has(fieldName)) return;
			var rawValue = rawValues[key];
			// HubSpot V4 single checkbox (boolean) must receive Boolean, not string
			result[fieldName] = key === 'nvx_is_test_lead' ? Boolean(rawValue) : rawValue;
		});

		return result;
	}

	// Capture on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', captureAttribution);
	} else {
		captureAttribution();
	}

	/** Public API — window.NUVANXAttributionContract (v2 canonical name). */
	window.NUVANXAttributionContract = {
		getFirstTouch:        function() { captureAttribution(); return readTouch(FIRST_TOUCH_KEY); },
		getConversionTouch:   function() { captureAttribution(); return readTouch(CONVERSION_TOUCH_KEY); },
		getLeadId:            safeSessionStorage,
		buildFormPayload:     buildFormPayload,
		classifyChannel:      classifyChannel,
		FIRST_TOUCH_KEY:      FIRST_TOUCH_KEY,
		CONVERSION_TOUCH_KEY: CONVERSION_TOUCH_KEY,
		UTM_KEYS:             UTM_KEYS,
		CLICK_KEYS:           CLICK_KEYS,
		ATTR_TTL_MS:          ATTR_TTL_MS,
	};

	/** Legacy compat: existing code referencing window.nvxAttribution keeps working. */
	window.nvxAttribution = {
		getLeadId:       safeSessionStorage,
		getFirstTouch:   function() { return readTouch(FIRST_TOUCH_KEY) || {}; },
		classifyChannel: classifyChannel,
		UTM_KEYS:        UTM_KEYS,
		CLICK_KEYS:      CLICK_KEYS,
		ATTR_TTL_MS:     ATTR_TTL_MS,
	};
})();
