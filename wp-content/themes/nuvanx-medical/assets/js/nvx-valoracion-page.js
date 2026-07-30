(function () {
  'use strict';

  var formSection = document.getElementById('nvx-hubspot-form');
  if (!formSection) return;

  var triggerSelector = [
    '#nvx-header-cta',
    '#nvx-mobile-cta',
    'a[href="#nvx-hubspot-form"]',
    '.nvx-open-valoracion-modal',
  ].join(', ');

  function closeMobileNav() {
    var mobileNav = document.getElementById('nvx-mobile-nav');
    if (!mobileNav || !mobileNav.classList.contains('is-open')) return;

    var closeButton = document.getElementById('nvx-mobile-close');
    if (closeButton && typeof closeButton.click === 'function') {
      closeButton.click();
      return;
    }

    mobileNav.classList.remove('is-open');
    mobileNav.setAttribute('aria-hidden', 'true');
    var hamburger = document.getElementById('nvx-hamburger-btn');
    if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  document.addEventListener(
    'click',
    function (event) {
      var trigger = event.target && event.target.closest
        ? event.target.closest(triggerSelector)
        : null;
      if (!trigger) return;

      var href = trigger.getAttribute('href') || '';
      if (
        trigger.id !== 'nvx-header-cta' &&
        trigger.id !== 'nvx-mobile-cta' &&
        href !== '#nvx-hubspot-form' &&
        !trigger.classList.contains('nvx-open-valoracion-modal')
      ) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      closeMobileNav();
      formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

      var focusTarget = formSection.querySelector('input, select, textarea, button, a[href]');
      if (focusTarget && typeof focusTarget.focus === 'function') {
        window.setTimeout(function () {
          focusTarget.focus({ preventScroll: true });
        }, 450);
      }
    },
    true
  );
})();
