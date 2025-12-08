/**
 * File: /wp-content/plugins/aaa-delivery-blocks-coords/assets/js/adbc-apply.js
 * Purpose: Functions for mapping parsed places into Woo fields and hidden coords.
 * Version: 1.0.0
 */
(function(){
  window.ADBC = window.ADBC || {};
  const { log, setInput, setSelect } = window.ADBC;

  window.ADBC.pick = function(container, scope, patterns, label){
    const sels = patterns.map(s=>s.replace(/\{scope\}/g, scope));
    for(const sel of sels){
      const el = container.querySelector(sel) || document.querySelector(sel);
      if(el){ log('PICK', scope, label||sel, el); return el; }
    }
    return null;
  };

  window.ADBC.pickAdditional = function(scope, fieldId){
    const hyphen = `${scope}-${fieldId.replace(/\//g,'-')}`;
    const sels = [
      `[data-additional-field-id="${fieldId}"] input`,
      `#${hyphen}`,
      `input[name="${hyphen}"]`
    ];
    for(const sel of sels){ const el=document.querySelector(sel); if(el){ return el; } }
    return null;
  };

  window.ADBC.writeHidden = function(scope, parsed){
    const latEl = window.ADBC.pickAdditional(scope, adbcSettings.fields.lat);
    const lngEl = window.ADBC.pickAdditional(scope, adbcSettings.fields.lng);
    const flagEl= window.ADBC.pickAdditional(scope, adbcSettings.fields.flag);
    if(latEl)  setInput(latEl,  parsed.lat);
    if(lngEl)  setInput(lngEl,  parsed.lng);
    if(flagEl) setInput(flagEl, (parsed.lat&&parsed.lng)?'yes':'no');
    log('WRITE_HIDDEN', scope, {lat:parsed.lat, lng:parsed.lng});
  };

  window.ADBC.applyParsed = function(container, scope, parsed, src){
    const addr1 = window.ADBC.pick(container, scope, ['#\\{scope\\}-address_1','input[autocomplete="address-line1"]','input[id*="address_1"]'], 'address_1');
    const city  = window.ADBC.pick(container, scope, ['#\\{scope\\}-city','input[name*="[city]"]','input[id*="city"]'], 'city');
    const state = window.ADBC.pick(container, scope, ['#\\{scope\\}-state','select[name*="[state]"]','input[id*="[state]"]','select[id*="state"]'], 'state');
    const zip   = window.ADBC.pick(container, scope, ['#\\{scope\\}-postcode','input[name*="[postcode]"]','input[id*="post"]'], 'postcode');
    const ctry  = window.ADBC.pick(container, scope, ['#\\{scope\\}-country','select[name*="[country]"]','select[id*="country"]'], 'country');

    if(addr1) setInput(addr1, parsed.address1||'');
    if(city)  setInput(city,  parsed.city||'');
    if(state){
      if(state.tagName==='SELECT'){
        const want = parsed.state||'CA';
        const opt = Array.prototype.find.call(state.options, o=> (o.value===want || o.text===want));
        setSelect(state, opt?opt.value:want);
      } else setInput(state, parsed.state||'CA');
    }
    if(zip) setInput(zip, parsed.postcode||'');
    if(ctry){
      if(ctry.tagName==='SELECT'){
        const want = parsed.country||'US';
        const opt = Array.prototype.find.call(ctry.options, o=> (o.value===want || o.text===want));
        setSelect(ctry, opt?opt.value:want);
      } else setInput(ctry, parsed.country||'US');
    }

    requestAnimationFrame(()=>{
      if(city && !city.value) setInput(city, parsed.city||'');
      if(zip  && !zip.value)  setInput(zip,  parsed.postcode||'');
      if(state && !state.value){
        if(state.tagName==='SELECT') setSelect(state, parsed.state||'CA');
        else setInput(state, parsed.state||'CA');
      }
      if(ctry && !ctry.value) setSelect(ctry, parsed.country||'US');
    });

    window.ADBC.writeHidden(scope, parsed);
    log('APPLY_PARSED', scope, parsed);
    if(src){
      setTimeout(()=>{ try{ src.blur(); }catch(_){ } window.ADBC.forceHidePAC(); document.body.click(); }, 100);
    }
  };
})();
