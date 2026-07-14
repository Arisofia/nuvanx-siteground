(function(){
  'use strict';

  /* --- Hamburger / nav móvil --- */
  var ham = document.getElementById('nvx-hamburger-btn');
  var mobileNav = document.getElementById('nvx-mobile-nav');
  var closeBtn = document.getElementById('nvx-mobile-close');
  if (ham && mobileNav) {
    ham.addEventListener('click', function() {
      var isOpen = mobileNav.classList.toggle('is-open');
      ham.setAttribute('aria-expanded', isOpen);
      mobileNav.setAttribute('aria-hidden', !isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
    if (closeBtn) closeBtn.addEventListener('click', function() {
      mobileNav.classList.remove('is-open');
      ham.setAttribute('aria-expanded', 'false');
      mobileNav.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    });
  }

  /* --- FAQ Accordion --- */
  var faqItems = document.querySelectorAll('.nvx-faq__item');
  faqItems.forEach(function(item) {
    var q = item.querySelector('.nvx-faq__question');
    if (!q) return;
    q.addEventListener('click', function() {
      var isOpen = item.classList.toggle('is-open');
      q.setAttribute('aria-expanded', isOpen);
    });
  });

  /* --- Smooth scroll en anclas --- */
  document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      var target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

})()