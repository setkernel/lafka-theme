/* lafka-theme/js/order-method.js
 * Method-switch modal — DOM construction via createElement to avoid innerHTML.
 * The choice itself goes through lafka.fulfilment.set() (js/lafka-fulfilment.js),
 * the one writer of the lafka_order_method cookie; it updates the bar in place.
 *
 * @since 5.16.0
 */
(function () {
  'use strict';

  function setMethod(method) {
    if (window.lafka && window.lafka.fulfilment) {
      window.lafka.fulfilment.set(method);
    }
  }

  function el(tag, attrs, text) {
    const node = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === 'class') { node.className = attrs[k]; }
        else { node.setAttribute(k, attrs[k]); }
      });
    }
    if (text != null) { node.textContent = text; }
    return node;
  }

  function buildModal() {
    const overlay = el('div', { 'class': 'lafka-method-modal__overlay' });
    const modal   = el('div', { 'class': 'lafka-method-modal', role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'lafka-method-modal-title' });
    modal.appendChild(el('h3', { id: 'lafka-method-modal-title' }, 'How are you getting your order?'));

    // Operator-specific labels come from wp_localize_script (resolver-backed).
    // Never hardcode literals — lafka-theme is public OSS. Falls back to a
    // generic label if the localized data is missing.
    const localized   = (typeof window.lafkaOrderMethodLabels === 'object') ? window.lafkaOrderMethodLabels : {};
    const pickupAddr  = (localized.pickupLabel || '').trim();
    const pickupText  = pickupAddr ? ('🏪  Pickup at ' + pickupAddr) : '🏪  Pickup';
    const btnDelivery = el('button', { type: 'button', 'data-method': 'delivery' }, '🚚  Delivery');
    const btnPickup   = el('button', { type: 'button', 'data-method': 'pickup' },   pickupText);
    const btnClose    = el('button', { type: 'button', 'class': 'lafka-method-modal__close', 'aria-label': 'Close' }, '×');

    modal.appendChild(btnDelivery);
    modal.appendChild(btnPickup);
    modal.appendChild(btnClose);
    overlay.appendChild(modal);
    return overlay;
  }

  function openModal() {
    const overlay = buildModal();
    document.body.appendChild(overlay);

    function closeModal() {
      overlay.remove();
      document.removeEventListener('keydown', onEsc);
    }

    overlay.addEventListener('click', function (e) {
      const m = e.target.getAttribute && e.target.getAttribute('data-method');
      if (m) { closeModal(); setMethod(m); return; }
      if (e.target === overlay || (e.target.classList && e.target.classList.contains('lafka-method-modal__close'))) {
        closeModal();
      }
    });

    function onEsc(ev) {
      if (ev.key === 'Escape') {
        closeModal();
      }
    }
    document.addEventListener('keydown', onEsc);
  }

  document.addEventListener('click', function (e) {
    const t = e.target.closest && e.target.closest('[data-lafka-method-toggle]');
    if (!t) return;
    e.preventDefault();
    openModal();
  });
})();
