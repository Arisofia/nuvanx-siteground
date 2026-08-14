(function () {
  'use strict';

  /* --- Hamburger / nav móvil --- */
  var ham = document.getElementById('nvx-hamburger-btn');
  var mobileNav = document.getElementById('nvx-mobile-nav');
  var closeBtn = document.getElementById('nvx-mobile-close');

  if (mobileNav && mobileNav instanceof HTMLDialogElement) {
    function openNav() {
      if (typeof mobileNav.showModal === 'function') {
        mobileNav.showModal();
      } else {
        mobileNav.setAttribute('open', 'open');
      }
      if (ham) {
        ham.setAttribute('aria-expanded', 'true');
        ham.setAttribute('aria-label', 'Cerrar menú');
      }
      document.body.style.overflow = 'hidden';
    }

    function closeNav() {
      if (typeof mobileNav.close === 'function') {
        mobileNav.close();
      } else {
        mobileNav.removeAttribute('open');
      }
      if (ham) {
        ham.setAttribute('aria-expanded', 'false');
        ham.setAttribute('aria-label', 'Abrir menú');
      }
      if (!document.body.classList.contains('nvx-valoracion-modal-open')) {
        document.body.style.overflow = '';
      }
    }

    if (ham) {
      ham.addEventListener('click', openNav);
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', closeNav);
    }
    mobileNav.addEventListener('cancel', closeNav);
  }

  /* FAQ: native <details>/<summary> (.nvx-faq / .nvx-brand-faq-*) — no JS. */

  /* --- Smooth scroll en anclas --- */
  var prefersReducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;

  var heroVideo = document.getElementById('nvx-home-hero-video');
  if (heroVideo && prefersReducedMotion) {
    heroVideo.pause();
    heroVideo.removeAttribute('autoplay');
  }

  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var href = a.getAttribute('href');
      if (!href || href === '#') return;
      var targetId = href.slice(1);
      var target = document.getElementById(targetId);
      if (target) {
        e.preventDefault();
        if (prefersReducedMotion) {
          target.scrollIntoView();
        } else {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        // Move keyboard/AT focus to the target (critical for the skip-link:
        // preventDefault() above stops the browser's native fragment-focus
        // behavior, so it must be restored manually here).
        if (typeof target.focus === 'function') {
          target.focus({ preventScroll: true });
        }
      }
    });
  });

  /* --- Footer Desktop <details> Fix --- */
  var footerCols = document.querySelectorAll('.nvx-footer__col');
  if (footerCols.length > 0) {
    // Columns are rendered <details open> so desktop stays navigable without JS.
    // On desktop keep them open; on mobile collapse them so the native accordion
    // starts closed. If JS never runs, mobile degrades to open (content visible)
    // rather than desktop hiding content behind a non-clickable summary.
    var footerMql = window.matchMedia('(min-width: 641px)');
    var handleFooterResize = function (e) {
      footerCols.forEach(function (col) {
        if (e.matches) {
          col.setAttribute('open', '');
        } else {
          col.removeAttribute('open');
        }
      });
    };
    footerMql.addEventListener('change', handleFooterResize);
    handleFooterResize(footerMql);

    footerCols.forEach(function (col) {
      var summary = col.querySelector('summary');
      if (summary) {
        summary.addEventListener('click', function (e) {
          if (window.innerWidth > 640) e.preventDefault();
        });
        summary.addEventListener('keydown', function (e) {
          if (window.innerWidth > 640 && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
          }
        });
      }
    });
  }

  /* --- Complianz Accessible Name Sanitizer (WCAG 2.4.4 / 4.1.2) --- */
  function sanitizeComplianzAccessibleNames() {
    var banners = document.querySelectorAll(
      '.cmplz-cookiebanner, #cmplz-cookiebanner-container, .cmplz-banner, .cmplz-manage-consent-container'
    );
    if (!banners || banners.length === 0) return;

    banners.forEach(function (banner) {
      var links = banner.querySelectorAll('a, button, [role="button"]');
      links.forEach(function (el) {
        var text = (el.textContent || '').trim();
        var ariaLabel = el.getAttribute('aria-label') || '';
        var href = el.getAttribute('href') || '';

        if (
          text === '{title}' ||
          ariaLabel === '{title}' ||
          text.indexOf('{title}') !== -1 ||
          ariaLabel.indexOf('{title}') !== -1
        ) {
          var fallback = 'Política de cookies';
          if (href.indexOf('privacidad') !== -1 || href.indexOf('privacy') !== -1) {
            fallback = 'Política de privacidad';
          } else if (href.indexOf('aviso-legal') !== -1 || href.indexOf('legal') !== -1) {
            fallback = 'Aviso legal';
          }

          if (text === '{title}' || text.indexOf('{title}') !== -1) {
            el.textContent = text.replace(/\{title\}/g, fallback);
          }
          if (ariaLabel === '{title}' || ariaLabel.indexOf('{title}') !== -1) {
            el.setAttribute('aria-label', ariaLabel.replace(/\{title\}/g, fallback));
          }
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', sanitizeComplianzAccessibleNames);
  } else {
    sanitizeComplianzAccessibleNames();
  }
  window.addEventListener('load', sanitizeComplianzAccessibleNames);

  if (typeof MutationObserver === 'function') {
    var complianzObserver = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
          sanitizeComplianzAccessibleNames();
          break;
        }
      }
    });
    if (document.body) {
      complianzObserver.observe(document.body, { childList: true, subtree: true });
    }
  }
})();
