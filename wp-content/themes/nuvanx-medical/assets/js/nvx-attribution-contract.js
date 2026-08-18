(function() {
	'use strict';

	// Attribution contract schema v2
	var UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
	var CLICK_KEYS = ['gclid', 'gbraid', 'wbraid', 'gclsrc'];
	var ATTR_TTL_MS = 90 * 24 * 60 * 60 * 1000; // 90 days

	/**
	 * Get or create session-scoped lead ID.
	 * Must NOT become a long-lived tracking cookie.
	 */
	function safeSessionStorage() {
		var key = 'nvx_lead_id';
		var id = sessionStorage.getItem(key);
		if (!id) {
			id = 'lead_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
			sessionStorage.setItem(key, id);
		}
		return id;
	}

	/**
	 * Classify traffic channel from UTMs and referrer.
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
			if (referrer.indexOf(window.location.hostname) === -1) return 'referral';
			return 'internal';
		}
		return 'direct';
	}

	/**
	 * Capture first-touch attribution on first load.
	 */
	function captureFirstTouch() {
		// Only capture if marketing consent is granted
		if (typeof window.wp_has_consent !== 'function') return;
		if (window.wp_has_consent('marketing') !== true) return;

		// Already captured in this session
		if (sessionStorage.getItem('nvx_first_touch')) return;

		// Parse current query string
		var params = new URLSearchParams(window.location.search || '');
		var utm = {};
		var clicks = {};
		var i;

		for (i = 0; i < UTM_KEYS.length; i++) {
			var key = UTM_KEYS[i];
			var val = params.get(key);
			if (val) utm[key] = val;
		}

		for (i = 0; i < CLICK_KEYS.length; i++) {
			var clickKey = CLICK_KEYS[i];
			var clickVal = params.get(clickKey);
			if (clickVal) clicks[clickKey] = clickVal;
		}

		var now = new Date();
		var expiresAt = new Date(now.getTime() + ATTR_TTL_MS);

		var firstTouch = {
			nvx_lead_id: safeSessionStorage(),
			nvx_first_channel: classifyChannel(utm, document.referrer, window.location.href),
			nvx_first_referrer_domain: new URL(document.referrer || 'http://localhost').hostname,
			nvx_first_landing_url: window.location.href,
			nvx_first_timestamp: now.toISOString(),
			nvx_attribution_captured_at: now.toISOString(),
			nvx_attribution_expires_at: expiresAt.toISOString()
		};

		// Merge UTM and click data
		Object.assign(firstTouch, utm);
		Object.assign(firstTouch, clicks);

		sessionStorage.setItem('nvx_first_touch', JSON.stringify(firstTouch));
	}

	/**
	 * Get available form fields from HubSpot embed context.
	 */
	function fieldCandidates(propertyName) {
		var available = {
			'firstname': true,
			'lastname': true,
			'email': true,
			'phone': true,
			'message': true,
			'nvx_lead_id': true,
			'nvx_utm_source': true,
			'nvx_utm_medium': true,
			'nvx_utm_campaign': true,
			'nvx_utm_content': true,
			'nvx_utm_term': true,
			'nvx_landing_url': true,
			'nvx_attribution_captured_at': true,
			'nvx_attribution_expires_at': true,
			'nvx_google_click_id': true,
			'nvx_google_braid': true,
			'nvx_google_wbraid': true,
			'nvx_google_gclsrc': true
		};
		return available.hasOwnProperty(propertyName) ? propertyName : null;
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', captureFirstTouch);
	} else {
		captureFirstTouch();
	}

	// Export for testing and form population
	window.nvxAttribution = {
		getLeadId: safeSessionStorage,
		getFirstTouch: function() {
			try {
				return JSON.parse(sessionStorage.getItem('nvx_first_touch') || '{}');
			} catch (e) {
				return {};
			}
		},
		classifyChannel: classifyChannel,
		UTM_KEYS: UTM_KEYS,
		CLICK_KEYS: CLICK_KEYS,
		ATTR_TTL_MS: ATTR_TTL_MS
	};
})();