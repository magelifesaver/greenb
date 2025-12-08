/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/assets/js/board-sidebar-feed.js
 * Purpose: Sidebar "Payment Confirm" feed — fetch, diff-update; expand matched order already on board.
 *          If the payload id isn't on the board, derive the visible order by last-name/amount (scanner-style).
 * Version: 1.3.3
 */
;(function($){
  'use strict';

  const DEBUG = true;
  const log = (...a)=>{ if (DEBUG && window.console) console.log('[AAA-OC][Feed]', ...a); };

  // --- selectors / timings ---
  const SIDEBAR_BODY = '#aaa-oc-sidebar-body';
  const FEED_WRAP    = '#aaa-oc-sidebar-feed';
  const FEED_MS      = 60000; // 60s
  const CARD_SEL     = '.aaa-oc-feed-card';
  const OPEN_BTN_SEL = '.aaa-oc-feed-open';

  // --- minimal CSS for feed cards ---
  function injectCss(){
    if ($('#aaa-oc-feed-style').length) return;
    const css = `
      .aaa-oc-feed-toolbar{display:flex;justify-content:flex-end;gap:6px;margin-bottom:6px}
      .aaa-oc-feed-list{display:flex;flex-direction:column;gap:8px}
      .aaa-oc-feed-card{border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff}
      .aaa-oc-feed-meta{opacity:.8;font-size:12px}
      .aaa-oc-feed-actions{margin-top:6px;display:flex;gap:8px;flex-wrap:wrap}
      .aaa-oc-feed-open{cursor:pointer}
    `;
    $('<style id="aaa-oc-feed-style"></style>').text(css).appendTo(document.head);
  }

  // --- ensure the feed container exists ---
  function ensureContainer(){
    if (!$(SIDEBAR_BODY).length) return false;
    if (!$('.aaa-oc-feed-toolbar').length){
      $(SIDEBAR_BODY).prepend('<div class="aaa-oc-feed-toolbar"><button class="button button-small aaa-oc-feed-refresh">Refresh</button></div>');
    }
    if (!$(FEED_WRAP).length){
      $(SIDEBAR_BODY).append('<div id="aaa-oc-sidebar-feed" class="aaa-oc-feed-list"><em>Loading…</em></div>');
    }
    return true;
  }

  // --- endpoint helper (REST preferred) ---
  function feedEndpoint(){
    const rest = (window.AAA_OC_PCFeed && window.AAA_OC_PCFeed.endpoint) || '';
    if (rest) return rest + '?per_page=' + encodeURIComponent((window.AAA_OC_PCFeed && AAA_OC_PCFeed.per_page) || 20);
    const base  = (window.AAA_OC_Vars && AAA_OC_Vars.ajaxUrl) || (window.ajaxurl || (location.origin + '/wp-admin/admin-ajax.php'));
    const nonce = (window.AAA_OC_Vars && AAA_OC_Vars.nonce) ? AAA_OC_Vars.nonce : '';
    const qs    = new URLSearchParams({ action:'aaa_oc_payment_feed', nonce });
    return base + (base.indexOf('?')>-1 ? '&' : '?') + qs.toString();
  }

  // --- ID helpers (prefer matched order; never fall back to PC id for open) ---
  function pickMatchedOrderId(p){
    return p.matched_order_id || p.matchedOrderId || p.order_id || p.orderId || p.order || null;
  }
  function pickSourceLink(p){
    return p.pc_link || p.link || p.permalink || '';
  }
  function adminOrderEditUrl(orderId){
    return (window.AAA_OC_Vars && AAA_OC_Vars.adminEditBase)
      ? AAA_OC_Vars.adminEditBase + orderId
      : (location.origin + '/wp-admin/post.php?post=' + orderId + '&action=edit');
  }

  // --- diff state ---
  let feedState = { order: [], map: {} };
  const hashOf = (p)=>JSON.stringify({
    id: p.id || p.post_id || '',                   // PC post id (for Source/admin edit of PC)
    moid: pickMatchedOrderId(p) || '',             // matched order id
    t: p.title || '',
    d: p.date  || '',
    src: pickSourceLink(p) || ''
  });

  function cardHtml(p){
    const title     = p.title || ('PC #' + (p.id ?? ''));
    const date      = p.date  || '';
    const matchedId = pickMatchedOrderId(p);       // may be null → then "No Match"
    const srcLink   = pickSourceLink(p);
    const actionBtn = matchedId
      ? `<button type="button" class="button button-secondary aaa-oc-feed-open" data-order="${String(matchedId)}">Expand Order ${String(matchedId)}</button>`
      : `<span class="button disabled" title="No matched order yet">No Match</span>`;
    const srcBtn    = srcLink
      ? `<a class="button button-link" href="${srcLink}" target="_blank" rel="noreferrer">Source</a>`
      : '';
    return `
      <div class="aaa-oc-feed-card" data-id="${String(p.id||'')}" data-hash="${encodeURIComponent(hashOf(p))}">
        <div style="display:flex;justify-content:space-between;gap:8px">
          <strong style="font-size:13px" class="t">${title}</strong>
          <span class="aaa-oc-feed-meta d">${date}</span>
        </div>
        <div class="aaa-oc-feed-actions">
          ${actionBtn}
          ${matchedId ? `<a class="button" href="${adminOrderEditUrl(matchedId)}" target="_blank" rel="noreferrer">Admin Edit</a>` : ''}
          ${srcBtn}
        </div>
      </div>`;
  }

  // --- diff render (no full rerender; keep scroll) ---
  function renderDiff(items){
    const $wrap = $(FEED_WRAP);
    if (!items || !items.length){ $wrap.html('<em>No recent payment confirmations.</em>'); feedState={order:[],map:{}}; return; }

    const prevScroll = $wrap.scrollTop();
    const newOrder = [], newMap = {};
    const existing = {};
    $wrap.find(CARD_SEL).each(function(){ existing[$(this).attr('data-id')||''] = $(this); });

    items.forEach((p, idx)=>{
      const id = String(p.id || ''); if (!id) return;
      const h  = hashOf(p); newOrder.push(id); newMap[id]=h;

      const $ex = existing[id];
      if ($ex && decodeURIComponent($ex.attr('data-hash')||'') === h) return;

      if ($ex){
        $ex.find('.t').text(p.title || ('PC #' + (p.id ?? '')));
        $ex.find('.d').text(p.date || '');
        $ex.attr('data-hash', encodeURIComponent(h));
        $ex.find('.aaa-oc-feed-actions').replaceWith($(cardHtml(p)).find('.aaa-oc-feed-actions'));
      } else {
        const html = cardHtml(p);
        let inserted = false;
        for (let i = idx+1; i < items.length; i++){
          const nextId = String(items[i].id || '');
          const $next  = existing[nextId] || $wrap.find(`${CARD_SEL}[data-id="${nextId}"]`);
          if ($next.length){ $(html).insertBefore($next); inserted = true; break; }
        }
        if (!inserted) $wrap.append(html);
      }
    });

    Object.keys(existing).forEach((id)=>{ if (!newMap[id]) existing[id].remove(); });

    feedState = { order:newOrder, map:newMap };
    $wrap.scrollTop(prevScroll);
  }

  // --- data loader ---
  let feedTimer = null;
  async function loadFeed(){
    try{
      const $w = $(FEED_WRAP);
      if (!$w.find(CARD_SEL).length){ $w.html('<em>Loading…</em>'); }
      const r = await fetch(feedEndpoint(), { credentials:'same-origin' });
      const json = await r.json();
      const data = Array.isArray(json) ? json : (json && json.data) ? json.data : [];
      renderDiff(data);
      log('feed loaded', data.length, 'items');
    }catch(e){
      log('feed error', e);
      if ($(FEED_WRAP).length){ $(FEED_WRAP).html('<em>Could not load feed.</em>'); }
    }
  }
  function startFeedAuto(){ if (feedTimer) clearInterval(feedTimer); feedTimer = setInterval(loadFeed, FEED_MS); }

  // --- helper: parse $feedCard title → payer last name + amount ---
  function parseFeedInfo($card){
    try{
      const t = $card.find('.t').text() || '';
      // Examples: "Zelle – $192.30 Paid by KEVIN VARGAS"
      const amtMatch  = t.replace(',', '').match(/\$([\d]+(?:\.\d{1,2})?)/);
      const payerPart = t.split('Paid by').pop() || '';
      const name      = payerPart.trim();
      const last      = name.split(/\s+/).pop() || '';
      const amount    = amtMatch ? parseFloat(amtMatch[1]) : NaN;
      return { name, last: last.toLowerCase(), amount };
    }catch(_){ return { name:'', last:'', amount: NaN }; }
  }

  // --- helper: parse first amount text from a board card (visible) ---
  function parseCardAmount($orderCard){
    try{
      const money = $orderCard.find('.woocommerce-Price-amount').first().text() || '';
      // e.g. "$192.31"
      const m = money.replace(/,/g,'').match(/([\d]+(?:\.\d{1,2})?)/);
      return m ? parseFloat(m[1]) : NaN;
    }catch(_){ return NaN; }
  }

  // --- fallback: find best visible order by last name ± amount ---
  function findVisibleOrderByHeuristic($feedBtn){
    const $feedCard = $feedBtn.closest('.aaa-oc-feed-card');
    const info = parseFeedInfo($feedCard);
    if (!info.last && !isFinite(info.amount)) return null;

    let best = { id:null, score:-1, delta:Infinity };
    $('.aaa-oc-order-card:visible').each(function(){
      const $c = $(this);
      const id = parseInt($c.attr('data-order-id'), 10);
      if (!id) return;

      // last-name match (from data-customer-name if present)
      const cust = String(($c.attr('data-customer-name') || '')).toLowerCase().trim();
      const custLast = cust.split(/\s+/).pop() || '';
      let score = 0;
      if (info.last && custLast && info.last === custLast) score += 2;

      // amount proximity (optional; ±$1 tolerance)
      const amt = parseCardAmount($c);
      if (isFinite(info.amount) && isFinite(amt)){
        const delta = Math.abs(info.amount - amt);
        if (delta <= 1.00){ score += 1; if (delta < best.delta) best.delta = delta; }
      }

      if (score > best.score){ best = { id, score, delta: best.delta }; }
    });

    if (best && best.id && best.score > 0){
      log('heuristic picked visible order', best);
      return best.id;
    }
    return null;
  }

  // --- open-on-board (scanner-style) ---
  let busy = false;

  function closeSidebar(cb){
    try{
      const $s  = $('#aaa-oc-sidebar');
      const $bg = $('#aaa-oc-backdrop-sidebar');
      if (!$s.length || !$s.hasClass('open')){ cb && cb(); return; }
      $s.removeClass('open'); $bg.removeClass('show');
      let done=false; const finish=()=>{ if(done) return; done=true; cb&&cb(); };
      $s.one('transitionend webkitTransitionEnd oTransitionEnd', finish);
      setTimeout(finish, 240);
    }catch(_){ cb&&cb(); }
  }

  function openScannerStyle(orderId){
    const selCard   = `.aaa-oc-order-card[data-order-id="${orderId}"]`;
    const selExpand = `${selCard} .aaa-oc-view-edit, ${selCard} .aaa-oc-expand, ${selCard} .js-open-order`;
    const selPay    = `.open-payment-modal[data-order-id="${orderId}"], .js-open-payment[data-order-id="${orderId}"]`;

    function expandAndMaybePay(){
      const $card = $(selCard);
      if (!$card.length){ log('order not on board:', orderId); busy=false; return; }

      try { $card.get(0).scrollIntoView({ behavior:'smooth', block:'center' }); } catch(_){}
      const $btn = $(selExpand).first();
      if ($btn.length){
        $btn.trigger('click');
        setTimeout(()=>{ const $pay=$(selPay).first(); if ($pay.length) $pay.trigger('click'); busy=false; }, 400);
      } else {
        log('expand button not found for', orderId);
        busy=false;
      }
    }
    closeSidebar(expandAndMaybePay);
  }

  // --- bindings ---
  function bind(){
    $(document).off('click', '.aaa-oc-feed-refresh')
               .on('click',  '.aaa-oc-feed-refresh', function(e){ e.preventDefault(); e.stopPropagation(); log('manual refresh'); loadFeed(); });

    $(document).off('click', OPEN_BTN_SEL)
               .on('click',  OPEN_BTN_SEL, function(e){
                 e.preventDefault(); e.stopPropagation();
                 if (busy) return; busy = true;

                 const providedId = parseInt($(this).data('order'),10) || 0;
                 log('feed-open click', providedId, this);

                 // If the providedId is visible, use it; otherwise try the heuristic.
                 const visible = $(`.aaa-oc-order-card[data-order-id="${providedId}"]`).length > 0;
                 const targetId = visible ? providedId : (findVisibleOrderByHeuristic($(this)) || 0);

                 if (!targetId){
                   log('no visible match found; doing nothing (scanner-style)');
                   busy = false;
                   return;
                 }
                 openScannerStyle(targetId);
               });
  }

  // --- init ---
  function initWhenReady(){
    const boot = () => {
      if (!ensureContainer()) return false;
      injectCss(); bind(); loadFeed(); startFeedAuto();
      log('feed mounted');
      $(document).trigger('aaa-oc:sidebar:feed:ready');
      return true;
    };
    if (boot()) return;
    $(document).on('aaa-oc:sidebar:ready', boot);
    let tries=40; const t=setInterval(()=>{ if (boot() || --tries<=0) clearInterval(t); },200);
  }

  // public hook
  window.aaaOcFeed = { reload: loadFeed };

  $(initWhenReady);
})(jQuery);
