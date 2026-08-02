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
      if (ham) ham.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeNav() {
      if (typeof mobileNav.close === 'function') {
        mobileNav.close();
      } else {
        mobileNav.removeAttribute('open');
      }
      if (ham) ham.setAttribute('aria-expanded', 'false');
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
  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
      }
    });
  });


})();
