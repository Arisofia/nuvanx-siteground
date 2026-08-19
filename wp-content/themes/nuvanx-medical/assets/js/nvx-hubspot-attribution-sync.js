(function () {
	'use strict';

	var config = window.nvxConversionEvents || {};
	var FORM_ID = String((config.forms || {}).valoracion || '').toLowerCase();
	var FIRST_PARTY_FIELDS = new Set([
		'nvx_lead_id',
		'nvx_is_test_lead',
		'nvx_test_run_id',
	]);
	var MARKETING_FIELDS = [
		'nvx_utm_source',
		'nvx_utm_medium',
		'nvx_utm_campaign',
		'nvx_utm_content',
		'nvx_utm_term',
		'nvx_google_click_id',
		'nvx_google_braid',
		'nvx_google_wbraid',
		'nvx_google_gclsrc',
		'hs_google_click_id',
		'nvx_first_source',
		'nvx_first_medium',
		'nvx_first_campaign_id',
		'nvx_first_referrer_domain',
		'nvx_first_landing_url',
		'nvx_first_timestamp',
		'nvx_first_channel',
		'nvx_conversion_channel',
		'nvx_conversion_source',
		'nvx_conversion_medium',
		'nvx_conversion_campaign_id',
		'nvx_conversion_landing_url',
		'nvx_conversion_timestamp',
	];

	function debugEnabled() {
		try {
			var runtime = window.nvxConversionEvents || {};
			return runtime.debug === true || runtime.env === 'staging2';
		} catch (_error) {
			return false;
		}
	}

	function debugWarn(scope, error) {
		if (!debugEnabled()) return;
		var name = error && error.name ? String(error.name) : 'Error';
		var message = error && error.message ? String(error.message) : String(error || 'unknown');
		if (message.length > 160) message = message.slice(0, 160);
		try {
			console.info('[nvx-attribution-sync]', scope, name, message);
		} catch (_ignore) {}
	}

	function hasMarketingConsent() {
		try {
			return typeof window.wp_has_consent === 'function' && window.wp_has_consent('marketing') === true;
		} catch (error) {
			debugWarn('hasMarketingConsent', error);
			return false;
		}
	}

	function canonicalPropertyName(fieldName) {
		return String(fieldName || '').trim().replace(/^\d+-\d+\//, '');
	}

	function isCanonicalForm(form) {
		if (!FORM_ID || !form || typeof form.getFormId !== 'function') return false;
		try {
			return String(form.getFormId() || '').toLowerCase() === FORM_ID;
		} catch (error) {
			debugWarn('isCanonicalForm', error);
			return false;
		}
	}

	function fieldIndex(fields) {
		var index = new Map();
		(fields || []).forEach(function (field) {
			var actualName = field && typeof field.name === 'string' ? field.name : '';
			var propertyName = canonicalPropertyName(actualName);
			if (!propertyName) return;
			if (!index.has(propertyName) || /^\d+-\d+\//.test(actualName)) {
				index.set(propertyName, actualName);
			}
		});
		return index;
	}

	function setField(form, index, propertyName, value) {
		var actualName = index.get(propertyName);
		if (!actualName) return false;
		try {
			form.setFieldValue(actualName, value === undefined || value === null ? '' : value);
			return true;
		} catch (error) {
			debugWarn('setField', error);
			return false;
		}
	}

	async function syncForm(form) {
		if (!isCanonicalForm(form)) return false;
		if (typeof form.getFormFieldValues !== 'function' || typeof form.setFieldValue !== 'function') return false;

		var contract = window.NUVANXAttributionContract;
		if (!contract || typeof contract.buildFormPayload !== 'function') return false;

		var fields;
		try {
			fields = await form.getFormFieldValues();
		} catch (error) {
			debugWarn('getFormFieldValues', error);
			return false;
		}
		if (!Array.isArray(fields)) return false;

		var index = fieldIndex(fields);
		var payload;
		try {
			payload = contract.buildFormPayload(new Set(index.keys())) || {};
		} catch (error) {
			debugWarn('buildFormPayload', error);
			return false;
		}

		var marketingConsent = hasMarketingConsent();
		var changed = false;

		if (!marketingConsent) {
			MARKETING_FIELDS.forEach(function (propertyName) {
				if (index.has(propertyName)) changed = setField(form, index, propertyName, '') || changed;
			});
		}

		Object.keys(payload).forEach(function (propertyName) {
			if (!marketingConsent && !FIRST_PARTY_FIELDS.has(propertyName)) return;
			changed = setField(form, index, propertyName, payload[propertyName]) || changed;
		});

		return changed;
	}

	function formFromEvent(event) {
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getFormFromEvent !== 'function') return null;
		try {
			return window.HubSpotFormsV4.getFormFromEvent(event) || null;
		} catch (error) {
			debugWarn('formFromEvent', error);
			return null;
		}
	}

	function syncExistingForms() {
		if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getForms !== 'function') return;
		try {
			(window.HubSpotFormsV4.getForms() || []).forEach(function (form) {
				var result = syncForm(form);
				if (result && typeof result.catch === 'function') {
					result.catch(function (error) { debugWarn('syncExistingForms', error); });
				}
			});
		} catch (error) {
			debugWarn('syncExistingForms', error);
		}
	}

	window.addEventListener('hs-form-event:on-ready', function (event) {
		var detail = event && event.detail ? event.detail : {};
		if (!FORM_ID || String(detail.formId || '').toLowerCase() !== FORM_ID) return;
		var form = formFromEvent(event);
		if (form) syncForm(form);
	});

	document.addEventListener('wp_listen_for_consent_change', syncExistingForms);
	document.addEventListener('wp_consent_type_defined', syncExistingForms);
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncExistingForms, { once: true });
	} else {
		syncExistingForms();
	}

	window.NUVANXHubSpotAttributionSync = Object.freeze({
		syncForm: syncForm,
		syncExistingForms: syncExistingForms,
		canonicalPropertyName: canonicalPropertyName,
	});
}());
