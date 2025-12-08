/**
 * File: /wp-content/plugins/aaa-order-workflow/assets/js/board-toolbar-extras.js
 * Purpose: Header (Refresh | Filters | Actions | FullPage | Prefs),
 *          headless panels, reliable click-outside close, sticky top sheets.
 * Version: 1.2.0
 */
;(function($){
  'use strict';
  const DEBUG_THIS_FILE = true;
  const log = (...a)=>{ if (DEBUG_THIS_FILE) console.log('[AAA-OC][BAR]', ...a); };

  const H1 = '.wrap h1:first';
  const LS_FP = 'aaaOC_fullpage';
  const FEED_WRAP = '#aaa-oc-sidebar-feed';
  const FEED_MS = 60000;

  // ---------- CSS ----------
  function injectCss(){ if($('#aaa-oc-shell-style').length) return;
    const css = `
      .aaa-oc-bar{display:flex;justify-content:space-between;align-items:center;margin:.25rem 0}
      .aaa-oc-title{font-weight:600;font-size:18px;opacity:.9}
      .aaa-oc-right{display:flex;align-items:center;gap:.5rem}
      .aaa-oc-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:42px;min-width:42px;padding:.6em 1.05em;border:1px solid #ccd0d4;border-radius:8px;background:#f3f4f5;color:#1e1e1e;font-weight:700;cursor:pointer}
      .aaa-oc-btn.primary{background:#2271b1;color:#fff;border-color:#1b5b8d}
      .aaa-oc-ico{width:16px;height:16px}.aaa-oc-gear .nm{opacity:.8}
      .aaa-oc-header-tools{margin-top:8px}

      /* Top sheets: sticky under admin bar, compact, headless */
      .aaa-oc-sheet{position:fixed;left:50%;transform:translateX(-50%);top:-160px;width:min(980px,96vw);
        max-height:130px;background:#fff;border-radius:0 0 10px 10px;box-shadow:0 14px 32px rgba(0,0,0,.18);
        transition:top .18s ease;z-index:100001}
      .aaa-oc-sheet.open{top:var(--aaa-oc-sheet-top, 0px)}
      .aaa-oc-sheet .aaa-oc-body{max-height:130px;overflow:auto;padding:10px 12px}

      /* Right sidebar: headless; body only */
      .aaa-oc-sidebar{position:fixed;top:0;right:-460px;width:440px;max-width:90vw;height:100vh;background:#fff;
        box-shadow:-2px 0 18px rgba(0,0,0,.18);transition:right .22s ease;z-index:100001;display:flex;flex-direction:column}
      .aaa-oc-sidebar.open{right:0}
      .aaa-oc-sidebar .aaa-oc-body{padding:10px;overflow:auto}
      .aaa-oc-feed-toolbar{display:flex;justify-content:flex-end;gap:6px;margin-bottom:6px}
      .aaa-oc-feed-list{display:flex;flex-direction:column;gap:8px}
      .aaa-oc-feed-card{border:1px solid #e5e7eb;border-radius:8px;padding:8px}
      .aaa-oc-feed-meta{opacity:.8;font-size:12px}

      /* Backdrops: capture clicks reliably */
      .aaa-oc-backdrop{position:fixed;inset:0;z-index:100000;background:transparent;display:none;pointer-events:none}
      .aaa-oc-backdrop.show{display:block;pointer-events:auto}

      /* Full-Page toggle */
      html.aaa-oc-fullpage #wpadminbar{display:none}
      html.aaa-oc-fullpage #wpcontent,html.aaa-oc-fullpage #wpfooter{margin-left:0!important}
      html.aaa-oc-fullpage #adminmenumain{display:table-column!important}
      html.aaa-oc-fullpage.wp-toolbar{padding-top:0}
    `;
    $('<style id="aaa-oc-shell-style"></style>').text(css).appendTo(document.head);
  }

  // ---------- Header ----------
  function buildHeader(){
    const $h=$(H1); if(!$h.length || $h.find('.aaa-oc-bar').length) return;
    const name=(document.querySelector('#wp-admin-bar-my-account .ab-item')?.textContent||'').replace(/^.*Howdy[, ]*/i,'').trim()||'admin';
    const icoFilter='<svg class="aaa-oc-ico" viewBox="0 0 24 24"><path d="M3 5h18v2l-7 7v4l-4 2v-6L3 7V5z"/></svg>';
    const icoAction='<svg class="aaa-oc-ico" viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>';
    const icoFull='<svg class="aaa-oc-ico" viewBox="0 0 24 24"><path d="M4 4h6v2H6v4H4V4zm10 0h6v6h-2V6h-4V4zM4 14h2v4h4v2H4v-6zm14 0h2v6h-6v-2h4v-4z"/></svg>';
    const gear='<svg class="aaa-oc-ico" viewBox="0 0 24 24"><path d="M12 8.8a3.2 3.2 0 1 1 0 6.4 3.2 3.2 0 0 1 0-6.4zm9.2 3.2c0-.5-.1-1-.2-1.5l2.1-1.6-2-3.5-2.5 1a7.8 7.8 0 0 0-2.6-1.5l-.4-2.7H9.4l-.4 2.7a7.8 7.8 0 0 0-2.6 1.5l-2.5-1-2 3.5 2.1 1.6c-.1.5-.2 1-.2 1.5s.1 1 .2 1.5l-2.1 1.6 2 3.5 2.5-1a7.8 7.8 0 0 0 2.6 1.5l.4 2.7h5.2l.4-2.7c.9-.3 1.8-.8 2.6-1.5l2.5 1 2-3.5-2.1-1.6c.1-.5.2-1 .2-1.5z"/></svg>';
    $h.append(`
      <div class="aaa-oc-bar" role="region" aria-label="Board controls">
        <div class="aaa-oc-title">Workflow Board</div>
        <div class="aaa-oc-right">
          <button type="button" class="aaa-oc-btn primary aaa-oc-refresh">Refresh</button>
          <button type="button" class="aaa-oc-btn aaa-oc-open-filters" title="Filters">${icoFilter}</button>
          <button type="button" class="aaa-oc-btn aaa-oc-open-actions" title="Actions">${icoAction}</button>
          <button type="button" class="aaa-oc-btn aaa-oc-toggle-fullpage" title="Full Page">${icoFull}</button>
          <button type="button" class="aaa-oc-btn aaa-oc-gear" title="Preferences">${gear}<span class="nm">${name}</span></button>
        </div>
      </div>
    `);
  }

  // ---------- Panels + backdrop + hooks ----------
  function buildPanels(){
    if($('#aaa-oc-sidebar').length) return;
    $('body').append('<div id="aaa-oc-backdrop-sheets" class="aaa-oc-backdrop" aria-hidden="true"></div>');
    $('body').append('<div id="aaa-oc-backdrop-sidebar" class="aaa-oc-backdrop" aria-hidden="true"></div>');

    $('body').append(`
      <aside id="aaa-oc-sidebar" class="aaa-oc-sidebar" aria-label="Board Sidebar">
        <div class="aaa-oc-body" id="aaa-oc-sidebar-body">
          <div class="aaa-oc-feed-toolbar"><button class="button button-small aaa-oc-feed-refresh">Refresh</button></div>
          <div id="aaa-oc-sidebar-feed" class="aaa-oc-feed-list"><em>Loading…</em></div>
        </div>
      </aside>
      <section id="aaa-oc-sheet-filters" class="aaa-oc-sheet" role="dialog" aria-modal="true"><div class="aaa-oc-body" id="aaa-oc-filters-body"></div></section>
      <section id="aaa-oc-sheet-actions" class="aaa-oc-sheet" role="dialog" aria-modal="true"><div class="aaa-oc-body" id="aaa-oc-actions-body"></div></section>
    `);

    window.aaaOcPanels = {
      add(area, el){
        const map={prefs:'#aaa-oc-sidebar-body', filters:'#aaa-oc-filters-body', actions:'#aaa-oc-actions-body', sidebar:'#aaa-oc-sidebar-body'};
        const t = map[area]; if(!t) return; (el instanceof Element)?$(t).append(el):$(t).append(el);
      },
      open(area){
        setSheetTop(); // compute sticky offset each open
        if(area==='prefs'||area==='sidebar'){ $('#aaa-oc-sidebar').addClass('open'); $('#aaa-oc-backdrop-sidebar').addClass('show'); }
        if(area==='filters'){ $('#aaa-oc-sheet-filters').addClass('open'); $('#aaa-oc-backdrop-sheets').addClass('show'); }
        if(area==='actions'){ $('#aaa-oc-sheet-actions').addClass('open'); $('#aaa-oc-backdrop-sheets').addClass('show'); }
      },
      closeAll(){
        $('.aaa-oc-sidebar.open,.aaa-oc-sheet.open').removeClass('open');
        $('.aaa-oc-backdrop').removeClass('show');
      }
    };

    // Small helper link inside prefs
    const btn=$('<button class="button">Open Feed</button>').on('click',()=>aaaOcPanels.open('sidebar'));
    window.aaaOcPanels.add('prefs', $('<div style="margin-bottom:8px"></div>').append(btn)[0]);
  }

  // ---------- Sticky offset (under admin bar) ----------
  function getAdminBarHeight(){
    const bar = document.getElementById('wpadminbar');
    if (!bar || bar.style.display==='none') return 0;
    const h = bar.offsetHeight || 0;
    return Math.max(0, h);
  }
  function setSheetTop(){
    const top = getAdminBarHeight(); // 0 if hidden/full-page
    document.documentElement.style.setProperty('--aaa-oc-sheet-top', top + 'px');
  }

  // ---------- Full-page toggle ----------
  function applyFP(){
    document.documentElement.classList.toggle('aaa-oc-fullpage', localStorage.getItem(LS_FP)==='1');
    setSheetTop();
  }

  // ---------- Feed via admin-ajax ----------
  function feedAjaxUrl(){
    const base = (window.AAA_OC_Vars && AAA_OC_Vars.ajaxUrl) || (window.ajaxurl || (location.origin + '/wp-admin/admin-ajax.php'));
    const nonce = (window.AAA_OC_Vars && AAA_OC_Vars.nonce) ? AAA_OC_Vars.nonce : '';
    const qs = new URLSearchParams({ action:'aaa_oc_payment_feed', nonce });
    return base + (base.indexOf('?')>-1 ? '&' : '?') + qs.toString();
  }
  function renderFeed(items){
    const $w=$(FEED_WRAP).empty();
    if(!items || !items.length){ $w.html('<em>No recent payment confirmations.</em>'); return; }
    items.forEach(p=>{
      const t=p.title||('(ID '+p.id+')');
      $w.append(`<div class="aaa-oc-feed-card">
        <div style="display:flex;justify-content:space-between;gap:8px"><strong style="font-size:13px">${t}</strong><span class="aaa-oc-feed-meta">${p.date}</span></div>
        <div class="aaa-oc-feed-meta"><a href="${p.link||'#'}" target="_blank" rel="noreferrer">Open</a></div>
      </div>`);
    });
  }
  let feedTimer=null;
  async function loadFeed(){
    try{
      $(FEED_WRAP).html('<em>Loading…</em>');
      const r = await fetch(feedAjaxUrl(), { credentials:'same-origin' });
      const json = await r.json();
      if(!json || json.success!==true) throw new Error('AJAX feed error');
      renderFeed(json.data||[]);
    }catch(e){ log('feed error', e); $(FEED_WRAP).html('<em>Could not load feed (AJAX).</em>'); }
  }
  function startFeedAuto(){ if(feedTimer) clearInterval(feedTimer); feedTimer=setInterval(loadFeed, FEED_MS); }

  // ---------- Events ----------
  function bind(){
    $(document).on('click','.aaa-oc-refresh',()=>{ window.aaaOcRefreshBoard && window.aaaOcRefreshBoard(); });
    $(document).on('click','.aaa-oc-open-filters',()=> window.aaaOcPanels && aaaOcPanels.open('filters'));
    $(document).on('click','.aaa-oc-open-actions',()=> window.aaaOcPanels && aaaOcPanels.open('actions'));
    $(document).on('click','.aaa-oc-gear',()=> window.aaaOcPanels && aaaOcPanels.open('prefs'));
    $(document).on('click','.aaa-oc-toggle-fullpage',function(){
      const on = localStorage.getItem(LS_FP)==='1'; localStorage.setItem(LS_FP, on?'0':'1'); applyFP();
    });

    // Click-outside via backdrops (always on top & pointer-enabled)
    $(document).on('click','#aaa-oc-backdrop-sheets',()=> window.aaaOcPanels && aaaOcPanels.closeAll());
    $(document).on('click','#aaa-oc-backdrop-sidebar',()=> window.aaaOcPanels && aaaOcPanels.closeAll());
    $(document).on('keydown',e=>{ if(e.key==='Escape') window.aaaOcPanels && aaaOcPanels.closeAll(); });

    // Feed controls
    $(document).on('click','.aaa-oc-feed-refresh',loadFeed);

    // Recompute top when window resizes or toolbar toggles
    $(window).on('resize', setSheetTop);
  }

  // ---------- Init ----------
  $(function(){
    injectCss(); buildHeader(); buildPanels(); bind(); applyFP();
    setSheetTop();
    loadFeed(); startFeedAuto();
    log('header+panels ready');
  });
})(jQuery);
