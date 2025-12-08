/**
 * File: /wp-content/plugins/aaa-order-workflow/assets/js/board-sidebar-pref.js
 * Purpose: Render preferences (theme, feed refresh), persist in localStorage, broadcast changes.
 * Version: 1.0.0
 */
(function(){
  const DEBUG_THIS_FILE = true;
  const LS_KEY = 'aaa_oc_prefs_v1';

  function log(...a){ if(DEBUG_THIS_FILE) console.log('[AAA-OC][Prefs]',...a); }
  function host(){ return document.querySelector('[data-aaa-oc-prefs]'); }
  function get(){ try{ return JSON.parse(localStorage.getItem(LS_KEY)||'{}'); }catch(_){ return {}; } }
  function set(v){ localStorage.setItem(LS_KEY, JSON.stringify(v||{})); }

  function applyTheme(theme){
    document.documentElement.dataset.ocTheme = theme; // CSS can style [data-oc-theme="dark"]
  }

  let pollTimer = null;
  function applyRefreshRate(sec){
    if (pollTimer) clearInterval(pollTimer);
    if (sec && sec>=15) {
      pollTimer = setInterval(()=>document.dispatchEvent(new Event('aaa-oc:feed:refresh')), sec*1000);
    }
  }

  function render(){
    const h = host(); if(!h) return;
    const prefs = Object.assign({ theme:'light', feedSeconds: 60 }, get());
    h.innerHTML = `
      <div class="prefs-grid">
        <label>Theme</label>
        <select data-pref="theme">
          <option value="light"${prefs.theme==='light'?' selected':''}>Light</option>
          <option value="dark"${prefs.theme==='dark'?' selected':''}>Dark</option>
          <option value="auto"${prefs.theme==='auto'?' selected':''}>Auto</option>
        </select>

        <label>Feed Refresh</label>
        <select data-pref="feedSeconds">
          <option value="0"${!prefs.feedSeconds?' selected':''}>Manual</option>
          <option value="30"${prefs.feedSeconds==30?' selected':''}>30s</option>
          <option value="60"${prefs.feedSeconds==60?' selected':''}>60s</option>
          <option value="120"${prefs.feedSeconds==120?' selected':''}>120s</option>
        </select>
      </div>
    `;
    h.addEventListener('change', onChange);
    applyTheme(prefs.theme);
    applyRefreshRate(Number(prefs.feedSeconds||0));
  }

  function onChange(e){
    const sel = e.target.closest('[data-pref]'); if(!sel) return;
    const prefs = Object.assign({ theme:'light', feedSeconds: 60 }, get());
    prefs[ sel.getAttribute('data-pref') ] = sel.value;
    set(prefs);

    if (sel.getAttribute('data-pref')==='theme') applyTheme(sel.value);
    if (sel.getAttribute('data-pref')==='feedSeconds') applyRefreshRate(Number(sel.value||0));

    document.dispatchEvent(new CustomEvent('aaa-oc:prefs:changed', { detail: prefs }));
    log('changed', prefs);
  }

  // Simple style
  (function css(){
    if(document.getElementById('aaa-oc-prefs-css')) return;
    const s=document.createElement('style'); s.id='aaa-oc-prefs-css'; s.textContent=`
      .prefs-grid{display:grid;grid-template-columns:120px 1fr;gap:6px;align-items:center}
      [data-oc-theme="dark"] body{background:#0b0b0b;color:#e5e7eb}
    `; document.head.appendChild(s);
  })();

  document.addEventListener('aaa-oc:prefs:mount', render);
})();
