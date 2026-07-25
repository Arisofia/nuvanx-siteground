(function () {
  'use strict';

  var formSection = document.getElementById('nvx-hubspot-form');
  if (!formSection) return;

  document.addEventListener(
    'click',
    function (event) {
      var trigger = event.target && event.target.closest
        ? event.target.closest('.nvx-open-valoracion-modal')
        : null;
      if (!trigger) return;

      event.preventDefault();
      event.stopPropagation();
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
