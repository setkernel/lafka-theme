/* lafka-theme/js/cart-drawer.js
 * Cart drawer slide-in + focus trap + WC fragment listener + cart-count sync.
 *
 * Listens to WC's added_to_cart jQuery event to auto-open the drawer
 * after AJAX add-to-cart. Also handles [data-lafka-cart-open] triggers
 * (e.g., header cart icon).
 *
 * Cart-count sync (f011): the header badge (header.php), the sticky-cart
 * count (template-parts/lafka-sticky-cart.php) and the drawer count pill
 * (partials/cart-drawer.php) all carry [data-lafka-cart-count] /
 * [data-lafka-cart-count-pill]. The plugin's add-to-cart fragments only
 * refresh the drawer item list + totals (ul.lafka-cart-drawer__items /
 * div.lafka-cart-drawer__total) — they never touch the count nodes — so
 * without this the badges stay at their server-rendered value (e.g. "0")
 * until a full page reload. We recompute the live item count from the
 * authoritative drawer item fragment and write it into every count node on
 * each cart change (quick-add AJAX included, since that fires added_to_cart).
 *
 * @since 5.16.0
 */
(function ($) {
  'use strict';
  if (!$) return;

  // -------------------------------------------------------------------------
  // Cart-count sync — keep every [data-lafka-cart-count] /
  // [data-lafka-cart-count-pill] node in step with the live cart.
  // -------------------------------------------------------------------------

  // Sum the per-item quantities (each rendered as "×N") across a node list of
  // .lafka-cart-drawer__qty elements.
  function countQtyNodes(qtyNodes) {
    var total = 0;
    Array.prototype.forEach.call(qtyNodes, function (node) {
      var digits = (node.textContent || '').replace(/[^0-9]/g, '');
      var n = parseInt(digits, 10);
      if (!isNaN(n)) { total += n; }
    });
    return total;
  }

  // Derive the live item count. On added_to_cart/removed_from_cart WC passes
  // the fragments object — parse the refreshed drawer item list from it so the
  // count is correct regardless of handler order (our listener can run before
  // WC has applied the fragments to the live DOM). The wc_fragments_* events
  // carry no fragments arg, but they fire AFTER WC has replaced the DOM nodes,
  // so the live-DOM read is accurate for them.
  function liveCartCount(fragments) {
    var itemsHtml = fragments && fragments['ul.lafka-cart-drawer__items'];
    if (itemsHtml) {
      // Parse in an inert document (no script execution, no thumbnail refetch).
      var doc = new DOMParser().parseFromString('<div>' + itemsHtml + '</div>', 'text/html');
      return countQtyNodes(doc.querySelectorAll('.lafka-cart-drawer__qty'));
    }
    return countQtyNodes(document.querySelectorAll('.lafka-cart-drawer__items .lafka-cart-drawer__qty'));
  }

  function writeCartCount(count) {
    var text = String(count);
    var nodes = document.querySelectorAll('[data-lafka-cart-count], [data-lafka-cart-count-pill]');
    Array.prototype.forEach.call(nodes, function (node) {
      node.textContent = text;
    });
  }

  function syncCartCount(event, fragments) {
    var itemsHtml = fragments && fragments['ul.lafka-cart-drawer__items'];
    // Skip when there's no authoritative source on this page (no fragment and
    // no drawer item list in the DOM) so we never clobber the correct
    // server-rendered count with a false 0.
    if (!itemsHtml && !document.querySelector('.lafka-cart-drawer__items')) {
      return;
    }
    writeCartCount(liveCartCount(fragments));
  }

  $(document.body).on(
    'added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded',
    syncCartCount
  );

  // -------------------------------------------------------------------------
  // Drawer slide-in + focus trap.
  // -------------------------------------------------------------------------
  var drawer = document.querySelector('.lafka-cart-drawer');
  if (!drawer) return;

  var lastFocus = null;

  // ---------------------------------------------------------------------------
  // Background isolation (f092 — a11y). The drawer behaves as a modal: it locks
  // body scroll, traps Tab and moves focus inside, and the partial declares
  // aria-modal="true". But it previously left the page behind the scrim in the
  // a11y tree, so a screen-reader virtual cursor could still browse the
  // obscured page (the scrim only blocks sighted users — WCAG 4.1.2 / 1.3.1).
  //
  // Mirror the mobile-nav fix: on open() remove the page wrappers from the a11y
  // tree + tab order via `inert` (with an aria-hidden fallback for older AT),
  // and restore them on close(). The drawer is injected at wp_footer
  // (functions.php) as a sibling of #header/#content, so inert-ing those
  // wrappers never disables the drawer. We must NOT inert document.body — the
  // drawer lives inside it.
  var BACKGROUND_SELECTORS = ['#header', '#content'];

  function setBackgroundInert(on) {
    BACKGROUND_SELECTORS.forEach(function (selector) {
      var node = document.querySelector(selector);
      if (!node) return;
      if (on) {
        node.setAttribute('inert', '');
        node.setAttribute('aria-hidden', 'true');
      } else {
        node.removeAttribute('inert');
        node.removeAttribute('aria-hidden');
      }
    });
  }

  function open() {
    lastFocus = document.activeElement;
    drawer.dataset.open = 'true';
    drawer.setAttribute('aria-hidden', 'false');
    setBackgroundInert(true);
    document.body.style.overflow = 'hidden';
    // Defer the focus move to the next task. Two synchronous forces would
    // otherwise steal focus straight back to <body>: (1) the drawer transitions
    // in from visibility:hidden, and an element that still computes as hidden
    // can't hold focus; (2) inerting #header — which contains the just-clicked
    // cart trigger — fires a blur the browser resolves after this handler
    // returns, overriding any focus() made in the same tick. A short timeout
    // lets the visible + inert state settle first, so focus reliably lands on
    // the drawer's first control. (requestAnimationFrame is unreliable here —
    // it is throttled when the page is not actively painting.) (WCAG 2.4.3)
    var f = drawer.querySelector('button, a, [tabindex="0"]');
    if (f && f.focus) {
      setTimeout(function () {
        if (drawer.dataset.open === 'true') { f.focus(); }
      }, 60);
    }
  }

  function close() {
    drawer.dataset.open = 'false';
    drawer.setAttribute('aria-hidden', 'true');
    // Restore the background to the a11y tree BEFORE returning focus to the
    // trigger — focus() on an inert ancestor (the header cart icon lives inside
    // #header) would otherwise be silently dropped.
    setBackgroundInert(false);
    document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest && e.target.closest('[data-lafka-cart-close]')) close();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.dataset.open === 'true') close();
    if (e.key === 'Tab' && drawer.dataset.open === 'true') {
      var focusables = Array.prototype.slice.call(drawer.querySelectorAll('button, a, [tabindex]:not([tabindex="-1"])'));
      var first = focusables[0];
      var last  = focusables[focusables.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  $(document.body).on('added_to_cart', open);

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest && e.target.closest('[data-lafka-cart-open]');
    if (trigger) { e.preventDefault(); open(); }
  });
})(window.jQuery);
