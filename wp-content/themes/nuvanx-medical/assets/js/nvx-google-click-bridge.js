(function () {
  'use strict';

  var PARAM_FIELDS = {
    gclid: ['hs_google_click_id', 'nvx_google_click_id'],
    gbraid: ['nvx_google_braid'],
    wbraid: ['nvx_google_wbraid'],
    gclsrc: ['nvx_google_gclsrc']
  };
  var HUBSPOT_FORMS_HOST = /^forms(?:-[a-z0-9]+)?\.hsforms\.(?:com|net)$/i;

  var params = new URLSearchParams(window.location.search || '');
  var values = {};

  function collectParamValue(param) {
    var raw = params.get(param);
    if (!raw) return;

    var value = String(raw).trim();
    if (!value || value.length > 512 || !/^[A-Za-z0-9._~-]+$/.test(value)) return;
    values[param] = value;
  }

  Object.keys(PARAM_FIELDS).forEach(collectParamValue);
  if (!Object.keys(values).length) return;

  function propertyFieldNames(propertyName) {
    return ['0-1/' + propertyName, propertyName];
  }

  /**
   * HubSpot hidden fields can be populated from query-string parameters whose
   * keys match the property's internal name. Add aliases for the Google click
   * parameters without persisting them in cookies or Web Storage. This happens
   * before the lazy HubSpot form is requested on the valoración route.
   */
  function addUrlAlias(url, param, fieldName) {
    if (url.searchParams.has(fieldName)) return false;
    url.searchParams.set(fieldName, values[param]);
    return true;
  }

  function aliasParamOnUrl(url, param) {
    var changed = false;
    PARAM_FIELDS[param].forEach(function (fieldName) {
      if (fieldName === 'hs_google_click_id') return;
      changed = addUrlAlias(url, param, fieldName) || changed;
    });
    return changed;
  }

  function aliasCurrentUrlForHubSpot() {
    if (!window.history || typeof window.history.replaceState !== 'function') return;

    var url;
    try {
      url = new URL(window.location.href);
    } catch (_err) {
      return;
    }

    var changed = false;
    Object.keys(values).forEach(function (param) {
      changed = aliasParamOnUrl(url, param) || changed;
    });

    if (!changed) return;
    window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
  }

  function setV4Field(form, availableNames, fieldName, value) {
    if (!availableNames.has(fieldName)) return;
    try {
      form.setFieldValue(fieldName, [value]);
    } catch (_err) {
      // Query-string aliasing remains the primary population path.
    }
  }

  function setV4Property(form, availableNames, propertyName, value) {
    propertyFieldNames(propertyName).forEach(function (fieldName) {
      setV4Field(form, availableNames, fieldName, value);
    });
  }

  function setV4Param(form, availableNames, param) {
    PARAM_FIELDS[param].forEach(function (propertyName) {
      setV4Property(form, availableNames, propertyName, values[param]);
    });
  }

  function populateV4Fields(form, fields) {
    var availableNames = new Set(
      (Array.isArray(fields) ? fields : [])
        .map(function (field) {
          return field && typeof field.name === 'string' ? field.name : '';
        })
        .filter(Boolean)
    );

    Object.keys(values).forEach(function (param) {
      setV4Param(form, availableNames, param);
    });
  }

  /**
   * Current HubSpot forms expose a supported V4 instance API. Populate only
   * fields that actually exist on the form. Contact properties use the 0-1/
   * prefix in the V4 field API.
   */
  function applyToV4Form(form) {
    if (
      !form ||
      typeof form.getFormFieldValues !== 'function' ||
      typeof form.setFieldValue !== 'function'
    ) {
      return;
    }

    Promise.resolve(form.getFormFieldValues())
      .then(function (fields) {
        populateV4Fields(form, fields);
      })
      .catch(function () {
        // Fail closed: never block or alter the public form.
      });
  }

  function applyToV4Event(event) {
    if (
      !window.HubSpotFormsV4 ||
      typeof window.HubSpotFormsV4.getFormFromEvent !== 'function'
    ) {
      return;
    }

    try {
      applyToV4Form(window.HubSpotFormsV4.getFormFromEvent(event));
    } catch (_err) {
      // Legacy fallback below remains available.
    }
  }

  function matchesPropertyName(fieldName, propertyName) {
    return propertyFieldNames(propertyName).includes(fieldName);
  }

  function setLegacyParam(field, fieldName, param) {
    PARAM_FIELDS[param].forEach(function (propertyName) {
      if (!matchesPropertyName(fieldName, propertyName)) return;
      if (field.value === values[param]) return;

      field.value = values[param];
      field.dispatchEvent(new Event('input', { bubbles: true }));
      field.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function applyValuesToLegacyField(field) {
    var fieldName = field.getAttribute('name') || '';
    Object.keys(values).forEach(function (param) {
      setLegacyParam(field, fieldName, param);
    });
  }

  /**
   * Legacy inline-form fallback. Intentionally writes ONLY to input[type=hidden]
   * so a misconfigured visible tracking field is never populated for a visitor.
   */
  function applyToLegacyForm(form) {
    if (!form || typeof form.querySelectorAll !== 'function') return;
    form.querySelectorAll('input[type="hidden"][name]').forEach(applyValuesToLegacyField);
  }

  function applyToKnownLegacyForms() {
    document
      .querySelectorAll('#nvx-hubspot-native-form form, #nvx-valoracion-modal form, .hbspt-form form, form.hs-form')
      .forEach(applyToLegacyForm);
  }

  function isTrustedHubSpotMessageOrigin(origin) {
    if (typeof origin !== 'string' || !origin) return false;
    if (origin === window.location.origin) return true;

    var parser = document.createElement('a');
    parser.href = origin;
    return (
      parser.protocol === 'https:' &&
      parser.origin === origin &&
      HUBSPOT_FORMS_HOST.test(parser.hostname)
    );
  }

  function isLegacyFormEvent(eventName) {
    return (
      eventName === 'onFormReady' ||
      eventName === 'onBeforeFormSubmit' ||
      eventName === 'onFormSubmit'
    );
  }

  function handleLegacyHubSpotMessage(event) {
    if (!event || !event.origin || !isTrustedHubSpotMessageOrigin(event.origin) || !event.source) return;

    var isValidSource = false;
    var frames = document.querySelectorAll('iframe');
    for (var i = 0; i < frames.length; i++) {
      if (frames[i].contentWindow === event.source) {
        isValidSource = true;
        break;
      }
    }
    if (!isValidSource) return;

    var data = event.data;
    if (!data || data.type !== 'hsFormCallback' || !isLegacyFormEvent(data.eventName)) return;
    applyToKnownLegacyForms();
  }

  aliasCurrentUrlForHubSpot();

  window.addEventListener('hs-form-event:on-ready', function (event) {
    applyToV4Event(event);
    applyToKnownLegacyForms();
  });

  window.addEventListener('message', handleLegacyHubSpotMessage);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyToKnownLegacyForms, { once: true });
  } else {
    applyToKnownLegacyForms();
  }
})();
