// File: /wp-content/plugins/aaa-frontend-checkout-intervention/assets/js/aaa-fci-logger.js
(function () {
  'use strict';

  // Only run on checkout (PHP injects AAA_FCI with is_checkout=true)
  if (!window.AAA_FCI || !AAA_FCI.is_checkout) return;

  // -------------------------------
  // Config
  // -------------------------------
  const Q = [];
  const MAX_BATCH = 10;
  const FLUSH_MS  = 2000;
  const SK        = AAA_FCI.session_key;
  const REST_URL  = AAA_FCI.rest;
  const REST_NONCE = AAA_FCI.rest_nonce || null;

  // Local dedup for fetch noise (server also dedups)
  const FETCH_DEDUP_MS = 1000; // throttle same URL within 1s
  const lastFetchAt = Object.create(null);

  // Running counts for the final "summary" event
  const counts = {
    session_start: 0, page_loaded: 0,
    click: 0, input: 0, fetch: 0, wc_fetch: 0,
    js_error: 0, js_unhandled: 0,
    block_notice: 0, block_validation: 0,
    block_select: 0, payment_select: 0, shipping_select: 0,
    block_submit_attempt: 0, block_rendered: 0
  };

  // -------------------------------
  // Utilities
  // -------------------------------
  const now = () => Date.now();

  function push(type, payload) {
    try {
      Q.push({ type, payload, t: now() });
      if (counts[type] !== undefined) counts[type]++; else counts[type] = 1;
      if (Q.length >= MAX_BATCH) flush();
    } catch (e) { /* no-op */ }
  }

  function sendViaRest(batch) {
    const headers = { 'Content-Type': 'application/json' };
    if (REST_NONCE) headers['X-WP-Nonce'] = REST_NONCE;

    // Server accepts array directly (and we also allow string bodies serverside)
    const body = JSON.stringify({ session_key: SK, events: batch });
    return fetch(REST_URL, {
      method: 'POST',
      body,
      credentials: 'same-origin',
      keepalive: true,
      headers
    });
  }

  function flush() {
    if (!Q.length) return;
    const batch = Q.splice(0, Q.length);
    sendViaRest(batch).catch(() => { /* swallow */ });
  }

  function getLabelFor(el) {
    try {
      if (!el) return null;
      // <label for="id">
      if (el.id) {
        const lbl = document.querySelector(`label[for="${CSS.escape(el.id)}"]`);
        if (lbl && lbl.textContent) return lbl.textContent.trim().slice(0, 128);
      }
      // aria-label
      if (el.getAttribute && el.getAttribute('aria-label')) {
        return el.getAttribute('aria-label').trim().slice(0, 128);
      }
      // nearest label wrapper
      const wrap = el.closest ? el.closest('label') : null;
      if (wrap && wrap.textContent) return wrap.textContent.trim().slice(0, 128);
      // placeholder or title fallback
      const ph = el.getAttribute && (el.getAttribute('placeholder') || el.getAttribute('title'));
      if (ph) return String(ph).trim().slice(0, 128);
      return null;
    } catch (_) {
      return null;
    }
  }

  function cssSelector(el) {
    try {
      if (!el || !el.nodeType || el.nodeType !== 1) return '';
      const parts = [];
      let node = el;
      while (node && node.nodeType === 1 && parts.length < 6) {
        const name = node.nodeName.toLowerCase();
        let part = name;
        if (node.id) {
          part += '#' + node.id.replace(/\s+/g, '');
          parts.unshift(part);
          break; // id is unique enough
        } else {
          const cls = (node.className && typeof node.className === 'string')
            ? node.className.trim().split(/\s+/).slice(0, 3).join('.')
            : '';
          if (cls) part += '.' + cls;
          // nth-child for some specificity
          const parent = node.parentNode;
          if (parent) {
            let i = 1, sib = node;
            while ((sib = sib.previousElementSibling)) i++;
            part += `:nth-child(${i})`;
          }
        }
        parts.unshift(part);
        node = node.parentElement;
      }
      return parts.join(' > ').slice(0, 256);
    } catch (_) {
      return '';
    }
  }

  function cleanDataset(el) {
    try {
      if (!el || !el.dataset) return null;
      // Return a shallow snapshot of data-* (limit keys/size)
      const out = {};
      let c = 0;
      for (const k in el.dataset) {
        if (!Object.prototype.hasOwnProperty.call(el.dataset, k)) continue;
        const v = String(el.dataset[k] || '');
        if (v.length > 0) {
          out[k] = v.slice(0, 128);
          c++;
          if (c >= 10) break;
        }
      }
      return c ? out : null;
    } catch (_) {
      return null;
    }
  }

  function isSensitiveName(name) {
    if (!name) return false;
    const nm = String(name).toLowerCase();
    const deny = ['pass', 'pwd', 'password', 'card', 'cvc', 'cvv', 'token', 'security', 'nonce'];
    return deny.some(d => nm.indexOf(d) !== -1);
  }

  function isAddressField(name) {
    if (!name) return false;
    const nm = String(name).toLowerCase();
    // Broad match to catch checkout blocks + classic names
    return /address|address_1|address_2|city|state|postcode|zip|country|first_name|last_name|company|phone|email/.test(nm);
  }

  function whichAddressSide(name) {
    if (!name) return null;
    const nm = String(name).toLowerCase();
    if (nm.indexOf('billing_') === 0) return 'billing';
    if (nm.indexOf('shipping_') === 0) return 'shipping';
    return null;
  }

  // -------------------------------
  // Session lifecycle
  // -------------------------------
  const t0 = performance.now();
  push('session_start', { url: location.href, ref: document.referrer || null });
  counts.session_start++;

  window.addEventListener('load', () => {
    const loadTime = Math.round(performance.now() - t0);
    push('page_loaded', { ms: loadTime });
    counts.page_loaded++;
  });

  // Periodic flush + final flush
  const tick = setInterval(flush, FLUSH_MS);
  function finalFlush() {
    push('summary', { counts });
    flush();
  }
  window.addEventListener('beforeunload', finalFlush, { capture: true });
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') finalFlush();
  });

  // -------------------------------
  // Click tracking (rich context)
  // -------------------------------
  document.addEventListener('click', function(e) {
    try {
      const t = e.target;
      const rect = t && t.getBoundingClientRect ? t.getBoundingClientRect() : null;
      const payload = {
        tag: t && t.tagName || null,
        id: t && t.id || null,
        name: t && t.name || null,
        class: t && (t.className || null),
        text: t && (t.innerText || '').trim().slice(0, 96),
        label: getLabelFor(t),
        selector: cssSelector(t),
        dataset: cleanDataset(t),
        x: e.clientX, y: e.clientY,
        w: rect ? Math.round(rect.width) : null,
        h: rect ? Math.round(rect.height) : null
      };
      push('click', payload);
    } catch (_) {}
  }, true);

  // -------------------------------
  // Input tracking (mask + address tagging)
  // -------------------------------
  document.addEventListener('input', function(e) {
    const t = e.target;
    if (!t || !t.name) return;

    const sensitive = isSensitiveName(t.name) || (t.type === 'password');
    const value = sensitive ? '[masked]' : String(t.value || '').slice(0, 128);
    const addr = isAddressField(t.name);
    const side = whichAddressSide(t.name);

    const payload = {
      name: t.name,
      label: getLabelFor(t),
      value,
      type: t.type || null,
      is_address: !!addr,
      side: side || undefined,
      selector: cssSelector(t)
    };

    push('input', payload);

    // (Note) Server also adds an "address" detail when it detects name matches;
    // We still mark is_address + side for more context.
  }, true);

  // -------------------------------
  // JS errors (filtered to avoid cross-domain noise)
  // -------------------------------
  window.addEventListener('error', e => {
    const src = e.filename || '';
    const msg = e.message || '';

    // 🔹 Ignore generic external or anonymous script errors
    if (
      msg === 'Script error.' ||
      (!src || !src.startsWith(window.location.origin))
    ) {
      return; // skip analytics, ads, and other cross-origin exceptions
    }

    // ✅ Only log same-origin or meaningful runtime errors
    push('js_error', {
      message: msg,
      src,
      line: e.lineno || 0,
      col: e.colno || 0
    });
  });

  // -------------------------------
  // Unhandled promise rejections
  // -------------------------------
  window.addEventListener('unhandledrejection', e => {
    const reason = e && e.reason;
    const msg = (reason && (reason.message || String(reason))) || 'unknown';
    push('js_unhandled', { reason: msg });
  });

  // -------------------------------
  // Wrap fetch to log WC Blocks requests
  // -------------------------------
  if (window.fetch) {
    const _fetch = window.fetch;
    window.fetch = function() {
      try {
        const args = arguments;
        let url = (args[0] && args[0].url) ? args[0].url : String(args[0] || '');
        const init = args[1] || {};
        const method = (init.method || 'GET').toUpperCase();

        const isWC = url.indexOf('/wc/store') !== -1 || url.indexOf('/wc-ajax/') !== -1;
        const type = isWC ? 'wc_fetch' : 'fetch';

        // Local throttle: same URL within FETCH_DEDUP_MS
        const key = type + '|' + url;
        const tNow = now();
        if (!lastFetchAt[key] || (tNow - lastFetchAt[key]) > FETCH_DEDUP_MS) {
          lastFetchAt[key] = tNow;
          push(type, { url: String(url).slice(0, 512), method });
        }
      } catch (_) {}
      return _fetch.apply(this, arguments);
    };
  }

  // -------------------------------
  // Woo Blocks observation
  // -------------------------------
  // Detect checkout blocks render
  const renderCheck = setInterval(() => {
    const main = document.querySelector('.wc-blocks-checkout__main, .wc-block-checkout');
    if (main) {
      clearInterval(renderCheck);
      push('block_rendered', { time: Math.round(performance.now() - t0) });
      observeBlocks(main);
    }
  }, 500);

  function observeBlocks(root) {
    // Notices + validation errors
    const obs = new MutationObserver(muts => {
      muts.forEach(m => {
        try {
          if (m.type === 'childList' && m.addedNodes && m.addedNodes.length) {
            m.addedNodes.forEach(n => {
              if (!n || n.nodeType !== 1) return;
              const cls = n.classList ? Array.from(n.classList) : [];
              // Notice banners
              if (cls.indexOf('wc-block-components-notice-banner') !== -1) {
                const text = (n.textContent || '').trim().slice(0, 256);
                push('block_notice', { text });
              }
            });
          }
          if (m.type === 'attributes' && m.target && m.target.classList
              && m.target.classList.contains('wc-block-components-validation-error')) {
            const label = m.target.getAttribute('aria-label') || m.target.name || null;
            push('block_validation', { field: label });
          }
        } catch (_) {}
      });
    });
    obs.observe(root, { childList: true, subtree: true, attributes: true });
  }

  // Payment / shipping method selection
  document.addEventListener('change', function(e) {
    const el = e.target;
    if (!el) return;

    // Payment method radio in WC Blocks
    if (el.closest('.wc-block-checkout__payment-method')) {
      const label = (el.labels && el.labels.length ? el.labels[0].innerText.trim().slice(0, 96) : (el.value || ''));
      push('payment_select', { name: el.name || null, label, selector: cssSelector(el) });
      return;
    }

    // Generic block radio options (shipping etc.)
    if (el.closest('.wc-block-components-radio-control__option')) {
      const label = (el.labels && el.labels.length ? el.labels[0].innerText.trim().slice(0, 96) : (el.value || ''));
      const isShipping = !!el.closest('.wc-block-components-shipping-rates') || !!el.closest('.wc-block-components-shipping-rates__package');
      push(isShipping ? 'shipping_select' : 'block_select', {
        name: el.name || null,
        label,
        selector: cssSelector(el)
      });
      return;
    }
  }, true);

  // Checkout submit attempt
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form && form.classList && (form.classList.contains('wc-block-checkout') || form.classList.contains('wc-blocks-checkout__main'))) {
      push('block_submit_attempt', { action: form.action || null });
    }
  }, true);

})();
