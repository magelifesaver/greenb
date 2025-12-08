// Keep Kadence header login drawer (#login-drawer) open on invalid login; show inline errors.
// Exits immediately if running inside an iframe (so Fast Cart flow remains untouched).
(function () {
  try { if (window.self !== window.top) return; } catch (e) { return; }

  const DRAWER_SEL = '#login-drawer';
  const TOGGLE_BTN = '[data-toggle-target="#login-drawer"]';
  const ERR_CLASS  = 'aaa-fci-login-error';

  const isLoginForm = (form) => {
    if (!(form instanceof HTMLFormElement)) return false;
    if (form.matches('form#loginform, form.woocommerce-form-login, form[name="loginform"]')) return true;
    if (form.action && /wp-login\.php/i.test(form.action)) return true;
    return !!form.querySelector('input[name="log"], input[name="user_login"], input[name="username"]');
  };

  const inDrawer = (el) => el.closest(DRAWER_SEL);
  const openDrawer = () => {
    const modal = document.querySelector(DRAWER_SEL);
    const btn   = document.querySelector(TOGGLE_BTN);
    if (!modal) return;

    // Kadence uses body class to show the drawer
    document.body.classList.add('showing-popup-drawer');
    modal.style.removeProperty('display');
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');

    // If another script toggles by click, simulate it
    if (btn && btn.getAttribute('aria-expanded') === 'false') {
      try { btn.click(); } catch(e){}
    }
  };
  const ensureErrorBox = (form) => {
    let box = form.querySelector('.' + ERR_CLASS);
    if (!box) {
      box = document.createElement('div');
      box.className = ERR_CLASS;
      form.prepend(box);
    }
    return box;
  };

  async function ajaxLogin(form) {
    openDrawer(); // keep it visible
    const action = form.getAttribute('action') || window.location.href;
    const method = (form.getAttribute('method') || 'POST').toUpperCase();
    const body   = new URLSearchParams(new FormData(form)).toString();

    form.classList.add('aaa-fci-login--submitting');
    try {
      const res = await fetch(action, {
        method,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
        redirect: 'manual',
        credentials: 'same-origin'
      });

      // Success is usually a 302 from wp-login.php.
      if (res.status >= 300 && res.status < 400) {
        window.location.reload(); // reflect logged-in header UI
        return;
      }

      const html = await res.text();
      const tmp = document.createElement('div');
      tmp.innerHTML = html;

      const wpErr = tmp.querySelector('#login_error');
      const wcErr = tmp.querySelector('.woocommerce-error, .wc-block-components-notice-banner.is-error');

      let msg = '';
      if (wpErr) msg = wpErr.textContent.trim();
      else if (wcErr) msg = wcErr.textContent.trim();
      else {
        const m = html.match(/invalid|incorrect|error|try again/i);
        msg = m ? 'Invalid username or password.' : 'Unable to log in. Please try again.';
      }

      const box = ensureErrorBox(form);
      box.textContent = msg;
      box.setAttribute('role', 'alert');
      openDrawer(); // reassert visibility if theme tried to close
    } catch (e) {
      const box = ensureErrorBox(form);
      box.textContent = 'Network error. Please try again.';
      box.setAttribute('role', 'alert');
      openDrawer();
    } finally {
      form.classList.remove('aaa-fci-login--submitting');
    }
  }

  // Intercept submits for forms inside the Kadence login drawer.
  document.addEventListener('submit', function (ev) {
    const form = ev.target;
    if (!isLoginForm(form)) return;
    if (!inDrawer(form)) return;
    ev.preventDefault();
    ajaxLogin(form);
  }, true);

  // Fallback: if redirected with ?login=failed, auto-open the drawer & show a message.
  if (new URLSearchParams(location.search).get('login') === 'failed') {
    openDrawer();
    const form = document.querySelector(`${DRAWER_SEL} form`);
    if (form) {
      const box = ensureErrorBox(form);
      if (!box.textContent) {
        box.textContent = 'Invalid username or password.';
        box.setAttribute('role', 'alert');
      }
    }
  }
})();
