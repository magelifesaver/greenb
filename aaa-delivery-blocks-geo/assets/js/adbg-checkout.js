
/**
 * File: assets/js/adbg-checkout.js
 * Purpose: On checkout, fetch travel/ETA and populate Additional Checkout Fields (hidden).
 */
(function(){
  var cfg = window.adbgGeoSettings || {};
  var dbg = !!cfg.debug;
  function log(){ if(dbg && window.console){ console.log.apply(console, ['[AAA-DBlocks-Geo]',].concat([].slice.call(arguments))); } }

  function setInput(el,val){
    if(!el) return;
    var proto=(el.tagName==='TEXTAREA'?HTMLTextAreaElement:HTMLInputElement).prototype;
    var setter=Object.getOwnPropertyDescriptor(proto,'value') && Object.getOwnPropertyDescriptor(proto,'value').set;
    if(setter) setter.call(el, val==null?'':String(val)); else el.value = val==null?'':String(val);
    el.dispatchEvent(new Event('input',{bubbles:true}));
    el.dispatchEvent(new Event('change',{bubbles:true}));
  }

  function pickAdditional(scope, fieldId){
    var hyphen = scope + '-' + fieldId.replace(/\//g,'-');
    var sels = [
      '[data-additional-field-id="'+fieldId+'"][data-checkout-section="'+scope+'"] input',
      '[data-additional-field-id="'+fieldId+'"] input',
      '#'+hyphen,
      'input[name="'+hyphen+'"]'
    ];
    for(var i=0;i<sels.length;i++){
      var el = document.querySelector(sels[i]);
      if(el) return el;
    }
    return null;
  }

  function writeHidden(scope, payload){
    var map = {
      'aaa-delivery-blocks/distance-meters': payload.distance_m,
      'aaa-delivery-blocks/travel-seconds':   payload.travel_s,
      'aaa-delivery-blocks/eta-seconds':      payload.eta_s,
      'aaa-delivery-blocks/eta-range':        (payload.eta_range_s ? (payload.eta_range_s[0]+','+payload.eta_range_s[1]) : ''),
      'aaa-delivery-blocks/eta-origin':       payload.origin_id,
      'aaa-delivery-blocks/travel-refreshed': payload.refreshed
    };
    Object.keys(map).forEach(function(fid){
      var el = pickAdditional(scope, fid);
      if(el){ setInput(el, map[fid]); }
    });
  }

  function fetchTravel(scope){
    var url = cfg.ajaxUrl, nonce = cfg.nonce;
    if(!url || !nonce) return;
    var body = new URLSearchParams({ action:'adbg_get_travel', scope: scope, nonce: nonce });
    fetch(url, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: body.toString()
    })
    .then(function(r){ return r.json(); })
    .then(function(j){
      if(!j || !j.success){ log('Travel fetch failed', j && j.data); return; }
      writeHidden('shipping', j.data);
      writeHidden('billing',  j.data); // mirror to billing for snapshot symmetry
      log('Travel populated', j.data);
    })
    .catch(function(e){ log('Travel error', e); });
  }

  function boot(){ fetchTravel('shipping'); }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
