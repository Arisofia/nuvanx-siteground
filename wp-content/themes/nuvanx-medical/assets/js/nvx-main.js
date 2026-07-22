(function () {
  'use strict';

  var ham = document.getElementById('nvx-hamburger-btn');
  var mobileNav = document.getElementById('nvx-mobile-nav');
  var closeBtn = document.getElementById('nvx-mobile-close');
  var mobileAccordionItems = [];
  var mobileNavLastFocus = null;
  var desktopMedia = window.matchMedia('(min-width: 80em)');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  /**
   * Finds the first direct child with the specified CSS class.
   * @param {Element} element - The parent element to search.
   * @param {string} className - The CSS class to match.
   * @return {Element|null} The matching child element, or `null` if none is found.
   */
  function directChildByClass(element, className) {
    if (!element || !element.children) return null;
    for (var i = 0; i < element.children.length; i += 1) {
      if (element.children[i].classList.contains(className)) return element.children[i];
    }
    return null;
  }

  /**
   * Finds the first direct child anchor of an element.
   * @param {Element|null} element - The element whose direct children to search.
   * @return {HTMLAnchorElement|null} The first direct child anchor, or `null` if none exists.
   */
  function directChildLink(element) {
    if (!element || !element.children) return null;
    for (var i = 0; i < element.children.length; i += 1) {
      if (element.children[i].tagName === 'A') return element.children[i];
    }
    return null;
  }

  /**
   * Updates a mobile submenu's visibility and transition state.
   * @param {HTMLElement|null} submenu - The submenu element to update.
   * @param {boolean} open - Whether the submenu should be open.
   */
  function animateMobileSubmenu(submenu, open) {
    if (!submenu) return;

    if (submenu.nvxAnimation && typeof submenu.nvxAnimation.cancel === 'function') {
      submenu.nvxAnimation.cancel();
      submenu.nvxAnimation = null;
    }

    if (reduceMotion.matches || typeof submenu.animate !== 'function') {
      submenu.hidden = !open;
      submenu.setAttribute('aria-hidden', open ? 'false' : 'true');
      return;
    }

    if (open) {
      submenu.hidden = false;
      submenu.setAttribute('aria-hidden', 'false');
      submenu.nvxAnimation = submenu.animate(
        [
          { height: '0px', opacity: 0, transform: 'translateY(-0.5rem)' },
          { height: submenu.scrollHeight + 'px', opacity: 1, transform: 'translateY(0)' },
        ],
        { duration: 180, easing: 'ease-out' }
      );
      submenu.nvxAnimation.addEventListener(
        'finish',
        function () {
          submenu.nvxAnimation = null;
        },
        { once: true }
      );
      return;
    }

    submenu.setAttribute('aria-hidden', 'true');
    submenu.nvxAnimation = submenu.animate(
      [
        { height: submenu.scrollHeight + 'px', opacity: 1, transform: 'translateY(0)' },
        { height: '0px', opacity: 0, transform: 'translateY(-0.5rem)' },
      ],
      { duration: 150, easing: 'ease-in' }
    );
    submenu.nvxAnimation.addEventListener(
      'finish',
      function () {
        submenu.hidden = true;
        submenu.nvxAnimation = null;
      },
      { once: true }
    );
  }

  /**
   * Updates the expanded state and accessibility attributes of a mobile navigation accordion.
   * @param {Object} entry - The accordion entry to update.
   * @param {boolean} open - Whether to expand the accordion.
   * @param {boolean} closeSiblings - Whether to close expanded sibling accordions.
   */
  function setMobileAccordionState(entry, open, closeSiblings) {
    if (!entry) return;

    if (open && closeSiblings && entry.item.parentElement) {
      mobileAccordionItems.forEach(function (candidate) {
        if (
          candidate !== entry &&
          candidate.item.parentElement === entry.item.parentElement &&
          candidate.item.classList.contains('is-expanded')
        ) {
          setMobileAccordionState(candidate, false, false);
        }
      });
    }

    entry.item.classList.toggle('is-expanded', open);
    entry.button.setAttribute('aria-expanded', open ? 'true' : 'false');
    entry.button.setAttribute(
      'aria-label',
      (open ? 'Cerrar' : 'Abrir') + ' submenú de ' + entry.label
    );
    animateMobileSubmenu(entry.submenu, open);
  }

  /**
   * Resets all mobile navigation accordions to their collapsed state.
   */
  function resetMobileAccordions() {
    mobileAccordionItems.forEach(function (entry) {
      entry.item.classList.remove('is-expanded');
      entry.button.setAttribute('aria-expanded', 'false');
      entry.button.setAttribute('aria-label', 'Abrir submenú de ' + entry.label);
      if (entry.submenu.nvxAnimation && typeof entry.submenu.nvxAnimation.cancel === 'function') {
        entry.submenu.nvxAnimation.cancel();
        entry.submenu.nvxAnimation = null;
      }
      entry.submenu.hidden = true;
      entry.submenu.setAttribute('aria-hidden', 'true');
    });
  }

  /**
   * Opens or closes the mobile navigation and manages its focus and document scrolling state.
   * @param {boolean} open - Whether to open the mobile navigation.
   * @param {boolean} restoreFocus - Whether to return focus to the element that was focused before opening.
   */
  function setMobileNavOpen(open, restoreFocus) {
    if (!mobileNav) return;

    mobileNav.classList.toggle('is-open', open);
    mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (ham) ham.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
      mobileNavLastFocus = document.activeElement;
      window.setTimeout(function () {
        if (closeBtn && typeof closeBtn.focus === 'function') closeBtn.focus();
      }, 20);
      return;
    }

    resetMobileAccordions();
    if (restoreFocus && mobileNavLastFocus && typeof mobileNavLastFocus.focus === 'function') {
      mobileNavLastFocus.focus();
    }
    mobileNavLastFocus = null;
  }

  /**
   * Initialize mobile navigation accordions for menu items with submenus.
   */
  function initMobileAccordions() {
    if (!mobileNav) return;
    var menu = mobileNav.querySelector('.nvx-mobile-nav__list');
    if (!menu) return;

    menu.querySelectorAll('.menu-item-has-children').forEach(function (item, index) {
      if (item.dataset.nvxMobileAccordion === 'ready') return;

      var submenu = directChildByClass(item, 'sub-menu');
      var link = directChildLink(item);
      if (!submenu) return;

      var label = link ? link.textContent.trim() : 'esta sección';
      var submenuId = submenu.id || 'nvx-mobile-submenu-' + index;
      submenu.id = submenuId;
      submenu.hidden = true;
      submenu.setAttribute('aria-hidden', 'true');

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'nvx-mobile-nav__toggle';
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-controls', submenuId);
      button.setAttribute('aria-label', 'Abrir submenú de ' + label);
      button.innerHTML = '<span class="nvx-mobile-nav__toggle-icon" aria-hidden="true"></span>';

      submenu.before(button);
      item.dataset.nvxMobileAccordion = 'ready';

      var entry = {
        item: item,
        submenu: submenu,
        button: button,
        label: label,
      };
      mobileAccordionItems.push(entry);

      button.addEventListener('click', function () {
        setMobileAccordionState(entry, !item.classList.contains('is-expanded'), true);
      });

      if (link) {
        var href = (link.getAttribute('href') || '').trim();
        if (!href || href === '#') {
          link.addEventListener('click', function (event) {
            event.preventDefault();
            setMobileAccordionState(entry, !item.classList.contains('is-expanded'), true);
          });
        }
      }
    });
  }

  initMobileAccordions();

  if (ham && mobileNav) {
    ham.addEventListener('click', function () {
      setMobileNavOpen(!mobileNav.classList.contains('is-open'), true);
    });
  }

  if (closeBtn && mobileNav) {
    closeBtn.addEventListener('click', function () {
      setMobileNavOpen(false, true);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && mobileNav && mobileNav.classList.contains('is-open')) {
      event.preventDefault();
      setMobileNavOpen(false, true);
    }
  });

  /**
   * Closes the mobile navigation when the desktop breakpoint becomes active.
   * @param {MediaQueryListEvent} event - The media query change event.
   */
  function closeMobileOnDesktop(event) {
    if (event.matches && mobileNav && mobileNav.classList.contains('is-open')) {
      setMobileNavOpen(false, false);
    }
  }

  if (typeof desktopMedia.addEventListener === 'function') {
    desktopMedia.addEventListener('change', closeMobileOnDesktop);
  } else if (typeof desktopMedia.addListener === 'function') {
    desktopMedia.addListener(closeMobileOnDesktop);
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
    var DEFAULT_VALORACION_PATH = '/madrid/valoracion/';
    var pageUrl = (cfg.pageUrl || DEFAULT_VALORACION_PATH).replace(/\/?$/, '/');

    /**
     * Normalizes a pathname to include exactly one trailing slash.
     * @param {string} pathname - The pathname to normalize.
     * @return {string} The normalized pathname.
     */
    function normalizePath(pathname) {
      return (pathname || '').replace(/\/+$/, '') + '/';
    }

    var pagePath;
    try {
      pagePath = normalizePath(new URL(pageUrl, window.location.origin).pathname);
    } catch (err) {
      pagePath = normalizePath(DEFAULT_VALORACION_PATH);
    }

    function isValoracionHref(href) {
      if (!href) return false;
      try {
        var u = new URL(href, window.location.origin);
        var path = normalizePath(u.pathname);
        if (path === pagePath) return true;
        return (
          path.indexOf(DEFAULT_VALORACION_PATH) !== -1 ||
          path.indexOf('/valoracion/') !== -1 ||
          path === '/consulta-medica/' ||
          path === '/consultamedica/'
        );
      } catch (err) {
        return /valoraci[oó]n|consulta-medica|consultamedica/i.test(href);
      }
    }

    /**
     * Closes the mobile navigation when it is open.
     */
    function closeMobileNav() {
      if (!mobileNav || !mobileNav.classList.contains('is-open')) return;
      setMobileNavOpen(false, false);
    }

    /**
     * Updates the valoración modal visibility and related document state.
     * @param {boolean} open - Whether to show the modal.
     */
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

    /**
     * Determines whether an anchor should open the valoración modal.
     * @param {Element} el - The element to evaluate.
     * @returns {boolean} `true` if the anchor matches the modal interception criteria, `false` otherwise.
     */
    function shouldIntercept(el) {
      if (!el || el.tagName !== 'A') return false;
      if (el.dataset.nvxValoracionModal === '0') return false;
      if (el.classList.contains('nvx-open-valoracion-modal')) return true;
      if (el.dataset.nvxValoracionModal === '1') return true;
      if (el.id === 'nvx-header-cta' || el.id === 'nvx-footer-cta' || el.id === 'nvx-mobile-cta') {
        return true;
      }
      var href = el.getAttribute('href') || '';
      if (!isValoracionHref(href)) return false;
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
