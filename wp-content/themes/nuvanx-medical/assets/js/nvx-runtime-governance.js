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

  /**
   * A11y layer only. Open/close and class/aria state belong to nvx-main.js
   * (same DOM ids). This module keeps inert + focus trap + Escape in sync
   * without re-implementing the menu controller.
   */
  function initMobileNavigationGovernance() {
    const nav = document.getElementById(config.mobileNavId || 'nvx-mobile-nav');
    const trigger = document.getElementById('nvx-hamburger-btn');
    const close = document.getElementById('nvx-mobile-close');
    if (!nav || !trigger) return;

    let wasOpen = false;

    function isOpen() {
      return nav.classList.contains('is-open') || nav.hasAttribute('open');
    }

    /** Prefer main.js close handler so body overflow / aria stay consistent. */
    function requestClose() {
      if (close && typeof close.click === 'function') {
        close.click();
        return;
      }
      setInert(nav, true);
    }

    function synchronizeA11y() {
      const open = isOpen();
      const focusWasInside = nav.contains(document.activeElement);
      setInert(nav, !open);

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

    // Clear inert before main.js opens the drawer (main runs on bubble phase).
    trigger.addEventListener(
      'click',
      function () {
        if (!isOpen()) setInert(nav, false);
      },
      true
    );

    new MutationObserver(synchronizeA11y).observe(nav, {
      attributes: true,
      attributeFilter: ['class', 'open']
    });

    nav.addEventListener('click', function (event) {
      const link = event.target?.closest?.('a[href]') || null;
      if (!link) return;
      requestClose();
    });

    document.addEventListener('keydown', function (event) {
      if (!isOpen()) return;

      if (event.key === 'Escape') {
        event.preventDefault();
        requestClose();
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

  function initValoracionModalGovernance() {
    const cfg = window.nvxValoracionModal || {};
    if (cfg.enabled === false) return;

    const modal = document.getElementById(cfg.modalId || 'nvx-valoracion-modal');
    if (!modal || typeof modal.showModal !== 'function') return;

    let lastFocus = null;
    const DEFAULT_VALORACION_PATH = '/madrid/valoracion/';
    const pageUrl = (cfg.pageUrl || DEFAULT_VALORACION_PATH).replace(/\/?$/, '/');

    function normalizePath(pathname) {
      let normalized = pathname || '';
      while (normalized.endsWith('/')) normalized = normalized.slice(0, -1);
      return normalized + '/';
    }

    let pagePath;
    try {
      pagePath = normalizePath(new URL(pageUrl, window.location.origin).pathname);
    } catch (_err) {
      pagePath = normalizePath(DEFAULT_VALORACION_PATH);
    }

    function isValoracionHref(href) {
      if (!href) return false;
      try {
        const u = new URL(href, window.location.origin);
        const path = normalizePath(u.pathname);
        if (path === pagePath) return true;
        return (
          path.indexOf(DEFAULT_VALORACION_PATH) !== -1 ||
          path.indexOf('/valoracion/') !== -1 ||
          path === '/consulta-medica/' ||
          path === '/consultamedica/'
        );
      } catch (_err) {
        return /valoraci[oó]n|consulta-medica|consultamedica/i.test(href);
      }
    }

    function closeMobileNav() {
      const mobileNav = document.getElementById(config.mobileNavId || 'nvx-mobile-nav');
      if (mobileNav && document.getElementById('nvx-mobile-close')) {
        const closeBtn = document.getElementById('nvx-mobile-close');
        if (mobileNav.classList.contains('is-open') || mobileNav.hasAttribute('open')) {
           closeBtn.click();
        }
      }
    }

    function openModal(trigger) {
      if (!modal) return;
      lastFocus = trigger || document.activeElement;
      closeMobileNav();
      modal.showModal();
      document.body.classList.add('nvx-valoracion-modal-open');
      document.body.style.overflow = 'hidden';

      // HubSpot initialization is handled by initLazyHubSpot via MutationObserver
    }

    function closeModal() {
      if (!modal) return;
      modal.close();
      document.body.classList.remove('nvx-valoracion-modal-open');
      document.body.style.overflow = '';
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
      lastFocus = null;
    }

    function shouldIntercept(el) {
      if (!el || el.tagName !== 'A') return false;
      if (el.dataset.nvxValoracionModal === '0') return false;
      if (el.classList.contains('nvx-open-valoracion-modal')) return true;
      if (el.dataset.nvxValoracionModal === '1') return true;
      if (el.id === 'nvx-header-cta' || el.id === 'nvx-footer-cta' || el.id === 'nvx-mobile-cta') return true;
      
      const href = el.getAttribute('href') || '';
      if (!isValoracionHref(href)) return false;
      const cls = el.className || '';
      if (
        /\bnvx-(btn|button|brand-btn)\b/.test(cls) ||
        el.closest('.nvx-cta-banner, .nvx-brand-actions, .nvx-home-hero-ctas, .nvx-cta-pair, .nvx-home-action-banner')
      ) {
        return true;
      }
      return false;
    }

    document.addEventListener('click', function (e) {
      const a = e.target && e.target.closest ? e.target.closest('a') : null;
      if (!shouldIntercept(a)) return;
      e.preventDefault();
      e.stopPropagation();
      openModal(a);
    }, true);

    modal.addEventListener('click', function (e) {
      // Close when clicking on backdrop or close button
      const isBackdrop = e.target.classList && e.target.classList.contains('nvx-valoracion-modal__backdrop');
      const isCloseBtn = e.target.closest && e.target.closest('[data-nvx-valoracion-modal-close]');
      if (isBackdrop || isCloseBtn) {
        e.preventDefault();
        closeModal();
      }
    });
    
    modal.addEventListener('close', function() {
       closeModal();
    });

    window.nvxOpenValoracionModal = function () {
      openModal(document.activeElement);
    };
    window.nvxCloseValoracionModal = closeModal;
  }

  /**
   * Resolves the HubSpot forms script URL from configuration or an embedded form frame.
   * @return {string} The configured or derived HubSpot script URL, or an empty string when no valid portal ID is available.
   */
  function resolveHubSpotScriptUrl() {
    if (config.hubspotScriptUrl) return String(config.hubspotScriptUrl);

    const frame = document.querySelector('.hs-form-frame[data-portal-id]');
    if (!frame && !config.hubspotPortalId) return '';

    const regionStr = config.hubspotRegion || (frame ? frame.dataset.region : 'eu1');
    const region = String(regionStr || 'eu1').replace(/[^a-z0-9-]/gi, '') || 'eu1';
    return 'https://js-' + region + '.hsforms.net/forms/v2.js';
  }

  /**
   * Lazily loads HubSpot Forms when an eligible modal or page form mount is activated.
   *
   * Reuses an existing or in-progress script load, initializes available forms after loading,
   * and retries after load failures.
   */
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
      
      if (window.hbspt && window.hbspt.forms && typeof window.hbspt.forms.create === 'function') {
        const frames = document.querySelectorAll('.hs-form-frame[data-form-id][data-portal-id]');
        frames.forEach(function(frame, index) {
          if (frame.dataset.hsInitialized === '1') return;

          // HubSpot Forms v2 expects `target` to be a CSS selector. Passing the
          // DOM node itself causes HubSpot to fall back to an out-of-place mount
          // (observed at the end of /madrid/valoracion/). Give every canonical
          // frame a deterministic, collision-safe ID and target that selector.
          if (!frame.id) {
            let suffix = index + 1;
            let frameId = 'nvx-hubspot-frame-' + suffix;
            while (document.getElementById(frameId) && document.getElementById(frameId) !== frame) {
              suffix += 1;
              frameId = 'nvx-hubspot-frame-' + suffix;
            }
            frame.id = frameId;
          }

          frame.dataset.hsInitialized = '1';

          try {
            window.hbspt.forms.create({
              region: frame.dataset.region || config.hubspotRegion || 'eu1',
              portalId: frame.dataset.portalId || config.hubspotPortalId,
              formId: frame.dataset.formId || config.hubspotFormId,
              target: '#' + frame.id
            });
          } catch (_err) {
            delete frame.dataset.hsInitialized;
            if (modal) modal.classList.add('nvx-valoracion-modal--embed-error');
          }
        });
      }
    }

    function loadHubSpot() {
      if (window.hbspt && window.hbspt.forms) {
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
        return modal.hasAttribute('open');
      }

      new MutationObserver(function () {
        if (modalIsOpen()) loadHubSpot();
      }).observe(modal, {
        attributes: true,
        attributeFilter: ['open']
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
    initValoracionModalGovernance();
    initLazyHubSpot();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }
})();
