;(function($){
  'use strict';
  const DEBUG_THIS_FILE = true;
  const log = (...a)=>{ if(DEBUG_THIS_FILE) console.log('[AAA-OC][Filters]',...a); };

  const ROOT_SEL   = '#aaa-oc-board-columns';
  const CARD_SEL   = '.aaa-oc-order-card';
  const SHEET_BODY = '#aaa-oc-filters-body';

  const LS = {
    SEARCH:'aaaOC_toolbar_search', PAY:'aaaOC_toolbar_paystatus', ENV:'aaaOC_toolbar_env',
    TIP:'aaaOC_toolbar_tip', CUST:'aaaOC_toolbar_customer', ID:'aaaOC_toolbar_id',
    DRV:'aaaOC_toolbar_driver', CV:'aaaOC_toolbar_createdvia', SRC:'aaaOC_toolbar_src'
  };

  function filtersHtml(){
    return `
      <div class="aaa-oc-filter-bar" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <button type="button" class="button aaa-oc-clear">Clear</button>
        <input class="aaa-oc-search" type="search" placeholder="Search: order #, customer, ID"
               style="min-width:280px;height:30px;padding:0 8px">
        <select class="aaa-oc-payfilter">
          <option value="all">Paid / Unpaid</option>
          <option value="paid">Paid</option>
          <option value="partial">Partial</option>
          <option value="unpaid">Unpaid</option>
        </select>
        <select class="aaa-oc-envfilter">
          <option value="all">Envelope</option>
          <option value="1">Outstanding</option>
          <option value="0">Not Outstanding</option>
        </select>
        <select class="aaa-oc-tipfilter">
          <option value="all">Tip</option>
          <option value="web">Web Tip</option>
          <option value="epayment">E-Payment Tip</option>
          <option value="none">No Tip</option>
        </select>
        <select class="aaa-oc-custfilter">
          <option value="all">Customer</option>
          <option value="new">New</option>
          <option value="existing">Existing</option>
        </select>
        <select class="aaa-oc-idfilter">
          <option value="all">ID</option>
          <option value="expired">Expired</option>
          <option value="valid">Valid</option>
        </select>
        <select class="aaa-oc-driverfilter"><option value="all">Driver</option></select>
        <select class="aaa-oc-createdviafilter"><option value="all">Type</option></select>
        <select class="aaa-oc-ordersourcefilter"><option value="all">Source</option></select>
      </div>
    `;
  }

  const norm = (s)=> (s||'').toString().toLowerCase();
  function getLS(k, d='all'){ return localStorage.getItem(k) || d; }

  function mount(){
    if (!window.aaaOcPanels) { log('Panels not ready. Aborting mount.'); return; }
    const $body = $(SHEET_BODY);
    if (!$body.length) { log('Filters sheet body missing'); return; }
    if ($body.data('hydrated')) return;

    $body.empty().append(filtersHtml()).data('hydrated', true);

    const $bar = $body.find('.aaa-oc-filter-bar');
    $bar.find('.aaa-oc-search').val(localStorage.getItem(LS.SEARCH) || '');
    $bar.find('.aaa-oc-payfilter').val(getLS(LS.PAY));
    $bar.find('.aaa-oc-envfilter').val(getLS(LS.ENV));
    $bar.find('.aaa-oc-tipfilter').val(getLS(LS.TIP));
    $bar.find('.aaa-oc-custfilter').val(getLS(LS.CUST));
    $bar.find('.aaa-oc-idfilter').val(getLS(LS.ID));
    $bar.find('.aaa-oc-driverfilter').val(getLS(LS.DRV));
    $bar.find('.aaa-oc-createdviafilter').val(getLS(LS.CV));
    $bar.find('.aaa-oc-ordersourcefilter').val(getLS(LS.SRC));

    populateDynamicFilters(); // from visible cards
    applyFilters(); // initial run
    bindHandlers();
  }

  function populateDynamicFilters(){
    const drv=new Set(), cv=new Set(), src=new Set();
    document.querySelectorAll(CARD_SEL).forEach(c=>{
      if (c.dataset.driverName) drv.add(c.dataset.driverName);
      if (c.dataset.createdVia) cv.add(c.dataset.createdVia);
      if (c.dataset.orderSource) src.add(c.dataset.orderSource);
    });
    const addOpts = (sel, arr, label)=> {
      const $sel = $(SHEET_BODY).find(sel);
      const base = `<option value="all">${label}</option>`;
      const opts = arr.sort().map(v=>`<option value="${v}">${v}</option>`).join('');
      $sel.empty().append(base + opts);
      const key = sel.includes('driver')?LS.DRV: sel.includes('createdvia')?LS.CV: LS.SRC;
      $sel.val(getLS(key));
    };
    addOpts('.aaa-oc-driverfilter', [...drv], 'Driver');
    addOpts('.aaa-oc-createdviafilter', [...cv], 'Type');
    addOpts('.aaa-oc-ordersourcefilter', [...src], 'Source');
  }

  function cardMatchesSearch(card, q){
    if (!q) return true;
    const $c = $(card);
    const id    = (card.dataset.orderId || '').toLowerCase();
    const num   = $c.find('.aaa-oc-order-number-large').text().toLowerCase();
    const dn    = (card.dataset.customerName || '').toLowerCase();
    const vn    = $c.find('.aaa-oc-customer-name, .customer-name').text().toLowerCase();
    q = norm(q);
    return id.includes(q) || num.includes(q) || dn.includes(q) || vn.includes(q);
  }
  function cardMatchesFilters(card, $bar){
    const ds = card.dataset || {};
    const want = {
      pay:  norm($bar.find('.aaa-oc-payfilter').val() || 'all'),
      env:  norm($bar.find('.aaa-oc-envfilter').val() || 'all'),
      tip:  norm($bar.find('.aaa-oc-tipfilter').val() || 'all'),
      cust: norm($bar.find('.aaa-oc-custfilter').val() || 'all'),
      id:   norm($bar.find('.aaa-oc-idfilter').val() || 'all'),
      drv:  $bar.find('.aaa-oc-driverfilter').val() || 'all',
      cv:   $bar.find('.aaa-oc-createdviafilter').val() || 'all',
      src:  $bar.find('.aaa-oc-ordersourcefilter').val() || 'all',
    };
    if (want.pay!=='all' && norm(ds.paymentStatus)!==want.pay) return false;
    if (want.env!=='all' && (ds.envelopeOutstanding||'')!==want.env) return false;

    const epTip = parseFloat(ds.epaymentTip || '0');
    const totTip= parseFloat(ds.totalOrderTip || '0');
    const haveTip=(epTip>0)?'epayment':((totTip>0)?'web':'none');
    if (want.tip!=='all' && haveTip!==want.tip) return false;

    if (want.cust!=='all' && (ds.customerType||'')!==want.cust) return false;
    if (want.id!=='all' && ((ds.idExpired==='1')?'expired':'valid')!==want.id) return false;
    if (want.drv!=='all' && (ds.driverName||'')!==want.drv) return false;
    if (want.cv!=='all'  && (ds.createdVia||'')!==want.cv) return false;
    if (want.src!=='all' && (ds.orderSource||'')!==want.src) return false;
    return true;
  }

  function applyFilters(){
    const $bar = $(SHEET_BODY).find('.aaa-oc-filter-bar');
    const q = ($bar.find('.aaa-oc-search').val() || '').trim();
    localStorage.setItem(LS.SEARCH, q);
    localStorage.setItem(LS.PAY, $bar.find('.aaa-oc-payfilter').val());
    localStorage.setItem(LS.ENV, $bar.find('.aaa-oc-envfilter').val());
    localStorage.setItem(LS.TIP, $bar.find('.aaa-oc-tipfilter').val());
    localStorage.setItem(LS.CUST,$bar.find('.aaa-oc-custfilter').val());
    localStorage.setItem(LS.ID,  $bar.find('.aaa-oc-idfilter').val());
    localStorage.setItem(LS.DRV, $bar.find('.aaa-oc-driverfilter').val());
    localStorage.setItem(LS.CV,  $bar.find('.aaa-oc-createdviafilter').val());
    localStorage.setItem(LS.SRC, $bar.find('.aaa-oc-ordersourcefilter').val());

    const root = document.querySelector(ROOT_SEL); if (!root) return;
    root.querySelectorAll(CARD_SEL).forEach(card=>{
      const ok = cardMatchesSearch(card,q) && cardMatchesFilters(card, $bar);
      card.style.display = ok ? '' : 'none';
    });
  }

  function bindHandlers(){
    const $p = $(SHEET_BODY);
    $p.on('click', '.aaa-oc-clear', function(){
      const $bar = $(SHEET_BODY).find('.aaa-oc-filter-bar');
      $bar.find('input[type=search]').val('');
      $bar.find('select').val('all');
      Object.values(LS).forEach(k=> localStorage.removeItem(k));
      applyFilters();
    });
    $p.on('input',  '.aaa-oc-search', applyFilters);
    $p.on('change', 'select', applyFilters);

    const moTarget = document.querySelector(ROOT_SEL);
    if (moTarget) {
      const mo = new MutationObserver(()=>{ populateDynamicFilters(); applyFilters(); });
      mo.observe(moTarget, { childList:true, subtree:false });
    }
    $(document).ajaxSuccess((_, __, settings)=>{
      if (typeof settings.data==='string' && settings.data.indexOf('action=aaa_oc_get_latest_orders')!==-1) {
        populateDynamicFilters(); applyFilters();
      }
    });
  }

  $(function(){
    if (!window.aaaOcPanels) { log('Panels not found—load toolbar shell first.'); return; }
    window.aaaOcPanels.add('filters', $('<div class="aaa-oc-filters-wrap"></div>').html(filtersHtml())[0]);
    mount();
    log('filters mounted');
  });

})(jQuery);
