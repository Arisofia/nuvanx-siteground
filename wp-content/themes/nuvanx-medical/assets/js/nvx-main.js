(function () {
  'use strict';

  /* --- Hamburger / nav móvil --- */
  var ham = document.getElementById('nvx-hamburger-btn');
  var mobileNav = document.getElementById('nvx-mobile-nav');
  var closeBtn = document.getElementById('nvx-mobile-close');

  function setMobileNavOpen(willOpen) {
    if (!mobileNav) return;
    if (willOpen) {
      mobileNav.removeAttribute('inert');
      mobileNav.classList.add('is-open');
      mobileNav.setAttribute('open', '');
      mobileNav.setAttribute('aria-hidden', 'false');
    } else {
      mobileNav.classList.remove('is-open');
      mobileNav.removeAttribute('open');
      mobileNav.setAttribute('aria-hidden', 'true');
      mobileNav.setAttribute('inert', '');
    }
    if (ham) ham.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    // Keep scroll lock if the valoración modal is open.
    if (willOpen) {
      document.body.style.overflow = 'hidden';
    } else if (!document.body.classList.contains('nvx-valoracion-modal-open')) {
      document.body.style.overflow = '';
    }
  }

  if (ham && mobileNav) {
    ham.addEventListener('click', function () {
      var isCurrentlyOpen =
        mobileNav.classList.contains('is-open') || mobileNav.hasAttribute('open');
      setMobileNavOpen(!isCurrentlyOpen);
    });
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        setMobileNavOpen(false);
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


})();
