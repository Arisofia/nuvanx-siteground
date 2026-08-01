(function () {
  'use strict';

  var config = window.nvxRuntimeGovernance || {};

  function setInert(element, inert) {
    if (!element) return;
    if (inert) element.setAttribute('inert', '');
    else element.removeAttribute('inert');
  }

  function focusableElements(container) {
    if (!container) return [];
    return Array.prototype.slice.call(
      container.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
      )
    ).filter(function (element) {
      return !element.hasAttribute('hidden') && element.getAttribute('aria-hidden') !== 'true';
    });
  }

  function initMobileNavigationGovernance() {
    var nav = document.getElementById(config.mobileNavId || 'nvx-mobile-nav');
    var trigger = document.getElementById('nvx-hamburger-btn');
    var close = document.getElementById('nvx-mobile-close');
    if (!nav || !trigger) return;

    var wasOpen = false;

    function isOpen() {
      return nav.classList.contains('is-open') || nav.getAttribute('aria-hidden') === 'false';
    }

    function synchronize() {
      var open = isOpen();
      var focusWasInside = nav.contains(document.activeElement);
      setInert(nav, !open);

      if (open && !wasOpen) {
        window.setTimeout(function () {
          var target = close || focusableElements(nav)[0];
          if (target && typeof target.focus === 'function') target.focus();
        }, 0);
      }

      if (!open && wasOpen && focusWasInside && typeof trigger.focus === 'function') {
        trigger.focus();
      }
      wasOpen = open;
    }

    setInert(nav, !isOpen());
    wasOpen = isOpen();

    // Remove inert synchronously before the existing menu handler exposes the drawer.
    trigger.addEventListener(
      'click',
      function () {
        if (!isOpen()) setInert(nav, false);
      },
      true
    );

    new MutationObserver(synchronize).observe(nav, {
      attributes: true,
      attributeFilter: ['class', 'aria-hidden']
    });

    nav.addEventListener('click', function (event) {
      var link = event.target?.closest?.('a[href]') || null;
      if (!link) return;
      nav.classList.remove('is-open');
      nav.setAttribute('aria-hidden', 'true');
      trigger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });

    document.addEventListener('keydown', function (event) {
      if (!isOpen()) return;

      if (event.key === 'Escape') {
        event.preventDefault();
        if (close) close.click();
        else {
          nav.classList.remove('is-open');
          nav.setAttribute('aria-hidden', 'true');
          trigger.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        }
        return;
      }

      if (event.key !== 'Tab') return;
      var focusables = focusableElements(nav);
      if (!focusables.length) return;
      var first = focusables[0];
      var last = focusables[focusables.length - 1];

      if (!nav.contains(document.activeElement)) {
        event.preventDefault();
        first.focus();
        return;
      }

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  function initLazyHubSpot() {
    if (!config.modalEnabled || !config.hubspotScriptUrl) return;

    var modal = document.getElementById(config.modalId || 'nvx-valoracion-modal');
    if (!modal) return;

    var promise = null;

    function initializeForms() {
      modal.classList.remove('nvx-valoracion-modal--embed-error');
      if (window.HubSpotForms && typeof window.HubSpotForms.initialize === 'function') {
        window.HubSpotForms.initialize();
      }
    }

    function loadHubSpot() {
      if (window.HubSpotForms) {
        initializeForms();
        return Promise.resolve();
      }
      if (promise) return promise;

      promise = new Promise(function (resolve, reject) {
        var scriptId = config.hubspotScriptId || 'nvx-hubspot-forms-runtime';
        var existing = document.getElementById(scriptId);
        if (existing) {
          if (existing.dataset.nvxLoaded === '1') {
            initializeForms();
            resolve();
            return;
          }

          existing.addEventListener('load', function () {
            existing.dataset.nvxLoaded = '1';
            initializeForms();
            resolve();
          }, { once: true });
          existing.addEventListener('error', function () {
            existing.remove();
            reject(new Error('Existing HubSpot form embed failed to load.'));
          }, { once: true });
          return;
        }

        var script = document.createElement('script');
        script.id = scriptId;
        script.src = config.hubspotScriptUrl;
        script.async = true;
        script.addEventListener('load', function () {
          script.dataset.nvxLoaded = '1';
          initializeForms();
          resolve();
        }, { once: true });
        script.addEventListener('error', function () {
          script.remove();
          modal.classList.add('nvx-valoracion-modal--embed-error');
          reject(new Error('HubSpot form embed failed to load.'));
        }, { once: true });
        document.head.appendChild(script);
      });

      promise.catch(function () {
        // Allow a later retry. The modal retains its full-page fallback link.
        promise = null;
      });
      return promise;
    }

    function modalIsOpen() {
      return modal.classList.contains('is-open') || modal.getAttribute('aria-hidden') === 'false';
    }

    new MutationObserver(function () {
      if (modalIsOpen()) loadHubSpot();
    }).observe(modal, {
      attributes: true,
      attributeFilter: ['class', 'aria-hidden', 'hidden']
    });

    document.addEventListener(
      'click',
      function (event) {
        var link = event.target?.closest?.('a') || null;
        if (!link) return;
        if (
          link.classList.contains('nvx-open-valoracion-modal') ||
          link.dataset.nvxValoracionModal === '1'
        ) {
          loadHubSpot();
        }
      },
      true
    );
  }

  function initialize() {
    initMobileNavigationGovernance();
    initLazyHubSpot();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }
})();
