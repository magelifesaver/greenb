// File: includes/geodata/assets/js/adbc-account.js
// Purpose: My Account -> Edit Address autocomplete (standalone, ES5-only, ASCII-only)
(function(){
  // Optional debugger (no-op if missing)
  var D = (window.OWFDT && OWFDT.log) ? OWFDT : { log:function(){}, warn:function(){}, err:function(){} };

  function hasGoogle(){ return !!(window.google && google.maps && google.maps.places); }

  function loadGoogle(cb){
    if (hasGoogle()) { cb(); return; }
    if (!window.adbcSettings || !adbcSettings.apiKey) { D.warn('GEO_INIT', {hasApi:false, reason:'no-key'}); return; }
    var s = document.createElement('script');
    s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(adbcSettings.apiKey) + '&libraries=places';
    s.async = true; s.defer = true; s.onload = cb;
    document.head.appendChild(s);
  }

  function parsePlace(place){
    var out = { address1:'', city:'', state:'', postcode:'', country:'', lat:'', lng:'' };
    try{
      if (place && place.geometry && place.geometry.location) {
        out.lat = String(place.geometry.location.lat());
        out.lng = String(place.geometry.location.lng());
      }
      var comps = (place && place.address_components) ? place.address_components : [];
      function pick(type, useShort){
        for (var i=0;i<comps.length;i++){
          var c = comps[i];
          if (c.types && c.types.indexOf(type) !== -1) {
            return useShort ? (c.short_name || '') : (c.long_name || '');
          }
        }
        return '';
      }
      var streetNum = pick('street_number', false);
      var route     = pick('route', false);
      out.address1  = (streetNum ? streetNum + ' ' : '') + (route || (place && place.name ? place.name : ''));
      out.city      = pick('locality', false) || pick('postal_town', false) || pick('sublocality', false) || '';
      out.state     = pick('administrative_area_level_1', true) || '';
      out.postcode  = pick('postal_code', false) || '';
      out.country   = pick('country', true) || 'US';
    }catch(e){}
    return out;
  }

  function urlencode(obj){
    var pairs = [];
    for (var k in obj){ if (obj.hasOwnProperty(k)) {
      var v = obj[k] == null ? '' : String(obj[k]);
      pairs.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
    }}
    return pairs.join('&');
  }

  function persist(scope, parsed){
    if (!window.adbcSettings || !adbcSettings.ajaxUrl || !adbcSettings.nonce) return;
    var data = {
      action:  'adbc_geocode_address',
      nonce:   adbcSettings.nonce,
      scope:   scope,
      address1: parsed.address1 || '',
      address2: '',
      city:     parsed.city || '',
      state:    parsed.state || '',
      postcode: parsed.postcode || '',
      country:  parsed.country || 'US'
    };
    var xhr = new XMLHttpRequest();
    xhr.open('POST', adbcSettings.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
      if (xhr.status >= 200 && xhr.status < 300) {
        D.log('GEO_PERSIST_OK', {scope:scope});
      } else {
        D.err('GEO_PERSIST_ERR', {scope:scope, msg:'HTTP '+xhr.status});
      }
    };
    xhr.onerror = function(){ D.err('GEO_PERSIST_ERR', {scope:scope, msg:'network'}); };
    xhr.send(urlencode(data));
  }

  function applyToForm(scope, parsed){
    function q(sel){ return document.querySelector(sel); }
    var addr = q(scope==='billing' ? '#billing_address_1' : '#shipping_address_1');
    var city = q(scope==='billing' ? '#billing_city'     : '#shipping_city');
    var st   = q(scope==='billing' ? '#billing_state'    : '#shipping_state');
    var zip  = q(scope==='billing' ? '#billing_postcode' : '#shipping_postcode');
    var ctry = q(scope==='billing' ? '#billing_country'  : '#shipping_country');

    if (addr) addr.value = parsed.address1 || '';
    if (city) city.value = parsed.city || '';
    if (zip)  zip.value  = parsed.postcode || '';

    if (st){
      var wantS = parsed.state || 'CA';
      if (st.tagName === 'SELECT'){
        var foundS = null, i, o;
        for (i=0;i<st.options.length;i++){
          o = st.options[i];
          if (o.value === wantS || o.text === wantS){ foundS = o.value; break; }
        }
        st.value = foundS || wantS;
      } else {
        st.value = wantS;
      }
      st.dispatchEvent(new Event('change', {bubbles:true}));
    }

    if (ctry){
      var wantC = parsed.country || 'US';
      if (ctry.tagName === 'SELECT'){
        var foundC = null, j, oc;
        for (j=0;j<ctry.options.length;j++){
          oc = ctry.options[j];
          if (oc.value === wantC || oc.text === wantC){ foundC = oc.value; break; }
        }
        ctry.value = foundC || wantC;
      } else {
        ctry.value = wantC;
      }
      ctry.dispatchEvent(new Event('change', {bubbles:true}));
    }

    persist(scope, parsed);
  }

  function bind(input, scope){
    var ac = new google.maps.places.Autocomplete(input, { types:['address'], fields:['address_components','geometry','name'] });
    ac.addListener('place_changed', function(){
      var p = ac.getPlace();
      var parsed = parsePlace(p);
      D.log('GEO_PLACE_SELECTED', {scope:scope, parsed:parsed, hasGeometry: !!(p && p.geometry)});
      applyToForm(scope, parsed);
    });
    D.log('GEO_PLACES_BOUND', {scope:scope});
  }

  function init(){
    var billing  = document.querySelector('#billing_address_1');
    var shipping = document.querySelector('#shipping_address_1');
    if (!billing && !shipping){ D.warn('GEO_INIT', {hasApi:true, reason:'no-inputs'}); return; }
    if (billing)  bind(billing,  'billing');
    if (shipping) bind(shipping, 'shipping');
  }

  D.log('GEO_INIT', {hasApi:false});
  loadGoogle(init);
})();
