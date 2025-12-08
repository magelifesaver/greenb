/**
 * File: /wp-content/plugins/aaa-delivery-blocks-coords/assets/js/adbc-checkout.js
 * Purpose: Entry point. Attaches Google Places to Woo fields, using helpers from adbc-core.js and adbc-apply.js.
 * Version: 2025-09-27-split
 */
window.ADBC = window.ADBC || {};
const { log = ()=>{}, warn = ()=>{}, err = ()=>{} } = window.ADBC;
(function () {
  if(!window.adbcSettings || !adbcSettings.fields){ console.log('[AAA-DBlocks-Coords] ABORT: missing adbcSettings'); return; }
  const { log, pacHasSelected, pacIsVisible, forceHidePAC, parsePlace, applyParsed } = window.ADBC;

  // Headless fallback: accept top prediction on Enter/Tab/Blur
  async function headlessSelectTopPrediction(input, scope, container){
    return new Promise((resolve)=>{
      const text = (input.value||'').trim();
      if(!text){ return resolve(null); }
      const svc = new google.maps.places.AutocompleteService();
      svc.getPlacePredictions({ input: text, types: ['address'] }, (preds, status)=>{
        if(status !== google.maps.places.PlacesServiceStatus.OK || !preds || !preds.length){
          return resolve(null);
        }
        const placeId = preds[0].place_id;
        const ps = new google.maps.places.PlacesService(document.createElement('div'));
        ps.getDetails({ placeId, fields: ['address_components','geometry'] }, (place, s2)=>{
          if(s2 !== google.maps.places.PlacesServiceStatus.OK || !place){ return resolve(null); }
          const parsed = parsePlace(place);
          if(parsed){ applyParsed(container, scope, parsed, input); }
          resolve(parsed || null);
        });
      });
    });
  }

  function attach(input){
    if(!input || input.__adbcBound) return;
    input.__adbcBound = true;

    const scope = detectScope(input);
    const container = input.closest('form') || document;
    log('ATTACH', {scope, id:input.id, name:input.name});

    const ac = new google.maps.places.Autocomplete(input, {
      types:['address'],
      fields:['address_components','geometry','place_id']
    });
    ac.addListener('place_changed', ()=>{
      const place = ac.getPlace();
      log('PLACE_CHANGED raw', scope, place && place.place_id);
      const parsed = parsePlace(place);
      if(parsed){ applyParsed(container, scope, parsed, input); }
      else { forceHidePAC(); }
    });

    input.addEventListener('keydown', async (e)=>{
      if(e.key==='Enter'){
        e.preventDefault();
        if(pacHasSelected()){ return; }
        if(pacIsVisible()){
          const ok = await headlessSelectTopPrediction(input, scope, container);
          if(ok){ forceHidePAC(); return; }
        }
        forceHidePAC();
      }
      if(e.key==='Tab' && pacIsVisible() && !pacHasSelected()){
        headlessSelectTopPrediction(input, scope, container).then(()=>{ forceHidePAC(); });
      }
      if(e.key==='Escape'){ e.preventDefault(); forceHidePAC(); input.blur(); }
    });

    input.addEventListener('blur', ()=>{
      if(pacIsVisible() && !pacHasSelected()){
        headlessSelectTopPrediction(input, scope, container).then(()=>{ forceHidePAC(); });
      } else {
        forceHidePAC();
      }
    });
  }

  function detectScope(input){
    const n=(input.getAttribute('name')||'').toLowerCase();
    const i=(input.id||'').toLowerCase();
    if(n.startsWith('billing_') || i.startsWith('billing')) return 'billing';
    if(n.startsWith('shipping_') || i.startsWith('shipping')) return 'shipping';
    return 'shipping';
  }

  function scan(){
    document.querySelectorAll(
      '#shipping-address_1,#billing-address_1,'+   // checkout Blocks
      '#shipping_address_1,#billing_address_1,'+   // My Account edit form
      'input[name="shipping_address_1"],input[name="billing_address_1"],'+
      'input[autocomplete="address-line1"]'
    ).forEach(attach);
    log('SCAN complete');
  }

  function start(){
    if(window.google?.maps?.places){ scan(); return; }
    const s=document.createElement('script');
    s.id='adbc-gmaps'; s.async=true; s.defer=true;
    s.src='https://maps.googleapis.com/maps/api/js?key='+encodeURIComponent(adbcSettings.apiKey)+'&libraries=places';
    s.onload=()=>scan();
    document.head.appendChild(s);
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
