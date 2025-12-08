/**
 * File: /wp-content/plugins/aaa-delivery-blocks-coords/assets/js/adbc-core.js
 * Purpose: Core utility functions for ADBC autocomplete.
 * Version: 1.0.0
 */
(function(){
  window.ADBC = window.ADBC || {};

  // Debug logger
  const dbg = !!(window.adbcSettings && adbcSettings.debug);
  window.ADBC.log = (...a)=>{ if(dbg){ try{ console.log('[AAA-DBlocks-Coords]',...a);}catch(_){} } };

  // Utilities
  window.ADBC.setInput = function(el,val){
    if(!el) return;
    const proto=(el.tagName==='TEXTAREA'?HTMLTextAreaElement:HTMLInputElement).prototype;
    const setter=Object.getOwnPropertyDescriptor(proto,'value')?.set;
    if(setter) setter.call(el, val==null?'':String(val)); else el.value = val==null?'':String(val);
    el.dispatchEvent(new Event('input',{bubbles:true}));
    el.dispatchEvent(new Event('change',{bubbles:true}));
  };

  window.ADBC.setSelect = function(el,val){
    if(!el) return;
    el.value = val==null?'':String(val);
    el.dispatchEvent(new Event('change',{bubbles:true}));
    el.dispatchEvent(new Event('input',{bubbles:true}));
  };

  // PAC helpers
  window.ADBC.pacHasSelected = ()=>!!document.querySelector('.pac-item.pac-selected');
  window.ADBC.pacIsVisible = ()=>{
    const pcs = Array.from(document.querySelectorAll('.pac-container'));
    return pcs.some(pc => pc.offsetParent !== null && pc.style.display !== 'none');
  };
  window.ADBC.forceHidePAC = ()=>{
    document.querySelectorAll('.pac-container').forEach(pc=>{
      pc.setAttribute('data-force-hide','1');
      pc.style.display='none';
    });
    window.ADBC.log('PAC force hidden');
  };

  // Parse place components
  window.ADBC.getComp = (p,t)=> (p.address_components||[]).find(c=>(c.types||[]).includes(t))||null;
  window.ADBC.parsePlace = function(place){
    if(!place?.geometry?.location) return null;
    const street=((window.ADBC.getComp(place,'street_number')?.long_name||'')+' '+(window.ADBC.getComp(place,'route')?.long_name||'')).trim();
    const city = window.ADBC.getComp(place,'locality')?.long_name
              || window.ADBC.getComp(place,'postal_town')?.long_name
              || window.ADBC.getComp(place,'administrative_area_level_2')?.long_name
              || window.ADBC.getComp(place,'sublocality')?.long_name || '';
    const state = window.ADBC.getComp(place,'administrative_area_level_1')?.short_name
               || window.ADBC.getComp(place,'administrative_area_level_1')?.long_name || 'CA';
    const zip   = window.ADBC.getComp(place,'postal_code')?.long_name || '';
    const country = window.ADBC.getComp(place,'country')?.short_name || 'US';
    const lat = place.geometry.location.lat(), lng = place.geometry.location.lng();
    return { address1:street, city, state, postcode:zip, country, lat, lng };
  };
})();
