(function () {
  'use strict';

  const config = window.nvxRuntimeGovernance || {};

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
    const nav = document.getElementById(config.mobileNavId || 'nvx-mobile-nav');
    const trigger = document.getElementById('nvx-hamburger-btn');
    const close = document.getElementById('nvx-mobile-close');
    if (!nav || !trigger) return;

    let wasOpen = false;

    function isOpen() {
      return (
        nav.hasAttribute('open') ||
        nav.classList.contains('is-open') ||
        nav.getAttribute('aria-hidden') === 'false'
      );
    }

    function closeNav() {
      nav.classList.remove('is-open');
      nav.removeAttribute('open');
      nav.setAttribute('aria-hidden', 'true');
      setInert(nav, true);
      trigger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    function synchronize() {
      const open = isOpen();
      const focusWasInside = nav.contains(document.activeElement);
      setInert(nav, !open);

      if (open) {
        if (!nav.hasAttribute('open')) nav.setAttribute('open', '');
        if (!nav.classList.contains('is-open')) nav.classList.add('is-open');
        if (nav.getAttribute('aria-hidden') !== 'false') {
          nav.setAttribute('aria-hidden', 'false');
        }
      } else if (nav.hasAttribute('open') || nav.classList.contains('is-open')) {
        nav.removeAttribute('open');
        nav.classList.remove('is-open');
      }

      if (open && !wasOpen) {
        window.setTimeout(function () {
          const target = close || focusableElements(nav)[0];
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
      attributeFilter: ['class', 'aria-hidden', 'open']
    });

    nav.addEventListener('click', function (event) {
      const link = event.target?.closest?.('a[href]') || null;
      if (!link) return;
      closeNav();
    });

    document.addEventListener('keydown', function (event) {
      if (!isOpen()) return;

      if (event.key === 'Escape') {
        event.preventDefault();
        if (close) close.click();
        else closeNav();
        return;
      }

      if (event.key !== 'Tab') return;
      const focusables = focusableElements(nav);
      if (!focusables.length) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];

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

  function resolveHubSpotScriptUrl() {
    if (config.hubspotScriptUrl) return String(config.hubspotScriptUrl);

    const portalId = String(config.hubspotPortalId || '').replace(/\D+/g, '');
    if (!portalId) return '';

    const region = String(config.hubspotRegion || 'eu1').replace(/[^a-z0-9-]/gi, '') || 'eu1';
    return 'https://js-' + region + '.hsforms.net/forms/embed/' + portalId + '.js';
  }

  function initLazyHubSpot() {
    const scriptUrl = resolveHubSpotScriptUrl();
    if (!scriptUrl) return;

    const modal = document.getElementById(config.modalId || 'nvx-valoracion-modal');
    const pageFrames = document.querySelectorAll(
      '.hs-form-frame[data-nvx-hubspot-lazy="1"], #nvx-hubspot-native-form .hs-form-frame, [data-nvx-hubspot-native="1"] .hs-form-frame'
    );
    const hasPageMount = config.hubspotPageMount !== false && pageFrames.length > 0;
    const hasModal = Boolean(config.modalEnabled && modal);

    if (!hasModal && !hasPageMount) return;

    let promise = null;

    function initializeForms() {
      if (modal) modal.classList.remove('nvx-valoracion-modal--embed-error');
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
        const scriptId = config.hubspotScriptId || 'nvx-hubspot-forms-runtime';
        const existing = document.getElementById(scriptId);
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

        const script = document.createElement('script');
        script.id = scriptId;
        script.src = scriptUrl;
        script.async = true;
        script.addEventListener('load', function () {
          script.dataset.nvxLoaded = '1';
          initializeForms();
          resolve();
        }, { once: true });
        script.addEventListener('error', function () {
          script.remove();
          if (modal) modal.classList.add('nvx-valoracion-modal--embed-error');
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

    if (hasModal) {
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
          const link = event.target?.closest?.('a') || null;
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

    if (hasPageMount) {
      // Dedicated form routes never ship an eager server-rendered HubSpot script.
      // Load on explicit intent (click/focus/CTA) or when the mount enters the viewport.
      let activated = false;
      const activate = function () {
        if (activated) return;
        activated = true;
        loadHubSpot();
      };

      pageFrames.forEach(function (frame) {
        const host =
          frame.closest('#nvx-hubspot-form, #nvx-hubspot-native-form, .nvx-hubspot-form-section, .nvx-form-stage') ||
          frame.parentElement ||
          frame;
        host.addEventListener('click', activate, { once: true, passive: true });
        host.addEventListener('focusin', activate, { once: true });

        if (typeof IntersectionObserver === 'function') {
          const observer = new IntersectionObserver(
            function (entries) {
              entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                  activate();
                  observer.disconnect();
                }
              });
            },
            { rootMargin: '120px 0px', threshold: 0.05 }
          );
          observer.observe(frame);
        }
      });

      document.addEventListener(
        'click',
        function (event) {
          const link = event.target?.closest?.('a[href*="#nvx-hubspot-form"]') || null;
          if (link) activate();
        },
        true
      );
    }
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
