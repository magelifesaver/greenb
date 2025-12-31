/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/assets/js/board-sidebar-feed.js
 * Purpose: Right-column Payment Confirmation feed for the Workflow Board.
 * Key behaviors:
 *   - REST with X-WP-Nonce (preferred), graceful fallback to admin-ajax if 401/403
 *   - NO AUTO POLLING (manual only + listens to 'aaa-oc:feed:refresh')
 *   - Diff-only DOM updates (only changed/added/removed items are touched)
 *   - Does not interfere with board card opening or links
 *
 * Expects (localized by your loaders):
 *   window.AAA_OC_PCFeed = {
 *     endpoint: '/wp-json/aaa-oc/v1/payconfirm',
 *     rest_nonce: '<wp_rest_nonce>',
 *     per_page: 20
 *   }
 *   window.AAA_OC_Vars = {
 *     ajaxUrl: '/wp-admin/admin-ajax.php',
 *     nonce:   '<aaa_oc_ajax_nonce>',
 *     adminEditBase: '/wp-admin/post.php?action=edit&post='
 *   }
 */

;(function($){
  'use strict';

  var DEBUG = true;
  function log(){ if (DEBUG && window.console) console.log.apply(console, ['[AAA-OC][Feed]'].concat([].slice.call(arguments))); }

  // -------- Targets (toolbar shell creates the sidebar & body already) --------
  var SIDEBAR_BODY = '#aaa-oc-sidebar-body';
  var FEED_WRAP    = '#aaa-oc-sidebar-feed';

  // -------- One-time CSS --------
  (function injectCssOnce(){
    if (document.getElementById('aaa-oc-feed-style')) return;
    var s = document.createElement('style');
    s.id = 'aaa-oc-feed-style';
    s.textContent = [
      '.aaa-oc-feed-toolbar{display:flex;justify-content:flex-end;gap:6px;margin-bottom:6px}',
      '.aaa-oc-feed-list{display:flex;flex-direction:column;gap:8px}',
      '.aaa-oc-feed-card{border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff}',
      '.aaa-oc-feed-meta{opacity:.8;font-size:12px}',
      '.aaa-oc-feed-actions{margin-top:6px;display:flex;gap:8px;flex-wrap:wrap}'
    ].join('');
    document.head.appendChild(s);
  })();

  // -------- Ensure container blocks exist in the sidebar --------
  function ensureContainer(){
    var body = document.querySelector(SIDEBAR_BODY);
    if (!body) { log('Sidebar body not found; feed will idle.'); return false; }

    if (!body.querySelector('.aaa-oc-feed-toolbar')) {
      var toolbar = document.createElement('div');
      toolbar.className = 'aaa-oc-feed-toolbar';
      toolbar.innerHTML = '<button class="button button-small aaa-oc-feed-refresh">Refresh</button>';
      body.prepend(toolbar);
    }
    if (!document.querySelector(FEED_WRAP)) {
      var wrap = document.createElement('div');
      wrap.id = 'aaa-oc-sidebar-feed';
      wrap.className = 'aaa-oc-feed-list';
      wrap.innerHTML = '<em>Loading…</em>';
      body.appendChild(wrap);
    }
    return true;
  }

  // -------- Helpers: URLs & headers --------
  function restUrl(){
    var ep  = (window.AAA_OC_PCFeed && AAA_OC_PCFeed.endpoint) || '';
    var per = (window.AAA_OC_PCFeed && AAA_OC_PCFeed.per_page) || 20;
    return ep ? (ep + '?per_page=' + encodeURIComponent(per)) : '';
  }
  function restHeaders(){
    var h = {};
    if (window.AAA_OC_PCFeed && AAA_OC_PCFeed.rest_nonce) {
      h['X-WP-Nonce'] = AAA_OC_PCFeed.rest_nonce;
    }
    return h;
  }
  function ajaxUrl(){
    var base  = (window.AAA_OC_Vars && AAA_OC_Vars.ajaxUrl) || (window.ajaxurl || (location.origin + '/wp-admin/admin-ajax.php'));
    var nonce = (window.AAA_OC_Vars && AAA_OC_Vars.nonce)   || '';
    var qs = new URLSearchParams({ action:'aaa_oc_payment_feed', nonce: nonce });
    return base + (base.indexOf('?')>-1 ? '&' : '?') + qs.toString();
  }
  function adminOrderEditUrl(id){
    var base = (window.AAA_OC_Vars && AAA_OC_Vars.adminEditBase) || (location.origin + '/wp-admin/post.php?action=edit&post=');
    return base + String(id || '');
  }

  // -------- Minimal templating + diff-only render --------
  function pickOrderId(p){ return p.matched_order_id || p.matchedOrderId || p.order_id || p.orderId || p.order || null; }
  function pickLink(p){ return p.pc_link || p.link || p.permalink || ''; }

  function cardHtml(p){
    var id   = p.id || '';
    var t    = p.title || p.payer_name || ('PC #' + id);
    var date = p.date || p.payment_date || p.created_at || '';
    var orderId = pickOrderId(p);
    var link    = pickLink(p);
    var btns = [];
    if (orderId) btns.push('<a class="button button-small" target="_blank" rel="noreferrer" href="'+adminOrderEditUrl(orderId)+'">Open Order</a>');
    if (link)    btns.push('<a class="button button-small" target="_blank" rel="noreferrer" href="'+link+'">Open Source</a>');
    btns.push('<button class="button button-small aaa-oc-feed-refresh">Refresh</button>');
    return [
      '<div class="aaa-oc-feed-card" data-id="', String(id), '" data-hash="">',
        '<div style="display:flex;justify-content:space-between;gap:8px">',
          '<strong class="t" style="font-size:13px">', String(t), '</strong>',
          '<span class="aaa-oc-feed-meta d">', String(date), '</span>',
        '</div>',
        '<div class="aaa-oc-feed-actions">', btns.join(' '), '</div>',
      '</div>'
    ].join('');
  }
  function rowHash(p){
    try { return encodeURIComponent(JSON.stringify([p.id,p.title,p.date,p.matched_order_id,p.link])); }
    catch(e){ return ''; }
  }

  var feedState = { order: [], map: {} };

  function renderDiff(items){
    var wrap = document.querySelector(FEED_WRAP);
    if (!wrap) return;

    // snapshot existing
    var existing = {};
    wrap.querySelectorAll('.aaa-oc-feed-card').forEach(function(el){
      existing[ el.getAttribute('data-id') || '' ] = el;
    });

    var newOrder = [], newMap = {};

    items.forEach(function(p, idx){
      var id = String(p.id || '');
      if (!id) return;
      var h  = rowHash(p);

      newOrder.push(id);
      newMap[id] = h;

      var ex = existing[id];
      if (ex && decodeURIComponent(ex.getAttribute('data-hash') || '') === h) {
        // unchanged → keep as-is
        return;
      }

      if (ex){
        // Patch title/date/actions only; keep node position for minimal churn
        ex.querySelector('.t').textContent = p.title || ('PC #' + (p.id || ''));
        ex.querySelector('.d').textContent = p.date || p.payment_date || p.created_at || '';
        ex.setAttribute('data-hash', encodeURIComponent(h));
        var tmp = document.createElement('div');
        tmp.innerHTML = cardHtml(p);
        var newActions = tmp.querySelector('.aaa-oc-feed-actions');
        var oldActions = ex.querySelector('.aaa-oc-feed-actions');
        if (newActions && oldActions) oldActions.replaceWith(newActions);
      } else {
        // Insert in correct order relative to the next known item
        var html = cardHtml(p);
        var inserted = false;
        for (var i = idx+1; i < items.length; i++){
          var nextId = String(items[i].id || '');
          var nextEl = existing[nextId] || wrap.querySelector('.aaa-oc-feed-card[data-id="'+nextId+'"]');
          if (nextEl){ nextEl.insertAdjacentHTML('beforebegin', html); inserted = true; break; }
        }
        if (!inserted) wrap.insertAdjacentHTML('beforeend', html);
      }
    });

    // Remove rows that disappeared
    Object.keys(existing).forEach(function(id){
      if (!newMap[id]) existing[id].remove();
    });

    feedState = { order: newOrder, map: newMap };

    // If list was empty and now has rows, remove the "Loading…" text
    if (!items.length && !wrap.querySelector('.aaa-oc-feed-card')) {
      wrap.innerHTML = '<em>No confirmations yet.</em>';
    }
  }

  // -------- Data loading (REST w/ nonce → fallback to admin-ajax) --------
  async function loadViaREST(){
    var url = restUrl();
    if (!url) throw new Error('no_rest');
    var r = await fetch(url, { credentials: 'same-origin', headers: restHeaders() });
    if (r.status === 401 || r.status === 403) throw new Error('rest_auth');
    var json = await r.json();
    if (Array.isArray(json)) return json;
    if (json && Array.isArray(json.items)) return json.items;
    if (json && json.data && Array.isArray(json.data)) return json.data;
    return [];
  }
  async function loadViaAJAX(){
    var r = await fetch(ajaxUrl(), { credentials: 'same-origin' });
    var json = await r.json();
    if (!json || json.success !== true) throw new Error('ajax_error');
    return json.data || [];
  }

  async function loadFeed(){
    try{
      if (!ensureContainer()) return;

      var list = document.querySelector(FEED_WRAP);
      if (list && !list.querySelector('.aaa-oc-feed-card')) list.innerHTML = '<em>Loading…</em>';

      var data = [];
      try {
        data = await loadViaREST();     // preferred path with X-WP-Nonce
      } catch (err) {
        if (String(err && err.message) === 'rest_auth' || String(err && err.message) === 'no_rest') {
          log('REST blocked/unavailable → fallback to admin-ajax');
          data = await loadViaAJAX();
        } else {
          throw err;
        }
      }

      renderDiff(data);
      log('feed ok', data.length, 'rows');
    } catch (e) {
      log('feed error', e);
      var w = document.querySelector(FEED_WRAP);
      if (w) w.innerHTML = '<em>Could not load feed.</em>';
    }
  }

  // -------- Controls / Events (Manual only) --------
  // Manual refresh button
  $(document).on('click', '.aaa-oc-feed-refresh', loadFeed);

  // External triggers (e.g., prefs or your code can dispatch this when Postie finishes)
  //   document.dispatchEvent(new Event('aaa-oc:feed:refresh'))
  document.addEventListener('aaa-oc:feed:refresh', loadFeed);

  // Boot (manual-first)
  $(function(){
    if (ensureContainer()){
      // No auto-poll at all — initial load only; subsequent refresh is manual or via event.
      loadFeed();
      log('feed mounted (manual refresh only)');
    }
  });

})(jQuery);
