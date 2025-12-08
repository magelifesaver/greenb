// File: /wp-content/plugins/aaa-fci/assets/js/aaa-fci-kadence-restore.js
// Purpose: Re-open Kadence header login drawer after failed login redirects and show an inline error.
// Verbose diagnostics included. Set window.AAA_FCI_DEBUG=true in console to force logs.

(function () {
  // ---------- debug/log helpers ----------
  const DEF_DEBUG = true; // default on for now; set to false to quiet by default
  const ns = '[AAA-FCI][KADENCE-RESTORE]';
  const isDebug = () => {
    try { if (typeof window.AAA_FCI_DEBUG !== 'undefined') return !!window.AAA_FCI_DEBUG; } catch(e){}
    return DEF_DEBUG;
  };
  const log  = (...a) => isDebug() && console.log(ns, ...a);
  const warn = (...a) => isDebug() && console.warn(ns, ...a);
  const err  = (...a) => isDebug() && console.error(ns, ...a);
  const grp  = (t) => isDebug() && console.group(ns, t);
  const grpend = () => isDebug() && console.groupEnd();

  // ---------- early exit inside iframes (Fast Cart) ----------
  try {
    if (window.self !== window.top) {
      log('Exiting: running inside an iframe (Fast Cart).');
      return;
    }
  } catch (e) {
    warn('Window frame check threw; exiting to be safe.', e);
    return;
  }

  // ---------- selectors / constants (tweak if your theme differs) ----------
  const DRAWER_ID   = '#login-drawer';
  const TOGGLE_BTN  = '[data-toggle-target="#login-drawer"]';
  const ERROR_CLASS = 'aaa-fci-login-error';

  const SRC_KEY     = 'aaa_fci_last_login_src';
  const SRC_DRAWER  = 'kadence_drawer';

  const qs  = (s, r=document) => r.querySelector(s);
  const qsa = (s, r=document) => Array.from(r.querySelectorAll(s));

  // ---------- drawer ops ----------
  function openDrawer(reason = '') {
    grp('openDrawer');
    const modal = qs(DRAWER_ID);
    const btn   = qs(TOGGLE_BTN);

    log('Reason:', reason);
    if (!modal) { warn('No drawer element found:', DRAWER_ID); grpend(); return; }

    // Kadence uses a body class to show drawer
    document.body.classList.add('showing-popup-drawer');
    modal.style.removeProperty('display');
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    log('Applied Kadence body class and attributes to show drawer.');

    if (btn) {
      const expanded = btn.getAttribute('aria-expanded');
      log('Found toggle button. aria-expanded=', expanded);
      if (expanded === 'false') {
        try { btn.click(); log('Simulated click on toggle button to ensure open.'); }
        catch (e) { warn('Toggle click failed:', e); }
      }
    } else {
      warn('No toggle button found with selector:', TOGGLE_BTN);
    }
    grpend();
  }

  function ensureErrorBox(form) {
    let box = form.querySelector('.' + ERROR_CLASS);
    if (!box) {
      box = document.createElement('div');
      box.className = ERROR_CLASS;
      form.prepend(box);
      log('Inserted error box into form.');
    } else {
      log('Using existing error box.');
    }
    box.setAttribute('role', 'alert');
    return box;
  }

  function showMessage(msg, where = 'auto') {
    grp('showMessage');
    log('Message:', msg);
    let form =
      qs(`${DRAWER_ID} form`) ||
      qs('form#loginform') ||
      qs('.woocommerce-form-login');

    if (!form) {
      warn('No login form found to display message.');
      grpend(); return;
    }
    const box = ensureErrorBox(form);
    box.textContent = msg;
    log('Message rendered in error box. Where:', where);
    grpend();
  }

  // ---------- server error sniffers ----------
  function extractServerError() {
    grp('extractServerError');
    const wpErr = qs('#login_error');
    if (wpErr && wpErr.textContent.trim()) {
      log('Found #login_error:', wpErr.textContent.trim());
      grpend();
      return wpErr.textContent.trim();
    }
    const wcErr = qs('.woocommerce-error, .wc-block-components-notice-banner.is-error');
    if (wcErr && wcErr.textContent.trim()) {
      log('Found Woo error:', wcErr.textContent.trim());
      grpend();
      return wcErr.textContent.trim();
    }
    log('No server error nodes found on page.');
    grpend();
    return '';
  }

  function isLoginFailedParam() {
    const u = new URL(location.href);
    const failed = u.searchParams.get('login') === 'failed';
    log('Query param check ?login=failed →', failed);
    return failed;
  }

  // ---------- remember source before submit ----------
  document.addEventListener('submit', function (ev) {
    const form = ev.target;
    if (!(form instanceof HTMLFormElement)) return;

    const inDrawer = !!form.closest(DRAWER_ID);
    const looksLogin =
      !!form.querySelector('input[name="log"], input[name="user_login"], input[name="username"]') ||
      /wp-login\.php/i.test(form.action || '');

    if (inDrawer && looksLogin) {
      try {
        sessionStorage.setItem(SRC_KEY, SRC_DRAWER);
        log('Submit captured: set sessionStorage flag', SRC_KEY, '→', SRC_DRAWER);
      } catch (e) {
        warn('Unable to set sessionStorage flag:', e);
      }
    } else if (isDebug()) {
      log('Submit ignored. inDrawer:', inDrawer, 'looksLogin:', looksLogin);
    }
  }, true);

  // ---------- restore on load ----------
  const cameFromDrawer = (() => {
    try {
      const val = sessionStorage.getItem(SRC_KEY);
      log('sessionStorage read', SRC_KEY, '→', val);
      return val === SRC_DRAWER;
    } catch (e) {
      warn('sessionStorage read failed:', e);
      return false;
    }
  })();

  const failedParam = isLoginFailedParam();
  const serverMsgNow = extractServerError();

  grp('restore-logic');
  log('cameFromDrawer:', cameFromDrawer, '| failedParam:', failedParam, '| serverMsgNow:', !!serverMsgNow);

  if (cameFromDrawer && (failedParam || serverMsgNow)) {
    openDrawer(failedParam ? 'queryParamFailed' : 'serverMarkupPresent');
    showMessage(serverMsgNow || 'Invalid username or password. Please try again.', failedParam ? 'query-param' : 'server-markup');
    try { sessionStorage.removeItem(SRC_KEY); log('Cleared sessionStorage flag after restore.'); } catch(e){ warn('Could not clear sessionStorage flag:', e); }
  } else {
    if (!cameFromDrawer) log('Not restoring: did not originate from header drawer.');
    if (!failedParam && !serverMsgNow) log('Not restoring: no failure indicators found.');
  }
  grpend();

  // ---------- health checks: log DOM presence for selectors ----------
  grp('health-check');
  log('Has drawer element?', !!qs(DRAWER_ID), DRAWER_ID);
  const btn = qs(TOGGLE_BTN);
  log('Has toggle button?', !!btn, TOGGLE_BTN, btn ? { 'aria-expanded': btn.getAttribute('aria-expanded') } : null);
  const formInDrawer = qs(`${DRAWER_ID} form`);
  log('Form inside drawer?', !!formInDrawer);
  grpend();
})();
