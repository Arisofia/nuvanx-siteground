(function () {
  'use strict';

  /* --- Hamburger / nav móvil --- */
  var ham = document.getElementById('nvx-hamburger-btn');
  var mobileNav = document.getElementById('nvx-mobile-nav');
  var closeBtn = document.getElementById('nvx-mobile-close');
  if (ham && mobileNav) {
    ham.addEventListener('click', function () {
      var isOpen = mobileNav.classList.toggle('is-open');
      ham.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      mobileNav.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        mobileNav.classList.remove('is-open');
        ham.setAttribute('aria-expanded', 'false');
        mobileNav.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      });
    }
  }

  /* FAQ: native <details>/<summary> (.nvx-faq / .nvx-brand-faq-*) — no JS. */

  /* --- Smooth scroll en anclas --- */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var href = a.getAttribute('href');
      if (!href || href === '#') return;
      var target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* --- Valoración modal (header / mobile / CTAs) --- */
  (function initValoracionModal() {
    var cfg = window.nvxValoracionModal;
    if (!cfg || cfg.enabled === false) return;

    var modal = document.getElementById('nvx-valoracion-modal');
    if (!modal) return;

    var lastFocus = null;
    // Canonical full-page fallback (also used when matching CTA hrefs).
    var pageUrl = (cfg.pageUrl || '/madrid/valoracion/').replace(/\/?$/, '/');

    function normalizePath(pathname) {
      return (pathname || '').replace(/\/+$/, '') + '/';
    }

    // Parsed once at init — avoid new URL() on every CTA click.
    var pagePath = normalizePath('/');
    try {
      pagePath = normalizePath(new URL(pageUrl, window.location.origin).pathname);
    } catch (err) {
      pagePath = '/madrid/valoracion/';
    }

    function isValoracionHref(href) {
      if (!href) return false;
      try {
        var u = new URL(href, window.location.origin);
        var path = normalizePath(u.pathname);
        if (path === pagePath) return true;
        return (
          path.indexOf('/madrid/valoracion/') !== -1 ||
          path.indexOf('/valoracion/') !== -1 ||
          path === '/consulta-medica/' ||
          path === '/consultamedica/'
        );
      } catch (err) {
        return /valoraci[oó]n|consulta-medica|consultamedica/i.test(href);
      }
    }

    function closeMobileNav() {
      if (!mobileNav || !mobileNav.classList.contains('is-open')) return;
      mobileNav.classList.remove('is-open');
      if (ham) ham.setAttribute('aria-expanded', 'false');
      mobileNav.setAttribute('aria-hidden', 'true');
    }

    /** Single source of truth: .is-open class (hidden/aria stay in sync here only). */
    function setModalOpen(open) {
      if (!modal) return;
      if (open) {
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('nvx-valoracion-modal-open');
        document.body.style.overflow = 'hidden';
      } else {
        modal.hidden = true;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nvx-valoracion-modal-open');
        if (!mobileNav || !mobileNav.classList.contains('is-open')) {
          document.body.style.overflow = '';
        }
      }
    }

    function isModalOpen() {
      return !!(modal && modal.classList.contains('is-open'));
    }

    function openModal(trigger) {
      if (!modal) return;
      lastFocus = trigger || document.activeElement;
      closeMobileNav();
      setModalOpen(true);

      var closeEl = modal.querySelector('[data-nvx-valoracion-modal-close]');
      var focusTarget =
        modal.querySelector('.hs-form input, .hs-form select, .hs-form textarea, .hs-input') ||
        modal.querySelector('.nvx-valoracion-modal__close') ||
        closeEl;
      if (focusTarget && typeof focusTarget.focus === 'function') {
        window.setTimeout(function () {
          focusTarget.focus();
        }, 50);
      }

      try {
        if (window.HubSpotForms && typeof window.HubSpotForms.initialize === 'function') {
          window.HubSpotForms.initialize();
        }
      } catch (e) {
        /* ignore */
      }
    }

    function closeModal() {
      if (!modal) return;
      setModalOpen(false);
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
      lastFocus = null;
    }

    function shouldIntercept(el) {
      if (!el || el.tagName !== 'A') return false;
      if (el.getAttribute('data-nvx-valoracion-modal') === '0') return false;
      if (el.classList.contains('nvx-open-valoracion-modal')) return true;
      if (el.getAttribute('data-nvx-valoracion-modal') === '1') return true;
      if (el.id === 'nvx-header-cta' || el.id === 'nvx-footer-cta' || el.id === 'nvx-mobile-cta') {
        return true;
      }
      var href = el.getAttribute('href') || '';
      if (!isValoracionHref(href)) return false;
      // Only intercept primary conversion CTAs, not plain footer/nav text links.
      var cls = el.className || '';
      if (
        /\bnvx-(btn|button|brand-btn)\b/.test(cls) ||
        el.closest('.nvx-cta-banner, .nvx-brand-actions, .nvx-home-hero-ctas, .nvx-cta-pair, .nvx-home-action-banner')
      ) {
        return true;
      }
      return false;
    }

    document.addEventListener(
      'click',
      function (e) {
        var a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (!shouldIntercept(a)) return;
        e.preventDefault();
        e.stopPropagation();
        openModal(a);
      },
      true
    );

    modal.addEventListener('click', function (e) {
      var t = e.target;
      if (t && t.closest && t.closest('[data-nvx-valoracion-modal-close]')) {
        e.preventDefault();
        closeModal();
      }
    });

    // One keyboard handler: Escape close + Tab focus trap.
    document.addEventListener(
      'keydown',
      function (e) {
        if (!isModalOpen()) return;

        if (e.key === 'Escape') {
          e.preventDefault();
          closeModal();
          return;
        }

        if (e.key !== 'Tab') return;

        var focusables = modal.querySelectorAll(
          'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
        );
        if (!focusables.length) return;
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      },
      true
    );

    window.nvxOpenValoracionModal = function () {
      openModal(document.activeElement);
    };
    window.nvxCloseValoracionModal = closeModal;
  })();
})();
